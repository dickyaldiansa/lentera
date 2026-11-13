<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->text('review_note')->nullable();
        });
        app(\App\Services\InternshipPaymentWorkflow::class)->publish();
    }

    public function down(): void
    {
        // Published versions are retained because running applications may reference them.
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('review_note'));
    }
};
