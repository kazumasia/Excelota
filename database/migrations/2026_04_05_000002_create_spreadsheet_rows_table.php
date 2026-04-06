<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spreadsheet_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spreadsheet_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->json('data');
            $table->text('search_blob')->nullable();
            $table->timestamps();

            $table->index(['spreadsheet_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_rows');
    }
};
