<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            $table->text('description')->nullable()->after('code');
        });

        $descriptions = [
            'PKL' => 'Pengajuan praktik kerja lapangan untuk pelajar dan mahasiswa.',
            'MAG' => 'Program pemagangan terstruktur di lingkungan rumah sakit.',
            'KLINIK' => 'Rotasi dan kegiatan pendidikan klinis peserta didik.',
            'RSC' => 'Pengajuan penelitian dan telaah kelayakan etik.',
            'STP' => 'Perizinan studi awal untuk persiapan penelitian.',
            'DATA' => 'Akses pengambilan data sesuai ketentuan rumah sakit.',
            'OBS' => 'Kegiatan pengamatan layanan dan praktik profesional.',
            'SB' => 'Kunjungan pembelajaran antarinstansi atau organisasi.',
            'PLT' => 'Program peningkatan kompetensi dan keterampilan.',
        ];

        foreach ($descriptions as $code => $description) {
            DB::table('activity_types')->where('code', $code)->update(['description' => $description]);
        }
    }

    public function down(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
