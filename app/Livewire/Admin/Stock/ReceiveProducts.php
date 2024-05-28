<?php

namespace App\Livewire\Admin\Stock;

use BranchManagement\Models\Branch;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\WithPagination;
use ProductManagement\Models\Product;
use StockManagementContracts\IStockManagementService;

#[Title('ReceiveProducts')]

class ReceiveProducts extends Component
{
    use WithPagination;

    #[Url(nullable:true)]
    public $search;

    public array $selected = [];
    public array $quantities = [];

    #[Validate('required')]
    public ?string $branch;

    public function mount()
    {
        $this->branch = auth()->user()->branch_id;
    }

    public function select($productId){
        if(in_array($productId, $this->selected)) return;

        $this->selected[] = $productId;
    }

    public function remove($productId){
        if(!in_array($productId, $this->selected)) return;

        if (($key = array_search($productId, $this->selected)) !== false) {
            unset($this->selected[$key]);
        }
    }

    public function submit(IStockManagementService $stockManagementService)
    {
        $this->validate();

        foreach($this->quantities as $key => $value){
            $stockManagementService->receive($key, $value, $this->branch, auth()->user()->id);
        }

        session()->flash('success', 'Products received');
    }

    private function getSelectedProducts()
    {
        $branchId = auth()->user()->branch_id;
        $query = DB::table('products')
            ->join('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->leftJoin('stocks', function ($join) use ($branchId) {
                $join->on('products.id', '=', 'stocks.product_id')
                    ->where('stocks.branch_id', '=', $branchId);
            })->select('products.*', 'suppliers.code', DB::raw('COALESCE(stocks.available, 0) as quantity'))
            ->whereIn('products.id', $this->selected)
            ->whereNull('products.deleted_at');

        return $query->get();
    }

    private function getProducts()
    {
        $branchId = auth()->user()->branch_id;
        $query = DB::table('products')
            ->join('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->leftJoin('stocks', function ($join) use ($branchId) {
                $join->on('products.id', '=', 'stocks.product_id')
                    ->where('stocks.branch_id', '=', $branchId);
            })->where(function($q){
                $q->where('model', 'LIKE', '%'.$this->search.'%')
                    ->orWhere('sku_number', 'LIKE', '%'.$this->search.'%')
                    ->orWhere('description', 'LIKE', '%'.$this->search.'%');
            })->select('products.*', 'suppliers.code', DB::raw('COALESCE(stocks.available, 0) as quantity'))
            ->whereNotIn('products.id', $this->selected)
            ->orderByDesc('quantity');

        return $query->paginate(10);
    }

    #[Layout('livewire.admin.base_layout')]
    public function render()
    {
        return view('livewire.admin.stock.receive-products', [
            'products' => $this->getProducts(),
            'selectedProducts' => $this->getSelectedProducts(),
            'branches' => Branch::all()
        ]);
    }
}
