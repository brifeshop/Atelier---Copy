<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('purchase_order_items', 'purchase_requisition_item_id')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->foreignId('purchase_requisition_item_id')
                    ->nullable()
                    ->after('purchase_order_id')
                    ->constrained('purchase_requisition_items')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_order_items', 'purchase_requisition_item_id')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropForeign(['purchase_requisition_item_id']);
                $table->dropColumn('purchase_requisition_item_id');
            });
        }
    }
};