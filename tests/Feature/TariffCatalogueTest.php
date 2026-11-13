<?php

namespace Tests\Feature;

use App\Models\{Application,Invoice,Tariff,User};
use App\Services\TariffCatalogue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TariffCatalogueTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void{parent::setUp();$this->seed(DatabaseSeeder::class);}
    private function admin():User{return User::where('email','admin@lentera.test')->firstOrFail();}
    private function applicant():User{return User::where('email','pemohon@lentera.test')->firstOrFail();}

    public function test_import_contains_45_unique_source_tariffs_and_preserves_units_and_group_limits():void
    {
        $this->assertSame(45,Tariff::whereNotNull('source_key')->count());
        $this->assertSame(45,Tariff::visible()->count());
        $expected=[
            'practice'=>[25000,125000,50000,150000,75000,200000,85000,250000],
            'internship'=>[200000,675000,275000,700000,350000,850000,400000,850000,500000,850000],
            'pre_research'=>[75000,100000,125000,150000,175000],
            'research'=>[150000,200000,225000,250000,275000,150000],
            'enrichment'=>[150000,200000,250000],
            'comparative_study'=>[1500000,1250000,1000000,750000,750000,500000,3500000,7500000,10000000,15000000,50000,65000,300000],
        ];
        foreach($expected as $category=>$amounts)$this->assertSame($amounts,Tariff::where('category',$category)->orderBy('sort_order')->pluck('amount')->map(fn($amount)=>(int)$amount)->all());
        $this->assertSame(4,Tariff::where('category','practice')->where('calculation','per_person_day')->count());
        $this->assertSame('per_sample',Tariff::where('source_key','research-special-sample')->firstOrFail()->calculation);
        $group=Tariff::where('source_key','comparative-group-16-20')->firstOrFail();
        $this->assertSame(16,$group->min_participants);$this->assertSame(20,$group->max_participants);
        $this->assertStringContainsString('1 snack + 1 makan + ruangan',$group->description);
        $this->assertSame(3,Tariff::where('calculation','unspecified')->count());
    }

    public function test_applicant_reads_only_published_active_tariffs_and_cannot_edit_master_data():void
    {
        $this->actingAs($this->applicant())->get(route('tariffs.index'))->assertOk()->assertSee('Daftar Tarif')->assertSee('Rp25.000')->assertSee('Per orang / minggu')->assertSee('Per sampel')->assertSee('Rp15.000.000')->assertDontSee('Biaya praktik per peserta');
        $this->get(route('applications.create'))->assertOk()->assertSee('data-activity-tariffs',false)->assertSee('Tarif Praktik / PKL')->assertSee('Rp25.000');
        $this->get(route('master.dataset','tariffs'))->assertForbidden();
        $this->post(route('master.store','tariffs'),[])->assertForbidden();
        $tariff=Tariff::where('source_key','practice-sma-day')->firstOrFail();
        $this->put(route('master.update',['tariffs',$tariff]),['amount'=>1])->assertForbidden();
        $this->get(route('master.edit',['tariffs',$tariff]))->assertForbidden();
    }

    public function test_admin_edits_are_reflected_in_both_views_without_changing_existing_invoice():void
    {
        $app=Application::whereDoesntHave('invoice')->firstOrFail();
        $invoice=Invoice::create(['application_id'=>$app->id,'number'=>'INV/TARIFF/OLD','subtotal'=>25000,'total'=>25000,'status'=>'unpaid']);
        $tariff=Tariff::where('source_key','practice-sma-day')->firstOrFail();
        $payload=['category'=>'practice','name'=>'Praktikum tarif terbaru','participant_category'=>'SMA / Sederajat','calculation'=>'per_person_day',
            'amount'=>30000,'description'=>'Keterangan tarif terbaru','sort_order'=>1,'active'=>1,'published'=>1];
        $this->actingAs($this->admin())->get(route('master.dataset','tariffs'))->assertOk()->assertSee('Edit')->assertSee('Nonaktifkan');
        $this->get(route('master.dataset','tariffs').'?page=2')->assertOk()->assertSee('Internal saja');
        $this->get(route('master.edit',['tariffs',$tariff]))->assertOk()->assertSee('Satuan tarif')->assertSee('Tampilkan kepada pemohon');
        $this->put(route('master.update',['tariffs',$tariff]),$payload)->assertSessionHas('success');
        $this->actingAs($this->applicant())->get(route('tariffs.index'))->assertOk()->assertSee('Praktikum tarif terbaru')->assertSee('Rp30.000')->assertSee('Keterangan tarif terbaru');
        $this->get(route('applications.create'))->assertOk()->assertSee('Praktikum tarif terbaru')->assertSee('Rp30.000');
        $this->assertSame(25000,(int)$invoice->fresh()->total);
        app(TariffCatalogue::class)->import();
        $this->assertSame(30000,(int)$tariff->fresh()->amount);
        $this->assertSame(45,Tariff::whereNotNull('source_key')->count());
        $this->actingAs($this->admin())->patch(route('master.toggle',['tariffs',$tariff]))->assertSessionHas('success');
        $this->actingAs($this->applicant())->get(route('tariffs.index'))->assertDontSee('Praktikum tarif terbaru');
        $this->get(route('applications.create'))->assertDontSee('Praktikum tarif terbaru');
    }

    public function test_admin_can_add_a_tariff_but_invalid_group_ranges_and_negative_prices_are_rejected():void
    {
        $payload=['category'=>'comparative_study','name'=>'Tarif kelompok uji','participant_category'=>'Kelompok uji','calculation'=>'per_group',
            'amount'=>1000000,'min_participants'=>51,'max_participants'=>60,'sort_order'=>100,'active'=>1,'published'=>1];
        $this->actingAs($this->admin())->post(route('master.store','tariffs'),$payload)->assertSessionHas('success');
        $this->assertDatabaseHas('tariffs',['name'=>'Tarif kelompok uji','amount'=>1000000]);
        $this->post(route('master.store','tariffs'),array_replace($payload,['amount'=>-1]))->assertSessionHasErrors('amount');
        $this->post(route('master.store','tariffs'),array_replace($payload,['max_participants'=>50]))->assertSessionHasErrors('max_participants');
        $this->post(route('master.store','tariffs'),array_replace($payload,['min_participants'=>1.5]))->assertSessionHasErrors('min_participants');
    }
}
