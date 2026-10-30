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
        Schema::create('job_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('kind')->default('photo');
            // Files are hidden from the customer unless explicitly shared.
            $table->string('visibility')->default('internal');

            $table->string('disk')->default('s3');
            $table->string('path')->unique();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Null while a presigned direct-to-S3 upload is pending; set once the object is confirmed.
            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();

            $table->index(['job_request_id', 'sort_order']);
            $table->index(['job_request_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_request_files');
    }
};
