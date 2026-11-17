<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        $prefix = (string) config('itmosoft-marketplace.table_prefix', 'itmosoft_');
        $orders = $prefix.'marketplace_vendor_orders';
        $items = $prefix.'marketplace_vendor_order_items';
        if (! Schema::hasTable($orders)) Schema::create($orders, function (Blueprint $table): void {
            $table->id(); $table->morphs('source_order'); $table->morphs('vendor');
            $table->decimal('subtotal',16,4); $table->char('currency',3)->default('USD');
            $table->string('status',30)->default('pending')->index(); $table->json('metadata')->nullable(); $table->timestamps();
            $table->unique(['source_order_type','source_order_id','vendor_type','vendor_id'],'marketplace_source_vendor_unique');
        });
        if (! Schema::hasTable($items)) Schema::create($items, function (Blueprint $table) use ($orders): void {
            $table->id(); $table->unsignedBigInteger('marketplace_vendor_order_id'); $table->morphs('source_item');
            $table->unsignedInteger('quantity'); $table->decimal('unit_price',16,4); $table->decimal('line_total',16,4);
            $table->json('metadata')->nullable(); $table->timestamps();
            $table->foreign('marketplace_vendor_order_id')->references('id')->on($orders)->cascadeOnDelete();
            $table->index(['marketplace_vendor_order_id','source_item_type','source_item_id'],'marketplace_vendor_order_item_lookup');
        });
    }
    public function down(): void {
        $prefix = (string) config('itmosoft-marketplace.table_prefix', 'itmosoft_');
        Schema::dropIfExists($prefix.'marketplace_vendor_order_items');
        Schema::dropIfExists($prefix.'marketplace_vendor_orders');
    }
};
