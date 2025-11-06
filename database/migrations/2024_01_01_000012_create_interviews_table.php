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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('job_role_id')->constrained('job_roles')->onDelete('restrict');
            $table->dateTime('interview_time');
            $table->string('location')->nullable();
            $table->string('video_link')->nullable();
            $table->enum('status', ['Scheduled', 'Completed', 'Cancelled', 'Rescheduled'])->default('Scheduled');
            $table->text('notes')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('scheduled_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('candidate_id');
            $table->index('client_id');
            $table->index('interview_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
