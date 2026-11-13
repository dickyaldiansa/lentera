<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $applicationIds = DB::table('invoices')
            ->where('status', 'waiting_verification')
            ->pluck('application_id');

        DB::table('applications')
            ->whereIn('id', $applicationIds)
            ->where('status', 'waiting_payment')
            ->update([
                'status' => 'waiting_payment_verification',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $applicationIds = DB::table('invoices')
            ->where('status', 'waiting_verification')
            ->pluck('application_id');

        DB::table('applications')
            ->whereIn('id', $applicationIds)
            ->where('status', 'waiting_payment_verification')
            ->update([
                'status' => 'waiting_payment',
                'updated_at' => now(),
            ]);
    }
};
