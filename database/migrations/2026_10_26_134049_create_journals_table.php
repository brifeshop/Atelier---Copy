<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Header Jurnal ----
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number')->unique();   // JE-YYYYMM-XXXX
            $table->date('journal_date');
            $table->string('reference_type')->nullable(); // morph: SalesInvoice, GoodsReceipt, Payment
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('description');
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['journal_date', 'status']);
        });

        // ---- Baris Jurnal (double-entry) ----
        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('memo')->nullable();
            $table->foreignId('customer_id')->nullable()
                  ->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()
                  ->constrained('suppliers')->nullOnDelete();
            $table->timestamps();

            $table->index('journal_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journals');
    }
};