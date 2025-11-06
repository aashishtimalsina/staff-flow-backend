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
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('job_role_id')->constrained('job_roles')->onDelete('cascade');
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);

            // Client Rates (what agency charges the client)
            $table->decimal('client_day_rate', 10, 2);
            $table->decimal('client_night_rate', 10, 2);
            $table->decimal('client_weekend_rate', 10, 2);
            $table->decimal('client_bank_holiday_rate', 10, 2);

            // Worker Rates (what agency pays the worker)
            $table->decimal('worker_day_rate', 10, 2);
            $table->decimal('worker_night_rate', 10, 2);
            $table->decimal('worker_weekend_rate', 10, 2);
            $table->decimal('worker_bank_holiday_rate', 10, 2);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('job_role_id');
            $table->index('effective_date');
            $table->index(['client_id', 'job_role_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_cards');
    }
};
