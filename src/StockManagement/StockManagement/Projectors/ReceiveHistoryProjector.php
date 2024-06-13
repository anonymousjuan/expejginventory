<?php

namespace StockManagement\Projectors;

use Illuminate\Support\Facades\DB;
use Spatie\EventSourcing\Enums\MetaData;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use StockManagementContracts\Events\DamagedProductReceived;
use StockManagementContracts\Events\ProductReceived;
use StockManagementContracts\Events\ProductReserved;
use StockManagementContracts\Events\ProductSetAsDamaged;
use StockManagementContracts\Events\ReservationCancelled;

class ReceiveHistoryProjector extends Projector
{
    public function onProductReceived(ProductReceived $event): void
    {
        $latest = $this->getLastQuantities($event->productId, $event->branchId);

        DB::table('stock_history')
            ->insert([
                'product_id' => $event->productId,
                'branch_id' => $event->branchId,
                'quantity' => $event->quantity,
                'user_id' => $event->actor,
                'action' => 'Received',
                'running_available' => $latest['available'] + $event->quantity,
                'running_reserved' => $latest['reserved'],
                'running_damaged' => $latest['damaged'],
                'version' => $event->aggregateRootVersion() ?? 0,
                'date' => date('Y-m-d H:i:s', strtotime($event->metaData()[MetaData::CREATED_AT]))
            ]);
    }

    public function onProductReserved(ProductReserved $event): void
    {
        $latest = $this->getLastQuantities($event->productId, $event->branchId);

        DB::table('stock_history')
            ->insert([
                'product_id' => $event->productId,
                'branch_id' => $event->branchId,
                'quantity' => $event->quantity,
                'user_id' => $event->actor,
                'action' => 'Reserved',
                'running_available' => $latest['available'] - $event->quantity,
                'running_reserved' => $latest['reserved'] + $event->quantity,
                'running_damaged' => $latest['damaged'],
                'version' => $event->aggregateRootVersion() ?? 0,
                'date' => date('Y-m-d H:i:s', strtotime($event->metaData()[MetaData::CREATED_AT]))
            ]);
    }

    public function onReservationCancelled(ReservationCancelled $event): void
    {
        $latest = $this->getLastQuantities($event->productId, $event->branchId);

        DB::table('stock_history')
            ->insert([
                'product_id' => $event->productId,
                'branch_id' => $event->branchId,
                'quantity' => $event->quantity,
                'user_id' => $event->actor,
                'action' => 'Reservation Cancelled',
                'running_available' => $latest['available'] + $event->quantity,
                'running_reserved' => $latest['reserved'] - $event->quantity,
                'running_damaged' => $latest['damaged'],
                'version' => $event->aggregateRootVersion() ?? 0,
                'date' => date('Y-m-d H:i:s', strtotime($event->metaData()[MetaData::CREATED_AT]))
            ]);
    }

    public function onDamagedProductReceived(DamagedProductReceived $event): void
    {
        $latest = $this->getLastQuantities($event->productId, $event->branchId);

        DB::table('stock_history')
            ->insert([
                'product_id' => $event->productId,
                'branch_id' => $event->branchId,
                'quantity' => $event->quantity,
                'user_id' => $event->actor,
                'action' => 'Damaged Received',
                'running_available' => $latest['available'],
                'running_reserved' => $latest['reserved'],
                'running_damaged' => $latest['damaged'] + $event->quantity,
                'version' => $event->aggregateRootVersion() ?? 0,
                'date' => date('Y-m-d H:i:s', strtotime($event->metaData()[MetaData::CREATED_AT]))
            ]);
    }

    public function onProductSetAsDamaged(ProductSetAsDamaged $event): void
    {
        $latest = $this->getLastQuantities($event->productId, $event->branchId);

        DB::table('stock_history')
            ->insert([
                'product_id' => $event->productId,
                'branch_id' => $event->branchId,
                'quantity' => $event->quantity,
                'user_id' => $event->actor,
                'action' => 'Set as Damaged ',
                'running_available' => $latest['available'] - $event->quantity,
                'running_reserved' => $latest['reserved'],
                'running_damaged' => $latest['damaged'] + $event->quantity,
                'version' => $event->aggregateRootVersion() ?? 0,
                'date' => date('Y-m-d H:i:s', strtotime($event->metaData()[MetaData::CREATED_AT]))
            ]);
    }

    /**
     * @param string $productId
     * @param string $branchId
     * @return array{'available': int, 'reserved': int, 'damaged': int}
     */
    private function getLastQuantities(string $productId, string $branchId): array
    {
        $latest = DB::table('stock_history')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->orderByDesc('date')
            ->orderByDesc('version')
            ->first();

        return [
            'available' => $latest?->running_available ?? 0,
            'reserved' => $latest?->running_reserved ?? 0,
            'damaged' => $latest?->running_damaged ?? 0
        ];
    }
}
