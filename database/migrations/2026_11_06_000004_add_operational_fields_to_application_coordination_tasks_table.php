<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('application_coordination_tasks', function (Blueprint $table) {
            $table->string('institution_contact')->nullable()->after('note');
            $table->string('institution_contact_phone')->nullable()->after('institution_contact');
            $table->timestamp('scheduled_at')->nullable()->after('institution_contact_phone');
            $table->string('location_or_meeting_link')->nullable()->after('scheduled_at');
            $table->string('hospital_pic')->nullable()->after('location_or_meeting_link');
            $table->text('applicant_instruction')->nullable()->after('hospital_pic');
            $table->boolean('notify_applicant')->default(true)->after('applicant_instruction');
            $table->timestamp('handover_completed_at')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('application_coordination_tasks', function (Blueprint $table) {
            $table->dropColumn(['institution_contact','institution_contact_phone','scheduled_at','location_or_meeting_link','hospital_pic','applicant_instruction','notify_applicant','handover_completed_at']);
        });
    }
};
