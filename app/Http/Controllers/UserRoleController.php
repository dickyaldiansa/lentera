<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->input('q'));
        $users = User::with('roles')->when($term, fn ($query) => $query->where(function ($query) use ($term) {
            $query->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('nik', 'like', "%{$term}%");
        }))->orderBy('name')->paginate(15, ['*'], 'user_page')->appends(array_filter([
            'tab' => 'users',
            'q' => $term,
        ], fn ($value) => filled($value)));
        $roles = Role::withCount('users')->with('permissions')->orderBy('name')->paginate(15, ['*'], 'role_page')->appends([
            'tab' => 'roles',
        ]);

        return view('operations.users', compact('users', 'roles', 'term'));
    }

    public function createUser()
    {
        return $this->userForm(new User);
    }

    public function storeUser(Request $request)
    {
        $data = $this->validatedUser($request);
        $roleIds = $data['roles'];
        unset($data['roles'], $data['password_confirmation']);
        $user = DB::transaction(function () use ($data, $roleIds) {
            $user = User::create($data);
            $user->roles()->sync($roleIds);
            AuditService::record('users', 'user.create', $user, [], $this->userAuditData($user->load('roles')));

            return $user;
        });

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function editUser(User $user)
    {
        return $this->userForm($user);
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $this->validatedUser($request, $user);
        $roleIds = $data['roles'];
        unset($data['roles'], $data['password_confirmation']);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        if ($request->user()->is($user)) {
            $data['active'] = true;
            $superAdminId = Role::where('slug', 'super-admin')->value('id');
            if ($request->user()->hasRole('super-admin') && $superAdminId && ! in_array($superAdminId, $roleIds)) {
                return back()->withInput()->withErrors(['roles' => 'Role Super Administrator pada akun yang sedang digunakan tidak dapat dilepas.']);
            }
        }
        $old = $this->userAuditData($user->load('roles'));
        DB::transaction(function () use ($user, $data, $roleIds, $old) {
            $user->update($data);
            $user->roles()->sync($roleIds);
            AuditService::record('users', 'user.update', $user, $old, $this->userAuditData($user->fresh('roles')));
        });

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroyUser(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'Akun yang sedang digunakan tidak dapat dihapus.']);
        }
        try {
            DB::transaction(function () use ($user) {
                AuditService::record('users', 'user.delete', $user, $this->userAuditData($user->load('roles')), []);
                $user->delete();
            });
        } catch (QueryException) {
            return back()->withErrors(['user' => 'Pengguna masih memiliki riwayat transaksi. Nonaktifkan akun untuk mempertahankan jejak audit.']);
        }

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function toggleUser(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.']);
        }
        $old = $this->userAuditData($user->load('roles'));
        $user->update(['active' => ! $user->active]);
        AuditService::record('users', 'user.status', $user, $old, $this->userAuditData($user->fresh('roles')));

        return back()->with('success', 'Status akun pengguna berhasil diperbarui.');
    }

    public function createRole()
    {
        return $this->roleForm(new Role);
    }

    public function storeRole(Request $request)
    {
        $data = $this->validatedRole($request);
        $permissionIds = $data['permissions'] ?? [];
        unset($data['permissions']);
        $role = DB::transaction(function () use ($data, $permissionIds) {
            $role = Role::create($data);
            $role->permissions()->sync($permissionIds);
            AuditService::record('users', 'role.create', $role, [], $this->roleAuditData($role->load('permissions')));

            return $role;
        });

        return redirect()->route('users.index', ['tab' => 'roles'])->with('success', "Role {$role->name} berhasil ditambahkan.");
    }

    public function editRole(Role $role)
    {
        return $this->roleForm($role);
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $this->validatedRole($request, $role);
        $permissionIds = $data['permissions'] ?? [];
        unset($data['permissions']);
        if ($role->slug === 'super-admin') {
            $data['slug'] = 'super-admin';
        }
        $old = $this->roleAuditData($role->load('permissions'));
        DB::transaction(function () use ($role, $data, $permissionIds, $old) {
            $role->update($data);
            $role->permissions()->sync($permissionIds);
            AuditService::record('users', 'role.update', $role, $old, $this->roleAuditData($role->fresh('permissions')));
        });

        return redirect()->route('users.index', ['tab' => 'roles'])->with('success', 'Role berhasil diperbarui.');
    }

    public function destroyRole(Role $role)
    {
        if ($role->slug === 'super-admin') {
            return back()->withErrors(['role' => 'Role Super Administrator tidak dapat dihapus.']);
        }
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Role masih digunakan oleh pengguna. Pindahkan role pengguna terlebih dahulu.']);
        }
        DB::transaction(function () use ($role) {
            AuditService::record('users', 'role.delete', $role, $this->roleAuditData($role->load('permissions')), []);
            $role->delete();
        });

        return redirect()->route('users.index', ['tab' => 'roles'])->with('success', 'Role berhasil dihapus.');
    }

    private function userForm(User $user)
    {
        return view('operations.user-form', ['user' => $user, 'roles' => Role::orderBy('name')->get(), 'institutions' => Institution::orderBy('name')->get()]);
    }

    private function roleForm(Role $role)
    {
        return view('operations.role-form', ['role' => $role, 'permissions' => Permission::orderBy('slug')->get()->groupBy(fn ($permission) => str($permission->slug)->before('.')->toString())]);
    }

    private function validatedUser(Request $request, ?User $user = null): array
    {
        $password = $user?->exists ? ['nullable', 'string', 'min:8', 'confirmed'] : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'], 'nik' => ['nullable', 'string', 'max:100', Rule::unique('users', 'nik')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)], 'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:150'], 'institution_id' => ['nullable', 'exists:institutions,id'], 'active' => ['required', 'boolean'],
            'password' => $password, 'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['integer', 'distinct', 'exists:roles,id'],
        ]);
    }

    private function validatedRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('roles', 'slug')->ignore($role)],
            'scope' => ['required', Rule::in(['internal', 'external'])], 'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ], ['slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.']);
    }

    private function userAuditData(User $user): array
    {
        return $user->only(['name', 'nik', 'email', 'phone', 'position', 'institution_id', 'active']) + ['role_ids' => $user->roles->pluck('id')->all()];
    }

    private function roleAuditData(Role $role): array
    {
        return $role->only(['name', 'slug', 'scope']) + ['permission_ids' => $role->permissions->pluck('id')->all()];
    }
}
