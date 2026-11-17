<?php

namespace App\Http\Controllers;

use App\Models\TariffRegulation;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TariffRegulationController extends Controller
{
    public function create()
    {
        return view('tariffs.regulation-form', ['regulation' => new TariffRegulation]);
    }

    public function edit(TariffRegulation $regulation)
    {
        return view('tariffs.regulation-form', compact('regulation'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new TariffRegulation);
    }

    public function update(Request $request, TariffRegulation $regulation)
    {
        return $this->save($request, $regulation);
    }

    private function save(Request $request, TariffRegulation $regulation)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 5)],
            'effective_from' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['current', 'archived'])],
            'document' => [$regulation->exists ? 'nullable' : 'required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:10240'],
        ], [], ['title' => 'judul peraturan', 'number' => 'nomor peraturan', 'year' => 'tahun', 'document' => 'dokumen PDF']);
        $file = $data['document'] ?? null;
        $data['is_current'] = $data['status'] === 'current' ? true : null;
        unset($data['document'], $data['status']);
        $path = null;

        try {
            if ($file) {
                $path = $file->store('tariff-regulations', 'local');
                abort_unless($path, 500, 'Dokumen gagal disimpan. Silakan coba lagi.');
                $data['file_path'] = $path;
                $data['original_filename'] = $file->getClientOriginalName();
            }

            DB::transaction(function () use ($request, $regulation, $data, $file) {
                $old = $regulation->exists ? $regulation->toArray() : [];

                // Replacing a PDF creates a new version and keeps its old file accessible.
                $replacement = $regulation->exists && $file;
                if ($replacement || $data['is_current']) {
                    $toArchive = TariffRegulation::query()->where(function ($query) use ($regulation, $replacement, $data) {
                        if ($data['is_current']) {
                            $query->where('is_current', true);
                        }
                        if ($replacement) {
                            $query->orWhere('id', $regulation->id);
                        }
                    })->lockForUpdate()->get();
                    foreach ($toArchive as $previous) {
                        if (!$replacement && $previous->id === $regulation->id) {
                            continue;
                        }
                        $before = $previous->toArray();
                        $previous->update(['is_current' => null]);
                        AuditService::record('tariff_regulations', 'archive', $previous, $before, $previous->toArray());
                    }
                }

                $target = $replacement ? new TariffRegulation : $regulation;
                if (!$target->exists) {
                    $data['created_by'] = $request->user()->id;
                }
                $creating = !$target->exists;
                $target->fill($data)->save();
                AuditService::record('tariff_regulations', $creating ? 'create' : 'update', $target, $creating ? [] : $old, $target->toArray());
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return redirect()->route('master.dataset', 'tariffs')->with('success', 'Dokumen dasar hukum tarif berhasil disimpan.');
    }

    public function document(Request $request, TariffRegulation $regulation)
    {
        abort_unless($request->user()->canDo('diklat.view') || $request->user()->canDo('master.manage'), 403);
        abort_unless(Storage::disk('local')->exists($regulation->file_path), 404, 'Dokumen tidak ditemukan.');
        $filename = 'Dasar-Hukum-Tarif-'.$regulation->year.'-'.$regulation->id.'.pdf';
        if ($request->boolean('download')) {
            return Storage::disk('local')->download($regulation->file_path, $filename);
        }

        return Storage::disk('local')->response($regulation->file_path, $filename, [
            'Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
