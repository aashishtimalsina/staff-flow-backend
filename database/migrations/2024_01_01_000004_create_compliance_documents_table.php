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
        Schema::create('compliance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_role_id')->constrained('job_roles')->onDelete('cascade');
            $table->string('document_name');
            $table->boolean('is_required')->default(true);
            $table->boolean('requires_expiry')->default(false);
            $table->timestamps();

            $table->index('job_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_documents');
    }
};
