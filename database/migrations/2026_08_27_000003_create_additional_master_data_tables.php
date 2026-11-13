<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('method_type');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('location_detail')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position');
            $table->string('nip')->nullable();
            $table->string('signature_path')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('payment_methods')->insert([
            ['name' => 'Transfer Bank', 'method_type' => 'bank_transfer', 'instructions' => 'Transfer ke rekening rumah sakit yang tercantum pada invoice.', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Virtual Account', 'method_type' => 'virtual_account', 'instructions' => 'Gunakan nomor virtual account yang tercantum pada invoice.', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('signatories');
        Schema::dropIfExists('activity_locations');
        Schema::dropIfExists('payment_methods');
    }
};
