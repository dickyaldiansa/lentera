<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_regulations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('number');
            $table->unsignedSmallInteger('year');
            $table->date('effective_from')->nullable();
            // TRUE is the current document; NULL retains older documents as archives.
            $table->boolean('is_current')->nullable()->unique();
            $table->string('file_path');
            $table->string('original_filename');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_regulations');
    }
};
