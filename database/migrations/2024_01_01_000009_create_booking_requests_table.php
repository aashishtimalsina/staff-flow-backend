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
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('job_role_id')->constrained('job_roles')->onDelete('restrict');
            $table->string('location');
            $table->text('description')->nullable();
            $table->dateTime('shift_start_time');
            $table->dateTime('shift_end_time');
            $table->integer('candidates_needed')->default(1);
            $table->decimal('client_rate', 10, 2);
            $table->decimal('worker_rate', 10, 2);
            $table->enum('status', ['Open', 'Partially Filled', 'Filled', 'Cancelled'])->default('Open');
            $table->text('special_requirements')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('client_id');
            $table->index('job_role_id');
            $table->index('status');
            $table->index(['shift_start_time', 'shift_end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
