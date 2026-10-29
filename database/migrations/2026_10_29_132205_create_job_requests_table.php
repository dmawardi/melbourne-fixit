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
        Schema::create('job_requests', function (Blueprint $table) {
            $table->id();
            // Public identifier used in URLs so request IDs can't be enumerated.
            $table->ulid('public_id')->unique();

            // Null until the submitter logs in with a verified email matching contact_email.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();

            // Contact details captured on the public form.
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone', 32)->nullable();

            // Location. Full street address is collected once the user is logged in.
            $table->string('suburb');
            $table->string('postcode', 4);
            $table->string('address_line')->nullable();

            // Job details.
            $table->string('category');
            $table->string('title');
            $table->text('description');
            $table->string('urgency')->default('flexible');
            $table->string('budget_range')->nullable();
            $table->string('property_type')->nullable();

            $table->string('status')->default('submitted');

            // Abuse tracking for the public form.
            $table->string('submitted_ip', 45)->nullable();
            $table->text('submitted_user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['contact_email', 'user_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_requests');
    }
};
