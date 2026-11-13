<?php

namespace Tests\Feature;

use App\Models\OfficialDecree;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialDecreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_umum_can_manage_accounts_without_becoming_super_admin(): void
    {
        $titin = User::where('email', 'sunartititin5678@gmail.com')->firstOrFail();

        $this->assertTrue($titin->hasRole('admin-umum'));
        $this->assertTrue($titin->hasRole('arsiparis'));
        $this->assertFalse($titin->hasRole('super-admin'));
        $this->actingAs($titin)->get(route('users.index'))->assertOk();
        $this->get(route('master.index'))->assertOk();
        $this->get(route('decrees.index'))->assertOk();
        $this->get(route('decrees.create'))->assertOk();
    }

    public function test_unit_admin_only_manages_decrees_in_own_category(): void
    {
        $mytha = User::where('email', 'mytha@lentera.test')->firstOrFail();

        $this->actingAs($mytha)->get(route('decrees.index'))
            ->assertOk()->assertSee('SK Diklat Tahun 2024')->assertDontSee('SK KAMKORDIK');

        $this->post(route('decrees.store'), [
            'category' => 'ethics', 'number' => 'SK/ETIK/TEST/001', 'title' => 'SK Etik Tidak Berwenang',
            'year' => 2026, 'status' => 'active',
        ])->assertForbidden();

        $this->post(route('decrees.store'), [
            'category' => 'diklat', 'number' => 'SK/DIKLAT/TEST/001', 'title' => 'SK Diklat Pengujian',
            'year' => 2026, 'status' => 'active', 'manager_id' => $mytha->id,
        ])->assertRedirect(route('decrees.index'));

        $this->assertDatabaseHas('official_decrees', [
            'number' => 'SK/DIKLAT/TEST/001', 'category' => 'diklat', 'manager_id' => $mytha->id,
        ]);
    }

    public function test_leadership_can_view_but_cannot_modify_decrees(): void
    {
        $director = User::where('email', 'direktur@lentera.test')->firstOrFail();
        $decree = OfficialDecree::firstOrFail();

        $this->actingAs($director)->get(route('decrees.index'))->assertOk();
        $this->get(route('decrees.create'))->assertForbidden();
        $this->put(route('decrees.update', $decree), [])->assertForbidden();
    }

    public function test_expected_organizational_accounts_and_draft_sk_are_seeded(): void
    {
        $this->assertDatabaseHas('users', ['email' => 'sunartititin5678@gmail.com', 'active' => true]);
        foreach (['mytha', 'desi', 'tika', 'rizal.marubob', 'handedi'] as $account) {
            $this->assertDatabaseHas('users', ['email' => $account.'@lentera.test', 'active' => true]);
        }

        foreach ([2024, 2025, 2026] as $year) {
            $this->assertDatabaseHas('official_decrees', ['category' => 'diklat', 'year' => $year, 'status' => 'draft']);
        }
    }
}
