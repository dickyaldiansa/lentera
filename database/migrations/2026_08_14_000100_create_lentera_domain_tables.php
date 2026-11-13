<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->foreignId('institution_id')->nullable();
            $table->boolean('active')->default(true);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique();
            $table->string('scope')->default('internal'); $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id','user_id']);
        });
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id','role_id']);
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('type')->nullable();
            $table->string('faculty')->nullable(); $table->string('study_program')->nullable();
            $table->text('address')->nullable(); $table->string('pic_name')->nullable();
            $table->string('email')->nullable(); $table->string('phone')->nullable();
            $table->string('cooperation_status')->default('active'); $table->string('mou_number')->nullable();
            $table->date('mou_start')->nullable(); $table->date('mou_end')->nullable(); $table->timestamps();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('code')->unique();
            $table->string('type')->default('unit'); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('education_programs', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('category');
            $table->unsignedInteger('duration_months')->nullable(); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('code')->unique();
            $table->string('color')->default('#2563eb'); $table->boolean('is_paid')->default(false);
            $table->boolean('requires_ethics')->default(false); $table->boolean('requires_kamkordik')->default(false);
            $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('requirements', function (Blueprint $table) {
            $table->id(); $table->foreignId('activity_type_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->text('description')->nullable(); $table->boolean('required')->default(true);
            $table->json('allowed_types')->nullable(); $table->unsignedInteger('max_size_kb')->default(5120); $table->timestamps();
        });
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id(); $table->foreignId('activity_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); $table->string('calculation')->default('flat');
            $table->decimal('amount',15,2); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id(); $table->string('module'); $table->foreignId('activity_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); $table->json('steps'); $table->boolean('active')->default(true); $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('applicant_id')->constrained('users');
            $table->foreignId('activity_type_id')->constrained(); $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete(); $table->string('title');
            $table->string('person_in_charge')->nullable(); $table->unsignedInteger('participant_count')->default(1);
            $table->date('start_date'); $table->date('end_date'); $table->text('purpose')->nullable();
            $table->text('description')->nullable(); $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('application_participants', function (Blueprint $table) {
            $table->id(); $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('identity_number')->nullable(); $table->string('email')->nullable();
            $table->string('phone')->nullable(); $table->timestamps();
        });
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id(); $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained()->nullOnDelete(); $table->string('name');
            $table->string('path'); $table->string('mime')->nullable(); $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('version')->default(1); $table->string('status')->default('uploaded');
            $table->text('note')->nullable(); $table->foreignId('uploaded_by')->constrained('users'); $table->timestamps();
        });
        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id(); $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable(); $table->string('to_status'); $table->text('note')->nullable();
            $table->foreignId('changed_by')->constrained('users'); $table->timestamps();
        });
        Schema::create('application_dispositions', function (Blueprint $table) {
            $table->id(); $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('destination'); $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->text('instruction')->nullable(); $table->string('status')->default('sent');
            $table->foreignId('created_by')->constrained('users'); $table->timestamp('completed_at')->nullable(); $table->timestamps();
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->id(); $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title'); $table->dateTime('start_at'); $table->dateTime('end_at');
            $table->string('location')->nullable(); $table->string('status')->default('scheduled'); $table->text('monitoring_note')->nullable(); $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->decimal('subtotal',15,2); $table->decimal('tax',15,2)->default(0); $table->decimal('total',15,2);
            $table->date('due_date')->nullable(); $table->string('status')->default('unpaid'); $table->json('items')->nullable(); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('invoice_id')->constrained()->cascadeOnDelete(); $table->decimal('amount',15,2);
            $table->string('method')->nullable(); $table->string('reference')->nullable(); $table->string('proof_path')->nullable();
            $table->string('status')->default('waiting_verification'); $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable(); $table->timestamp('verified_at')->nullable(); $table->timestamps();
        });

        Schema::create('education_batches', function (Blueprint $table) {
            $table->id(); $table->foreignId('education_program_id')->constrained(); $table->foreignId('institution_id')->constrained();
            $table->string('name'); $table->date('start_date'); $table->date('end_date'); $table->string('status')->default('active'); $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('institution_id')->constrained(); $table->foreignId('education_program_id')->constrained();
            $table->foreignId('education_batch_id')->nullable()->constrained()->nullOnDelete(); $table->string('nim')->nullable();
            $table->string('name'); $table->string('gender',1)->nullable(); $table->string('email')->nullable(); $table->string('phone')->nullable();
            $table->date('period_start'); $table->date('period_end'); $table->string('status')->default('active'); $table->timestamps();
        });
        Schema::create('clinical_supervisors', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained(); $table->string('name'); $table->string('professional_number')->nullable();
            $table->string('specialization')->nullable(); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('clinical_rotations', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained(); $table->foreignId('clinical_supervisor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage'); $table->date('start_date'); $table->date('end_date'); $table->string('status')->default('scheduled'); $table->timestamps();
        });
        Schema::create('logbooks', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_rotation_id')->constrained()->cascadeOnDelete(); $table->foreignId('clinical_supervisor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('activity_date'); $table->string('room')->nullable(); $table->string('activity_type');
            $table->string('anonymous_case_number')->nullable(); $table->string('age_group')->nullable(); $table->string('patient_gender',1)->nullable();
            $table->string('diagnosis')->nullable(); $table->text('procedure')->nullable(); $table->string('student_role')->nullable();
            $table->text('note')->nullable(); $table->string('status')->default('draft'); $table->text('supervisor_note')->nullable();
            $table->timestamp('submitted_at')->nullable(); $table->timestamp('verified_at')->nullable(); $table->timestamps();
        });
        Schema::create('assessments', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_rotation_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('assessor_id')->constrained('users');
            $table->json('scores'); $table->decimal('final_score',5,2); $table->string('grade'); $table->text('note')->nullable(); $table->timestamps();
        });

        Schema::create('ethics_applications', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('applicant_id')->constrained('users'); $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title'); $table->string('principal_investigator'); $table->json('members')->nullable();
            $table->string('research_type')->nullable(); $table->string('location')->nullable(); $table->string('population')->nullable();
            $table->string('subjects')->nullable(); $table->date('start_date')->nullable(); $table->date('end_date')->nullable();
            $table->string('funding_source')->nullable(); $table->string('status')->default('draft'); $table->timestamp('submitted_at')->nullable(); $table->timestamps();
        });
        Schema::create('ethics_documents', function (Blueprint $table) {
            $table->id(); $table->foreignId('ethics_application_id')->constrained()->cascadeOnDelete();
            $table->string('type'); $table->string('name'); $table->string('path'); $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('uploaded'); $table->foreignId('uploaded_by')->constrained('users'); $table->timestamps();
        });
        Schema::create('ethics_reviews', function (Blueprint $table) {
            $table->id(); $table->foreignId('ethics_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users'); $table->date('assigned_at'); $table->date('deadline');
            $table->string('status')->default('assigned'); $table->json('scores')->nullable(); $table->json('section_notes')->nullable();
            $table->string('recommendation')->nullable(); $table->text('summary')->nullable(); $table->timestamp('completed_at')->nullable(); $table->timestamps();
        });
        Schema::create('ethics_revisions', function (Blueprint $table) {
            $table->id(); $table->foreignId('ethics_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version'); $table->text('request_note'); $table->text('response_note')->nullable();
            $table->string('status')->default('requested'); $table->timestamp('submitted_at')->nullable(); $table->timestamps();
        });
        Schema::create('ethics_meetings', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->dateTime('scheduled_at'); $table->string('location')->nullable();
            $table->text('agenda')->nullable(); $table->json('members')->nullable(); $table->string('status')->default('scheduled'); $table->text('minutes')->nullable(); $table->timestamps();
        });
        Schema::create('ethics_decisions', function (Blueprint $table) {
            $table->id(); $table->foreignId('ethics_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ethics_meeting_id')->nullable()->constrained()->nullOnDelete(); $table->string('decision');
            $table->text('note')->nullable(); $table->foreignId('decided_by')->constrained('users'); $table->timestamp('decided_at'); $table->timestamps();
        });
        Schema::create('ethical_clearances', function (Blueprint $table) {
            $table->id(); $table->foreignId('ethics_application_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique(); $table->date('issued_at'); $table->date('valid_until'); $table->string('chairperson');
            $table->string('verification_token')->unique(); $table->string('status')->default('active'); $table->string('document_path')->nullable(); $table->timestamps();
        });
        Schema::create('research_monitoring', function (Blueprint $table) {
            $table->id(); $table->foreignId('ethics_application_id')->constrained()->cascadeOnDelete();
            $table->string('type'); $table->date('report_date'); $table->text('summary'); $table->string('document_path')->nullable();
            $table->string('status')->default('submitted'); $table->timestamps();
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('type'); $table->longText('content'); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('letters', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->string('type'); $table->nullableMorphs('letterable');
            $table->foreignId('template_id')->nullable()->constrained('document_templates')->nullOnDelete(); $table->string('subject');
            $table->longText('content'); $table->date('issued_at'); $table->string('verification_token')->unique();
            $table->string('status')->default('issued'); $table->string('document_path')->nullable(); $table->timestamps();
        });
        Schema::create('document_numbers', function (Blueprint $table) {
            $table->id(); $table->string('type')->unique(); $table->string('format'); $table->unsignedBigInteger('last_sequence')->default(0); $table->unsignedInteger('year'); $table->timestamps();
        });
        Schema::create('announcements', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->text('content'); $table->string('audience')->default('all');
            $table->dateTime('published_at'); $table->dateTime('expires_at')->nullable(); $table->foreignId('created_by')->constrained('users'); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('ip_address')->nullable();
            $table->string('module'); $table->string('action'); $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); $table->string('group')->default('general'); $table->string('key')->unique(); $table->text('value')->nullable(); $table->string('type')->default('string'); $table->boolean('public')->default(false); $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings','audit_logs','announcements','document_numbers','letters','document_templates','research_monitoring','ethical_clearances','ethics_decisions','ethics_meetings','ethics_revisions','ethics_reviews','ethics_documents','ethics_applications','assessments','logbooks','clinical_rotations','clinical_supervisors','students','education_batches','payments','invoices','activities','application_dispositions','application_status_histories','application_documents','application_participants','applications','workflow_definitions','tariffs','requirements','activity_types','education_programs','departments','institutions','permission_role','role_user','permissions','roles'] as $table) Schema::dropIfExists($table);
    }
};
