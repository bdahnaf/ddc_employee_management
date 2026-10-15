<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The firm's structure, set up by HR:
 *   divisions ──< departments
 *   divisions ──< projects        (lead division)
 *   offices                       (Head Office, and any other office later)
 *   designations                  (job titles)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('code', 30)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 30)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['division_id', 'name']);
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150)->unique();
            $table->string('grade', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 30)->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_headquarters')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->string('code', 30)->nullable()->unique();
            $table->string('client', 200)->nullable();
            $table->string('location', 200)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('ongoing'); // planned | ongoing | completed
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('offices');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('divisions');
    }
};
