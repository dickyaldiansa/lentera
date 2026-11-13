<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
        });

        DB::table('institutions')->updateOrInsert(
            ['name' => 'RSUD Embung Fatimah Kota Batam'],
            [
                'logo_path' => 'images/branding/rsud-embung-fatimah.png',
                'type' => 'Rumah Sakit Umum Daerah Tipe B',
                'address' => 'Jalan Letjen R. Suprapto No. 9, Batu Aji, Kota Batam, Kepulauan Riau 29425',
                'phone' => '(0778) 364446',
                'cooperation_status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('settings')->where('key', 'hospital_name')->update([
            'value' => 'RSUD Embung Fatimah Kota Batam',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
