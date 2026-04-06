<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spreadsheets', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->json('columns');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spreadsheets');
    }
};
