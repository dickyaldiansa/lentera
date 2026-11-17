<?php

namespace Tests\Feature;

use App\Models\{Tariff, TariffRegulation, User};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TariffRegulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'title' => 'Perda Tarif Pengujian', 'number' => '7', 'year' => 2026,
            'effective_from' => '2026-01-01', 'status' => 'current',
            'document' => UploadedFile::fake()->createWithContent('perda.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"),
        ], $changes);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@lentera.test')->firstOrFail();
    }

    public function test_admin_uploads_and_applicant_can_view_and_download_pdf(): void
    {
        $this->actingAs($this->admin())->get(route('master.dataset', 'tariffs'))
            ->assertOk()->assertSee('Dokumen Dasar Hukum Tarif')->assertSee('Tambah Dokumen');
        $this->get(route('tariff-regulations.create'))->assertOk();
        $this->post(route('tariff-regulations.store'), $this->payload())
            ->assertRedirect(route('master.dataset', 'tariffs'))->assertSessionHasNoErrors();
        $regulation = TariffRegulation::firstOrFail();
        Storage::disk('local')->assertExists($regulation->file_path);
        $this->assertTrue($regulation->is_current);
        $this->assertDatabaseHas('audit_logs', ['module' => 'tariff_regulations', 'action' => 'create']);

        $this->actingAs(User::where('email', 'pemohon@lentera.test')->firstOrFail());
        $this->get(route('tariffs.index'))->assertOk()->assertSee('Perda Tarif Pengujian')
            ->assertSee('Nomor 7 Tahun 2026')->assertSee('Berlaku')->assertSee('Lihat PDF')->assertSee('Unduh PDF')->assertDontSee('Tambah Dokumen');
        $this->get(route('tariff-regulations.document', $regulation))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('tariff-regulations.document', [$regulation, 'download' => 1]))->assertDownload();
    }

    public function test_replacing_pdf_preserves_old_version_and_only_one_document_is_current(): void
    {
        $prices = Tariff::orderBy('id')->pluck('amount', 'id')->all();
        $this->actingAs($this->admin())->post(route('tariff-regulations.store'), $this->payload())->assertSessionHasNoErrors();
        $original = TariffRegulation::firstOrFail();
        $oldPath = $original->file_path;
        $this->get(route('tariff-regulations.edit', $original))->assertOk();
        $this->put(route('tariff-regulations.update', $original), $this->payload(['title' => 'Perda Tarif Revisi']))
            ->assertSessionHasNoErrors()->assertRedirect(route('master.dataset', 'tariffs'));
        $replacement = TariffRegulation::where('is_current', true)->firstOrFail();
        $this->assertNotSame($original->id, $replacement->id);
        $this->assertNull($original->fresh()->is_current);
        $this->assertSame('Perda Tarif Pengujian', $original->fresh()->title);
        Storage::disk('local')->assertExists($oldPath);
        Storage::disk('local')->assertExists($replacement->file_path);
        $this->get(route('tariff-regulations.document', $original))->assertOk();

        // Metadata changes keep the PDF, while activation archives the current version.
        $this->put(route('tariff-regulations.update', $original), $this->payload(['document' => null, 'title' => 'Perda Tarif Dikoreksi']))->assertSessionHasNoErrors();
        $this->assertSame(2, TariffRegulation::count());
        $this->assertSame(1, TariffRegulation::where('is_current', true)->count());
        $this->assertNull($replacement->fresh()->is_current);
        $this->assertSame($oldPath, $original->fresh()->file_path);
        $this->assertSame($prices, Tariff::orderBy('id')->pluck('amount', 'id')->all());
        $this->assertDatabaseHas('audit_logs', ['module' => 'tariff_regulations', 'action' => 'archive']);
    }

    public function test_archives_can_be_added_without_replacing_the_current_document(): void
    {
        $this->actingAs($this->admin())->post(route('tariff-regulations.store'), $this->payload())->assertSessionHasNoErrors();
        $current = TariffRegulation::firstOrFail();
        $this->post(route('tariff-regulations.store'), $this->payload(['status' => 'archived', 'year' => 2025]))->assertSessionHasNoErrors();
        $this->post(route('tariff-regulations.store'), $this->payload(['status' => 'archived', 'year' => 2024]))->assertSessionHasNoErrors();
        $this->assertTrue($current->fresh()->is_current);
        $this->assertSame(2, TariffRegulation::whereNull('is_current')->count());
        $this->get(route('tariffs.index'))->assertOk()->assertSee('Arsip');
    }

    public function test_invalid_uploads_are_rejected_and_missing_files_return_404(): void
    {
        $this->actingAs($this->admin());
        foreach ([null, UploadedFile::fake()->create('not-pdf.txt', 1, 'text/plain'), UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')] as $file) {
            $this->post(route('tariff-regulations.store'), $this->payload(['document' => $file]))->assertSessionHasErrors('document');
        }
        $this->assertSame(0, TariffRegulation::count());
        $this->post(route('tariff-regulations.store'), $this->payload())->assertSessionHasNoErrors();
        $regulation = TariffRegulation::firstOrFail();
        $this->put(route('tariff-regulations.update', $regulation), $this->payload(['year' => 'invalid', 'status' => 'invalid']))
            ->assertSessionHasErrors(['year', 'status']);
        $this->assertSame(1, TariffRegulation::count());
        Storage::disk('local')->delete($regulation->file_path);
        $this->get(route('tariff-regulations.document', $regulation))->assertNotFound();
    }

    public function test_guests_and_applicants_cannot_manage_documents(): void
    {
        $this->get(route('tariff-regulations.create'))->assertRedirect(route('login'));
        $this->actingAs($this->admin())->post(route('tariff-regulations.store'), $this->payload())->assertSessionHasNoErrors();
        $regulation = TariffRegulation::firstOrFail();
        $this->actingAs(User::where('email', 'pemohon@lentera.test')->firstOrFail());
        $this->get(route('tariff-regulations.create'))->assertForbidden();
        $this->get(route('tariff-regulations.edit', $regulation))->assertForbidden();
        $this->post(route('tariff-regulations.store'), $this->payload())->assertForbidden();
        $this->put(route('tariff-regulations.update', $regulation), $this->payload())->assertForbidden();
        auth()->logout();
        $this->get(route('tariff-regulations.document', $regulation))->assertRedirect(route('login'));
        $this->assertSame(1, TariffRegulation::count());
    }
}
