<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DiklatContact;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DiklatContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_add_and_edit_number_and_links_follow_the_saved_number(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('https://wa.me/');
        $this->actingAs(User::where('email', 'admin@lentera.test')->firstOrFail());
        $this->get(route('master.index'))->assertOk()->assertSee(route('master.diklat-contact.edit'));
        $this->get(route('master.diklat-contact.edit'))->assertOk()->assertSee('Tambah Nomor WhatsApp');

        foreach (['0812-3456-7890' => '6281234567890', '+62 813 4567 8901' => '6281345678901', '6281456789012' => '6281456789012'] as $input => $expected) {
            $this->put(route('master.diklat-contact.update'), ['whatsapp_number' => (string) $input])
                ->assertRedirect(route('master.diklat-contact.edit'))->assertSessionHasNoErrors();
            $this->assertDatabaseHas('settings', ['key' => DiklatContact::KEY, 'value' => $expected]);
            $this->get(route('home'))->assertOk()->assertSee('https://wa.me/'.$expected)->assertSee('Hubungi Admin Diklat');
            $this->get(route('dashboard'))->assertOk()->assertSee('https://wa.me/'.$expected);
        }

        $this->assertSame(1, DB::table('settings')->where('key', DiklatContact::KEY)->count());
        $this->assertDatabaseHas('audit_logs', ['module' => 'master.diklat_contact', 'action' => 'update']);
    }

    public function test_invalid_number_is_rejected_without_overwriting_the_contact(): void
    {
        $this->actingAs(User::where('email', 'admin@lentera.test')->firstOrFail());
        $this->put(route('master.diklat-contact.update'), ['whatsapp_number' => '081234567890'])->assertSessionHasNoErrors();
        foreach (['', '123', '08123abc456789', '6281234567890123456', 'https://example.com', '+1 234 567 8901'] as $number) {
            $this->put(route('master.diklat-contact.update'), ['whatsapp_number' => $number])
                ->assertSessionHasErrors('whatsapp_number');
        }
        $this->assertSame('6281234567890', DiklatContact::number());
    }

    public function test_only_users_with_master_permission_can_manage_the_contact(): void
    {
        $this->get(route('master.diklat-contact.edit'))->assertRedirect(route('login'));
        $this->put(route('master.diklat-contact.update'), ['whatsapp_number' => '081234567890'])->assertRedirect(route('login'));
        $this->actingAs(User::where('email', 'pemohon@lentera.test')->firstOrFail());
        $this->get(route('master.diklat-contact.edit'))->assertForbidden();
        $this->put(route('master.diklat-contact.update'), ['whatsapp_number' => '081234567890'])->assertForbidden();
        $this->assertNull(DiklatContact::number());
    }
}
