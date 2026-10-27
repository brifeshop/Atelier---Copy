<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Header Payment ----
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();     // PAY-YYYYMM-XXXX
            $table->enum('direction', ['in', 'out']);       // in = terima dari customer, out = bayar supplier
            $table->date('payment_date');
            $table->enum('method', ['cash', 'bank_transfer', 'cheque', 'giro', 'other'])->default('cash');
            $table->foreignId('cash_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->string('reference_number')->nullable(); // no. transfer / cek
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['direction', 'status']);
            $table->index('payment_date');
        });

        // ---- Alokasi Payment ke AR/AP ----
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->enum('allocatable_type', ['ar', 'ap']);
            $table->unsignedBigInteger('allocatable_id');   // ar_invoices.id / ap_bills.id
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index(['allocatable_type', 'allocatable_id'], 'pay_alloc_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};