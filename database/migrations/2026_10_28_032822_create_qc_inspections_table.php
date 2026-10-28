<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Header QC
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('qc_number')->unique(); // Format: QC-YYYYMM-XXXX
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->date('inspection_date');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->string('inspection_method')->nullable(); // 100% check, sampling, dll
            $table->integer('sample_size')->nullable();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabel Detail Item QC
        Schema::create('qc_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_inspection_id')->constrained('qc_inspections')->cascadeOnDelete();
            $table->foreignId('goods_receipt_item_id')->constrained('goods_receipt_items')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            $table->decimal('qty_received', 15, 2);
            $table->decimal('qty_ok', 15, 2)->default(0);
            $table->decimal('qty_reject', 15, 2)->default(0);
            $table->string('reject_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspection_items');
        Schema::dropIfExists('qc_inspections');
    }
};