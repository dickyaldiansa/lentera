<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up():void
    {
        Schema::table('tariffs',function(Blueprint $table){
            $table->string('category')->default('other')->index();
            $table->string('participant_category')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('min_participants')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('published')->default(false);
            $table->string('source_key')->nullable()->unique();
        });
        app(\App\Services\TariffCatalogue::class)->import();
    }
    public function down():void
    {
        Schema::table('tariffs',function(Blueprint $table){
            $table->dropUnique(['source_key']);
            $table->dropIndex(['category']);
            $table->dropColumn(['category','participant_category','description','min_participants','max_participants','sort_order','published','source_key']);
        });
    }
};
