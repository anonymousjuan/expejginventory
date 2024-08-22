<?php

namespace App\Livewire\Admin\Delivery;

use Delivery\Models\Delivery\Enums\DeliveryStatus;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Out for Delivery')]
class OutForDelivery extends Component
{
    /**
     * @return Collection<int, object>
     */
    public function getDeliveries(): Collection
    {
        return DB::table('deliveries')
            ->join('branches', 'deliveries.branch_id', '=', 'branches.id')
            ->join('users', 'deliveries.driver', '=', 'users.id')
            ->select(['deliveries.*', 'branches.name as branch_name', 'users.first_name as driver_first_name', 'users.last_name as driver_last_name'])
            ->where('status', DeliveryStatus::OUT_FOR_DELIVERY->value)
            ->get();
    }

    #[Layout('livewire.admin.base_layout')]
    public function render(): Factory|Application|View|\Illuminate\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('livewire.admin.delivery.out-for-delivery', [
            'deliveries' => $this->getDeliveries()
        ]);
    }
}
