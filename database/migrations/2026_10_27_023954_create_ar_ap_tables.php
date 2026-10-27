<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- AR: piutang customer ----
        Schema::create('ar_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('ar_number')->unique();
            $table->foreignId('sales_invoice_id')->nullable()
                  ->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', ['open', 'partial', 'paid', 'void'])->default('open');
            $table->foreignId('journal_id')->nullable()
                  ->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('due_date');
        });

        // ---- AP: hutang supplier ----
        Schema::create('ap_bills', function (Blueprint $table) {
            $table->id();
            $table->string('ap_number')->unique();
            $table->foreignId('goods_receipt_id')->nullable()
                  ->constrained('goods_receipts')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->date('bill_date');
            $table->date('due_date');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', ['open', 'partial', 'paid', 'void'])->default('open');
            $table->foreignId('journal_id')->nullable()
                  ->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_bills');
        Schema::dropIfExists('ar_invoices');
    }
};