<?php
namespace ItmoSoft\Marketplace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ItmoSoft\Marketplace\Models\MarketplaceVendorOrder;
class MarketplaceManager
{
    /**
     * Each allocation: vendor (Model), subtotal, optional currency, items.
     * Each item: source_item (Model), quantity, unit_price, optional metadata.
     */
    public function recordOrder(Model $sourceOrder, array $allocations)
    {
        if ($allocations === []) throw new InvalidArgumentException('At least one vendor allocation is required.');
        return DB::transaction(function () use ($sourceOrder, $allocations) {
            $result = collect();
            foreach ($allocations as $allocation) {
                $vendor = $allocation['vendor'] ?? null;
                if (! $vendor instanceof Model) throw new InvalidArgumentException('Every allocation must include a vendor Eloquent model.');
                $currency = strtoupper((string) ($allocation['currency'] ?? 'USD'));
                if (! preg_match('/^[A-Z]{3}$/', $currency)) throw new InvalidArgumentException('Currency must be a three-letter code.');
                $items = $allocation['items'] ?? [];
                if ($items === []) throw new InvalidArgumentException('Every vendor allocation must include at least one item.');
                $subtotal = $this->decimal($allocation['subtotal'] ?? null, 'subtotal');
                $order = MarketplaceVendorOrder::query()->firstOrNew([
                    'source_order_type'=>$sourceOrder->getMorphClass(), 'source_order_id'=>$sourceOrder->getKey(),
                    'vendor_type'=>$vendor->getMorphClass(), 'vendor_id'=>$vendor->getKey(),
                ]);
                $order->forceFill(['subtotal'=>$subtotal,'currency'=>$currency,'metadata'=>$allocation['metadata'] ?? null])->save();
                $order->items()->delete();
                foreach ($items as $item) {
                    $sourceItem = $item['source_item'] ?? null;
                    if (! $sourceItem instanceof Model) throw new InvalidArgumentException('Every allocation item must include a source_item model.');
                    $quantity = (int) ($item['quantity'] ?? 0);
                    if ($quantity < 1) throw new InvalidArgumentException('Allocated item quantity must be at least 1.');
                    $unitPrice = $this->decimal($item['unit_price'] ?? null, 'unit_price');
                    $order->items()->create([
                        'source_item_type'=>$sourceItem->getMorphClass(), 'source_item_id'=>$sourceItem->getKey(),
                        'quantity'=>$quantity, 'unit_price'=>$unitPrice,
                        'line_total'=>$this->formatUnits($this->decimalUnits($unitPrice) * $quantity),
                        'metadata'=>$item['metadata'] ?? null,
                    ]);
                }
                $result->push($order->fresh(['items']));
            }
            return $result;
        });
    }
    public function findVendorOrder(int|string $id): MarketplaceVendorOrder
    {
        $order = MarketplaceVendorOrder::query()->with('items')->find($id);
        if (! $order) throw new InvalidArgumentException('Marketplace vendor order not found.');
        return $order;
    }
    public function updateStatus(MarketplaceVendorOrder|int|string $vendorOrder, string $status): MarketplaceVendorOrder
    {
        $order = $vendorOrder instanceof MarketplaceVendorOrder ? $vendorOrder : $this->findVendorOrder($vendorOrder);
        $allowed = (array) config('itmosoft-marketplace.statuses', ['pending','accepted','processing','completed','cancelled']);
        if (! in_array($status, $allowed, true)) throw new InvalidArgumentException('Status is not configured.');
        $transitions = ['pending'=>['accepted','cancelled'],'accepted'=>['processing','cancelled'],'processing'=>['completed','cancelled'],'completed'=>[],'cancelled'=>[]];
        if (! in_array($status, $transitions[(string)$order->status] ?? [], true)) throw new InvalidArgumentException('Invalid vendor order status transition.');
        $order->forceFill(['status'=>$status])->save();
        return $order->refresh();
    }
    public function vendorOrdersFor(Model $sourceOrder)
    {
        return MarketplaceVendorOrder::with('items')->where('source_order_type',$sourceOrder->getMorphClass())->where('source_order_id',$sourceOrder->getKey())->get();
    }
    public function ordersForVendor(Model $vendor)
    {
        return MarketplaceVendorOrder::with('items')->where('vendor_type',$vendor->getMorphClass())->where('vendor_id',$vendor->getKey())->latest('id')->get();
    }
    private function decimalUnits(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 10000) + (int) str_pad(substr($fraction, 0, 4), 4, '0');
    }

    private function formatUnits(int $units): string
    {
        return intdiv($units, 10000).'.'.str_pad((string) ($units % 10000), 4, '0', STR_PAD_LEFT);
    }

    private function decimal(mixed $value, string $field): string
    {
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            throw new InvalidArgumentException("The {$field} must be a non-negative decimal value.");
        }

        $raw = trim((string) $value);
        if (! preg_match('/^\\+?\\d+(?:\\.\\d{1,4})?$/', $raw)) {
            throw new InvalidArgumentException("The {$field} must be a non-negative decimal with up to four fractional digits.");
        }

        return $this->formatUnits($this->decimalUnits($raw));
    }
}
