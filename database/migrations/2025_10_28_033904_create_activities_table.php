<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id_activity');
            $table->string('type'); // created, updated, deleted
            $table->string('model_type'); // App\Models\KaryawanModel, etc.
            $table->unsignedBigInteger('id_model');
            $table->text('description');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->unsignedBigInteger('id_cabang')->nullable();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'id_model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
