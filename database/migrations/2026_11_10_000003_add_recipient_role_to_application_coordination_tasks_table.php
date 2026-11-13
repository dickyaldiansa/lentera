<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('application_coordination_tasks', function (Blueprint $table) {
            $table->string('recipient_role_slug')->nullable()->after('recipient_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('application_coordination_tasks', function (Blueprint $table) {
            $table->dropIndex(['recipient_role_slug']);
            $table->dropColumn('recipient_role_slug');
        });
    }
};
