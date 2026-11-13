<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('application_follow_ups')->updateOrInsert(
            ['code' => 'waiting_payment_verification'],
            [
                'label' => 'Menunggu Verifikasi Pembayaran',
                'sort_order' => 75,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('application_follow_ups')->where('code', 'waiting_payment_verification')->delete();
    }
};
