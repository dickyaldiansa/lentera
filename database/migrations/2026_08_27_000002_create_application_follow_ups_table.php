<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $items = [
            ['administrative_verification', 'Verifikasi Administrasi'],
            ['revision_required', 'Perlu Perbaikan'],
            ['verified', 'Terverifikasi'],
            ['waiting_approval', 'Menunggu Persetujuan'],
            ['approved', 'Disetujui'],
            ['rejected', 'Ditolak'],
            ['waiting_payment', 'Menunggu Pembayaran'],
            ['payment_verified', 'Pembayaran Terverifikasi'],
            ['permit_issued', 'Surat Izin Terbit'],
            ['running', 'Sedang Berjalan'],
            ['completed', 'Selesai'],
            ['closed', 'Ditutup'],
        ];

        DB::table('application_follow_ups')->insert(array_map(
            fn (array $item, int $index) => ['code' => $item[0], 'label' => $item[1], 'sort_order' => ($index + 1) * 10, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            $items,
            array_keys($items),
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('application_follow_ups');
    }
};
