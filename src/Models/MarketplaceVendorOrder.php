<?php
namespace ItmoSoft\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{MorphTo, HasMany};
use Illuminate\Support\Facades\Config;
class MarketplaceVendorOrder extends Model
{
    protected $guarded = ['id'];
    protected $attributes = ['status' => 'pending'];
    protected function casts(): array { return ['subtotal' => 'decimal:4', 'metadata' => 'array']; }
    public function getTable(): string { return (string) Config::get('itmosoft-marketplace.table_prefix', 'itmosoft_').'marketplace_vendor_orders'; }
    public function sourceOrder(): MorphTo { return $this->morphTo('source_order'); }
    public function vendor(): MorphTo { return $this->morphTo('vendor'); }
    public function items(): HasMany { return $this->hasMany(MarketplaceVendorOrderItem::class, 'marketplace_vendor_order_id')->orderBy('id'); }
}
