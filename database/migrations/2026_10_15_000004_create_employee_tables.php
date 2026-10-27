<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * People and where they work.
 *
 *   employees ──< postings          (HQ or project; end_date NULL = current)
 *   employees ──< salary_structures (effective_to NULL = current)
 *   employees ─── users             (optional sign-in account)
 *
 * Columns marked "sensitive" are only shown to HR and to the person themselves
 * (see App\Models\Employee::SENSITIVE and docs/directory.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();

            // Visible to everyone signed in
            $table->string('employee_code', 30)->unique();
            $table->string('name', 150);
            $table->string('photo_path')->nullable();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('active');             // active | on_leave | resigned | retired
            $table->string('employment_type', 20)->default('permanent'); // permanent | contractual | project
            $table->date('joining_date')->nullable();
            $table->string('office_email', 190)->nullable();
            $table->string('office_phone', 40)->nullable();

            // Sensitive
            $table->string('personal_email', 190)->nullable();
            $table->string('personal_phone', 40)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('nid_no', 30)->nullable()->unique();
            $table->string('tin_no', 30)->nullable();
            $table->string('passport_no', 30)->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_relation', 60)->nullable();
            $table->string('emergency_contact_phone', 40)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_account_no', 60)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'name']);
        });

        Schema::create('postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('posting_type', 10);                  // hq | project
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_on_posting', 120)->nullable();  // e.g. Team Leader
            $table->date('start_date');
            $table->date('end_date')->nullable();                // NULL = current posting
            $table->string('order_ref', 60)->nullable();         // office order number
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'end_date']);
            $table->index(['project_id', 'end_date']);
            $table->index(['office_id', 'end_date']);
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('house_rent', 12, 2)->default(0);
            $table->decimal('medical', 12, 2)->default(0);
            $table->decimal('conveyance', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('gross', 12, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();            // NULL = current salary
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('postings');
        Schema::dropIfExists('employees');
    }
};
