<?php
namespace ItmoSoft\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo};
use Illuminate\Support\Facades\Config;
class MarketplaceVendorOrderItem extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['quantity'=>'integer','unit_price'=>'decimal:4','line_total'=>'decimal:4','metadata'=>'array']; }
    public function getTable(): string { return (string) Config::get('itmosoft-marketplace.table_prefix', 'itmosoft_').'marketplace_vendor_order_items'; }
    public function vendorOrder(): BelongsTo { return $this->belongsTo(MarketplaceVendorOrder::class, 'marketplace_vendor_order_id'); }
    public function sourceItem(): MorphTo { return $this->morphTo('source_item'); }
}
