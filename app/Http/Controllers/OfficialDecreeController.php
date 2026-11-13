<?php

namespace App\Http\Controllers;

use App\Models\OfficialDecree;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OfficialDecreeController extends Controller
{
    private const CATEGORIES = [
        'diklat' => 'Diklat',
        'kamkordik' => 'KAMKORDIK',
        'ethics' => 'Komite Etik',
    ];

    public function index(Request $request)
    {
        $query = OfficialDecree::with('manager');
        $this->scopeCategories($request, $query);

        $decrees = $query
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q
                ->where('number', 'like', '%'.trim($request->q).'%')
                ->orWhere('title', 'like', '%'.trim($request->q).'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->integer('year')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('year')->orderByDesc('issued_at')->paginate(15)->withQueryString();

        return view('decrees.index', [
            'decrees' => $decrees,
            'categories' => $this->visibleCategories($request),
            'years' => OfficialDecree::query()->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }

    public function create(Request $request)
    {
        return $this->form($request, new OfficialDecree);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->authorizeCategory($request, $data['category']);
        $file = $data['document'] ?? null;
        unset($data['document']);

        if ($file) {
            $data['file_path'] = $file->store('official-decrees/'.$data['year'], 'local');
            $data['original_filename'] = $file->getClientOriginalName();
        }
        $data['created_by'] = $request->user()->id;

        $decree = OfficialDecree::create($data);
        AuditService::record('decrees', 'decree.create', $decree, [], $decree->toArray());

        return redirect()->route('decrees.index')->with('success', 'SK berhasil ditambahkan.');
    }

    public function edit(Request $request, OfficialDecree $decree)
    {
        $this->authorizeCategory($request, $decree->category);

        return $this->form($request, $decree);
    }

    public function update(Request $request, OfficialDecree $decree)
    {
        $this->authorizeCategory($request, $decree->category);
        $data = $this->validated($request, $decree);
        $this->authorizeCategory($request, $data['category']);
        $file = $data['document'] ?? null;
        unset($data['document']);
        $old = $decree->toArray();

        DB::transaction(function () use ($decree, $data, $file, $old) {
            if ($file) {
                $oldPath = $decree->file_path;
                $data['file_path'] = $file->store('official-decrees/'.$data['year'], 'local');
                $data['original_filename'] = $file->getClientOriginalName();
                if ($oldPath) {
                    Storage::disk('local')->delete($oldPath);
                }
            }
            $decree->update($data);
            AuditService::record('decrees', 'decree.update', $decree, $old, $decree->fresh()->toArray());
        });

        return redirect()->route('decrees.index')->with('success', 'SK berhasil diperbarui.');
    }

    public function status(Request $request, OfficialDecree $decree)
    {
        $this->authorizeCategory($request, $decree->category);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'active', 'expired', 'revoked'])]]);
        $old = $decree->toArray();
        $decree->update($data);
        AuditService::record('decrees', 'decree.status', $decree, $old, $decree->fresh()->toArray());

        return back()->with('success', 'Status SK berhasil diperbarui.');
    }

    public function download(Request $request, OfficialDecree $decree)
    {
        $this->authorizeVisibleCategory($request, $decree->category);
        abort_unless($decree->file_path && Storage::disk('local')->exists($decree->file_path), 404);

        return Storage::disk('local')->download($decree->file_path, $decree->original_filename ?: 'SK-'.$decree->year.'.pdf');
    }

    private function form(Request $request, OfficialDecree $decree)
    {
        return view('decrees.form', [
            'decree' => $decree,
            'categories' => $this->manageableCategories($request),
            'managers' => User::where('active', true)->whereHas('roles', fn ($q) => $q->whereIn('slug', [
                'admin-umum', 'arsiparis', 'diklat-admin', 'kamkordik-admin', 'ethics-admin',
            ]))->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request, ?OfficialDecree $decree = null): array
    {
        return $request->validate([
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'number' => ['required', 'string', 'max:150', Rule::unique('official_decrees', 'number')->ignore($decree)],
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 5)],
            'issued_at' => ['nullable', 'date'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'signatory' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(['draft', 'active', 'expired', 'revoked'])],
            'notes' => ['nullable', 'string', 'max:3000'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);
    }

    private function visibleCategories(Request $request): array
    {
        return array_intersect_key(self::CATEGORIES, array_flip($this->categoryKeys($request, false)));
    }

    private function manageableCategories(Request $request): array
    {
        return array_intersect_key(self::CATEGORIES, array_flip($this->categoryKeys($request, true)));
    }

    private function categoryKeys(Request $request, bool $managing): array
    {
        $user = $request->user();
        if ($user->hasRole('super-admin', 'admin-umum', 'arsiparis')) {
            return array_keys(self::CATEGORIES);
        }

        $categories = [];
        if ($user->hasRole('diklat-admin', 'diklat-head', 'diklat-coordinator')) {
            $categories[] = 'diklat';
        }
        if ($user->hasRole('kamkordik-admin', 'kamkordik-head')) {
            $categories[] = 'kamkordik';
        }
        if ($user->hasRole('ethics-admin', 'ethics-chair')) {
            $categories[] = 'ethics';
        }

        return $managing ? $categories : ($categories ?: array_keys(self::CATEGORIES));
    }

    private function scopeCategories(Request $request, $query): void
    {
        $categories = array_keys($this->visibleCategories($request));
        if (count($categories) < count(self::CATEGORIES)) {
            $query->whereIn('category', $categories);
        }
    }

    private function authorizeCategory(Request $request, string $category): void
    {
        abort_unless($request->user()->canDo('sk.manage') && in_array($category, $this->categoryKeys($request, true), true), 403);
    }

    private function authorizeVisibleCategory(Request $request, string $category): void
    {
        abort_unless(in_array($category, $this->categoryKeys($request, false), true), 403);
    }
}
