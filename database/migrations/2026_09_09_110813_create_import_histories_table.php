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
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();

            $table->string('operation'); // import / export
            $table->string('file_name')->nullable();

            $table->integer('total_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('duplicate_records')->default(0);
            $table->integer('invalid_records')->default(0);

            $table->string('status')->default('success'); // success / partial / failed

            $table->text('details')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_histories');
    }
};