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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_role_id')->constrained('job_roles')->onDelete('restrict');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->date('dob')->nullable();
            $table->string('ni_number')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postcode')->nullable();
            $table->json('skills')->nullable();
            $table->json('locations')->nullable();
            $table->json('availability')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'On Leave', 'Terminated'])->default('Active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('job_role_id');
            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
