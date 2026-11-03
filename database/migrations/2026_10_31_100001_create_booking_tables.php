<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('extended_from_id')->nullable()->constrained('bookings')->restrictOnDelete();
            $table->string('period');
            $table->unsignedSmallInteger('duration');
            $table->string('payment_plan');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('total_price');
            $table->unsignedSmallInteger('guests');
            $table->date('check_in');
            $table->date('check_out');
            $table->string('status');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->unsignedInteger('terms_version');
            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['room_id', 'check_in', 'check_out']);
            $table->index('status');
            $table->index('user_id');
            $table->index('property_id');
            $table->index('room_type_id');
            $table->index('expires_at');
            $table->index('extended_from_id');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('late_fee')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->date('due_date');
            $table->date('grace_until');
            $table->timestamp('late_fee_applied_at')->nullable();
            $table->timestamp('upload_reset_at')->nullable();
            $table->boolean('allow_partial')->default(false);
            $table->string('status')->default('unpaid');
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index('grace_until');
            $table->index('booking_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('method');
            $table->string('status');
            $table->unsignedBigInteger('amount');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('proof_path')->nullable();
            $table->string('external_id')->nullable()->unique();
            $table->string('external_reference')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('initiated_by');
            $table->unsignedBigInteger('amount');
            $table->string('status');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('bookings');
    }
};
