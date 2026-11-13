<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRoleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_super_admin_can_manage_users(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $role = Role::where('slug', 'diklat-admin')->firstOrFail();

        $this->actingAs($admin)->get(route('users.create'))->assertOk();
        $this->post(route('users.store'), [
            'name' => 'Admin Unit Test', 'nik' => '99887766', 'email' => 'unit-admin@example.test',
            'phone' => '08123456789', 'position' => 'Admin Unit', 'active' => 1,
            'password' => 'password-baru', 'password_confirmation' => 'password-baru', 'roles' => [$role->id],
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'unit-admin@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('password-baru', $user->password));
        $this->assertTrue($user->roles()->whereKey($role->id)->exists());

        $this->put(route('users.update', $user), [
            'name' => 'Admin Unit Diperbarui', 'nik' => '99887766', 'email' => 'unit-admin@example.test',
            'phone' => '08123456789', 'position' => 'Koordinator', 'active' => 0, 'password' => '',
            'password_confirmation' => '', 'roles' => [$role->id],
        ])->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Admin Unit Diperbarui', 'active' => 0]);

        $this->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_super_admin_can_manage_roles_and_permissions(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $permissions = Permission::take(2)->pluck('id')->all();

        $this->actingAs($admin)->get(route('roles.create'))->assertOk();
        $this->post(route('roles.store'), [
            'name' => 'Admin Unit', 'slug' => 'admin-unit', 'scope' => 'internal', 'permissions' => $permissions,
        ])->assertRedirect(route('users.index', ['tab' => 'roles']));

        $role = Role::where('slug', 'admin-unit')->firstOrFail();
        $this->assertCount(2, $role->permissions);

        $this->put(route('roles.update', $role), [
            'name' => 'Koordinator Unit', 'slug' => 'koordinator-unit', 'scope' => 'internal', 'permissions' => [$permissions[0]],
        ])->assertRedirect(route('users.index', ['tab' => 'roles']));
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'slug' => 'koordinator-unit']);
        $this->assertCount(1, $role->fresh()->permissions);

        $this->delete(route('roles.destroy', $role))->assertRedirect(route('users.index', ['tab' => 'roles']));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_non_super_admin_cannot_access_user_role_crud(): void
    {
        $user = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('roles.create'))->assertForbidden();
    }

    public function test_user_and_role_pagination_keep_their_own_tab_and_query_parameters(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        User::factory()->count(16)->sequence(fn ($sequence) => [
            'name' => sprintf('Pagination User %02d', $sequence->index + 1),
            'email' => sprintf('pagination-user-%02d@example.test', $sequence->index + 1),
        ])->create();

        $users = $this->actingAs($admin)->get(route('users.index', ['tab' => 'users', 'q' => 'Pagination User']));
        $users->assertOk()
            ->assertSee('user_page=2', false)
            ->assertSee('tab=users', false)
            ->assertSee('q=Pagination%20User', false)
            ->assertDontSee('role_page=', false);

        $rolesToCreate = max(0, 16 - Role::count());
        foreach (range(1, $rolesToCreate) as $index) {
            Role::create([
                'name' => sprintf('ZZ Pagination Role %02d', $index),
                'slug' => sprintf('zz-pagination-role-%02d', $index),
                'scope' => 'internal',
            ]);
        }

        $roles = $this->get(route('users.index', ['tab' => 'roles']));
        $roles->assertOk()
            ->assertSee('role_page=2', false)
            ->assertSee('tab=roles', false)
            ->assertDontSee('user_page=', false);
    }

    public function test_current_account_and_super_admin_role_are_protected(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $role = Role::where('slug', 'super-admin')->firstOrFail();

        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertSessionHasErrors('user');
        $this->delete(route('roles.destroy', $role))->assertSessionHasErrors('role');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
