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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            // The job categories a specialist covers. Null for general vendors, who cover everything.
            $table->json('categories')->nullable();

            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('notes')->nullable();

            // Licences and insurance, recorded from the vendor's documents.
            $table->string('abn', 14)->nullable();
            $table->string('public_liability_insurer')->nullable();
            $table->string('public_liability_policy_number', 64)->nullable();
            $table->unsignedBigInteger('public_liability_cover')->nullable();
            $table->date('public_liability_expires_on')->nullable();
            $table->string('workcover_policy_number', 64)->nullable();
            $table->date('workcover_expires_on')->nullable();
            $table->string('builder_registration_number', 64)->nullable();
            $table->date('builder_registration_expires_on')->nullable();
            $table->string('plumbing_licence_number', 64)->nullable();
            $table->date('plumbing_licence_expires_on')->nullable();
            $table->string('electrical_registration_number', 64)->nullable();
            $table->date('electrical_registration_expires_on')->nullable();
            $table->date('documents_checked_on')->nullable();

            $table->timestamps();
            // Soft deleted so requests keep the vendor they were assigned.
            $table->softDeletes();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
