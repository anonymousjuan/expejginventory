<?php

namespace App\Livewire\Admin\Order;

use Akaunting\Money\Money;
use App\Exceptions\ErrorHandler;
use DateTime;
use DeliveryContracts\IDeliveryService;
use DeliveryContracts\Utils\Result;
use IdentityAndAccessContracts\IIdentityAndAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use OrderContracts\IOrderService;
use PaymentContracts\IPaymentService;
use ProductManagement\Models\Product;
use StockManagementContracts\IStockManagementService;
use Str;

#[Title('Order Details')]
class OrderDetails extends Component
{
    use WithPagination;

    public $orderId;
    public $customer;

    #[Url(nullable: true)]
    public $search;
    public $branch;
    public $prices = [];
    public $quantities = [];

    /**
     * @var array<string, float>
     */
    public array $discounts = [];

    public $email = '';
    public $password = '';

    public $paymentMethods = ['Cash'];
    public $referenceNumbers = [''];
    public $amounts = [null];
    public $receiptNumber = '';
    public $credit = [false];

    public $completed = false;
    public string $paymentType = 'full';
    public int $months = 5;
    public $rate = 0;

    public $paymentMethodsCod = ['Cash'];
    public $referenceNumbersCod = [''];
    public $amountsCod = [null];
    public $receiptNumberCod = '';

    public $completedCod = false;
    public string $orderType = '';

    public string $deliveryType = 'pickup';
    #[Validate('required_if:deliveryType,deliver')]
    public float $deliveryFee = 0.0;
    public ?string $deliveryAddress = null;
    public bool $sameAddress = true;


    /**
     * @var array<string, string>
     */
    public array $priceType = [];

    #[Validate('required_if:paymentType,installment')]
    public string $installmentStartDate = '';
    public function mount(string $order_id): void
    {
        $this->orderId = $order_id;

        $order = $this->getOrder();
        $this->completed = $order->status;
        $this->completedCod = $order->status == 2;
        $this->branch = $order->branch_id;
        $this->receiptNumber = $order->receipt_number;
        $this->paymentType = $order->payment_type ?? 'full';
        $this->months = $order->months ?? 5;
        $this->rate = $order->rate ?? 0;
        $this->orderType = $order->order_type;
        $this->deliveryType = $order->delivery_type;
        $this->deliveryFee = $order->delivery_fee / 100;
        $this->deliveryAddress = $order->delivery_address;

//        if($order->order_type != 'regular'){
//            $this->paymentType = 'cod';
//        }

        $this->getPaymentMethods();

        if($this->completedCod){


            $paymentMethods = $this->getCodPaymentMethods();
            if($paymentMethods->isNotEmpty()){
                $this->paymentMethodsCod = [];
                $this->referenceNumbersCod = [];
                $this->amountsCod = [];

                foreach($paymentMethods as $methods){
                    $this->referenceNumbersCod[] = $methods->reference;
                    $this->paymentMethodsCod[] = $methods->method;
                    $this->amountsCod[] = $methods->amount / 100;
                }
            }
        }

        $items = $this->getItems();
        foreach ($items as $item) {
            $this->calculateDiscount($item->product_id, $item->price);
        }

        $this->customer = $this->getCustomer();

        $this->sameAddress = $order->delivery_address == $this->customer->address;
    }

    public function calculateDiscount(string $productId, int $price): void
    {
        $product = $this->getProduct($productId);
        $item = $this->getItem($productId);
        $priceType = $item->price_type;

        $discount = 0;
        if($product->$priceType > $price)
            $discount = ($product->$priceType - $price) / $product->$priceType;

        $this->discounts[$productId] = round($discount * 100, 2);
    }

    public function updatedPrices(IOrderService $orderService, $price, $productId): void
    {
        $item = $this->getItem($productId);
        $result = $orderService->updateItemPrice($this->orderId, $productId, $price * 100, $item->price_type);
        $this->calculateDiscount($productId, $price * 100);
    }

    public function updatedDiscounts(IOrderService $orderService, $percentage, $productId): void
    {
        $product = $this->getProduct($productId);
        $item = $this->getItem($productId);
        $priceType = $item->price_type;

        $rate = $percentage / 100;
        $discount = $product->$priceType * $rate;
        $discountedPrice = $product->$priceType - $discount;

        $orderService->updateItemPrice($this->orderId, $productId, $discountedPrice, $priceType);
    }

    public function incrementQuantity(IStockManagementService $stockManagementService, IOrderService $orderService, $productId)
    {
        $quantity = $this->quantities[$productId];
        $quantity = $quantity + 1;

        $item = $this->getItem($productId);
        $reservationId = $item->reservation_id;

        DB::beginTransaction();

        $cancelResult = $stockManagementService->cancelReservation(
            $productId,
            $reservationId,
            auth()->user()->id, false
        );

        if ($cancelResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($cancelResult->getError()));
        }

        $newReservationId = Str::uuid()->toString();
        $reserveResult = $stockManagementService->reserve(
            $productId,
            $newReservationId,
            $quantity,
            $this->branch,
            auth()->user()->id,
            $this->orderType != 'regular'
        );

        if ($reserveResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($reserveResult->getError()));
        }

        $updateItemResult = $orderService->updateItemQuantity($this->orderId, $productId, $quantity, $newReservationId);
        if ($updateItemResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($updateItemResult->getError()));
        }

        DB::commit();
    }

    public function decrementQuantity(IStockManagementService $stockManagementService, IOrderService $orderService, $productId)
    {
        $quantity = $this->quantities[$productId];

        if ($quantity == 1) return;

        $quantity = $quantity - 1;

        $item = $this->getItem($productId);
        $reservationId = $item->reservation_id;

        DB::beginTransaction();

        $cancelResult = $stockManagementService->cancelReservation(
            $productId,
            $reservationId,
            auth()->user()->id, false
        );

        if ($cancelResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($cancelResult->getError()));
        }

        $newReservationId = Str::uuid()->toString();
        $reserveResult = $stockManagementService->reserve(
            $productId,
            $newReservationId,
            $quantity,
            $this->branch,
            auth()->user()->id,
            $this->orderType != 'regular'
        );

        if ($reserveResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($reserveResult->getError()));
        }

        $updateItemResult = $orderService->updateItemQuantity($this->orderId, $productId, $quantity, $newReservationId);
        if ($updateItemResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($updateItemResult->getError()));
        }

        DB::commit();
    }

    public function addItem(IStockManagementService $stockManagementService, IOrderService $orderService, $productId, $type = 'regular')
    {
        $this->resetErrorBag();
        if($type != $this->orderType){
            session()->flash('alert', "Can only add items for $this->orderType order.");
            return;
        }

        $product = $this->getProduct($productId);

        DB::beginTransaction();

        $newReservationId = Str::uuid()->toString();
        $reserveResult = $stockManagementService->reserve(
            $productId,
            $newReservationId,
            1,
            $this->branch,
            auth()->user()->id,
            $type != 'regular'
        );

        if ($reserveResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($reserveResult->getError()));
        }
        $priceType = $this->priceType[$productId] ?? 'regular_price';
        $addResult = $orderService->addItem($this->orderId, $productId, "{$product->model} {$product->description}", $product->$priceType, $product->$priceType, 1, $newReservationId, $priceType);
        if ($addResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($addResult->getError()));
        }

        DB::commit();
    }

    public function removeItem(IStockManagementService $stockManagementService, IOrderService $orderService, $productId)
    {
        $item = $this->getItem($productId);
        $reservationId = $item->reservation_id;

        DB::beginTransaction();

        $cancelResult = $stockManagementService->cancelReservation(
            $productId,
            $reservationId,
            auth()->user()->id, false
        );

        if ($cancelResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($cancelResult->getError()));
        }

        $removeResult = $orderService->removeItem($this->orderId, $productId);
        if ($removeResult->isFailure()) {
            DB::rollBack();
            return session()->flash('alert', ErrorHandler::getErrorMessage($removeResult->getError()));
        }

        DB::commit();
    }

    public function confirmOrder(IIdentityAndAccessService $identityAndAccessService, IOrderService $orderService)
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        DB::beginTransaction();

        $authResult = $identityAndAccessService->authorize($this->email, $this->password, $this->orderId);
        if($authResult->isFailure()){
            DB::rollBack();
            return session()->flash('alert-auth', ErrorHandler::getErrorMessage($authResult->getError()));
        }

        $encrypted = $authResult->getValue();
        $confirmResult = $orderService->confirmOrder($this->orderId, auth()->user()->id, $encrypted);
        if($confirmResult->isFailure()){
            DB::rollBack();
            return session()->flash('alert-auth', ErrorHandler::getErrorMessage($confirmResult->getError()));
        }

        DB::commit();

        $this->dispatch('order-confirmed');
    }

    private function getOrder()
    {
        return DB::table('orders')->where('order_id', $this->orderId)->first();
    }

    private function getCancelledOrder()
    {
        $order = $this->getOrder();
        return DB::table('orders')->where('order_id', $order->cancelled_order_id)->first();
    }

    public function isSameDayCancelled($order): bool
    {
        return $order && date('Y-m-d', strtotime($order->completed_at)) == date('Y-m-d');
    }

    private function getPaymentMethods()
    {
        $paymentMethods = [];
        $order = $this->getOrder();
        $transaction = DB::table('transactions')->where('order_id', $this->orderId)->first();
        $fromCancelledOrder = false;

        if ($transaction){
            $query = DB::table('payment_methods')
                ->where('order_id', $this->orderId)
                ->where('transaction_id', $transaction->id);

            $paymentMethods = $query->get();
        }else if($order->cancelled_order_id){
            $transaction = DB::table('transactions')->where('order_id', $order->cancelled_order_id)->first();

            if($transaction){
                $query = DB::table('payment_methods')
                    ->where('order_id', $order->cancelled_order_id)
                    ->where('transaction_id', $transaction->id);

                $paymentMethods = $query->get();
                $fromCancelledOrder = true;
            }
        }

        if(!empty($paymentMethods)){
            $this->paymentMethods = [];
            $this->referenceNumbers = [];
            $this->amounts = [];
            $this->credit = [];

            foreach($paymentMethods as $methods){
                $this->referenceNumbers[] = $methods->reference;
                $this->paymentMethods[] = $methods->method;
                $this->amounts[] = $methods->amount / 100;
                $this->credit[] = $methods->credit ? true : false;
            }
        }
    }

    private function getCodPaymentMethods()
    {
        $transaction = DB::table('transactions')->where('order_id', $this->orderId)->latest()->first();
        $this->receiptNumberCod = $transaction->or_number;

        return DB::table('payment_methods')
            ->where('order_id', $this->orderId)
            ->where('transaction_id', $transaction->id)
            ->get();
    }

    private function getItems()
    {
        $items =  DB::table('line_items')
            ->where('order_id', $this->orderId)
            ->get();

        $this->reset('prices', 'quantities');
        foreach($items as $item){
            $this->prices[$item->product_id] = $item->price / 100;
            $this->quantities[$item->product_id] = $item->quantity;
        }

        return $items;
    }

    private function getItem($productId)
    {
        return DB::table('line_items')
            ->where('order_id', $this->orderId)
            ->where('product_id', $productId)
            ->first();
    }

    public function getCustomer()
    {
        $order = $this->getOrder();

        return DB::table('customers')
            ->where('id', $order->customer_id)
            ->first();
    }

    public function getAssistant()
    {
        $order = $this->getOrder();

        return DB::table('users')
            ->where('id', $order->assistant_id)
            ->first();
    }

    public function getCashier()
    {
        $order = $this->getOrder();

        return DB::table('users')
            ->where('id', $order->cashier)
            ->first();
    }

    public function getProduct($productId)
    {
        return Product::whereId($productId)->withTrashed()->first();
    }

    private function getProducts()
    {
        $items = $this->getItems();

        $ids = [];
        foreach($items as $item){
            $ids[] = $item->product_id;
        }

        $branchId = $this->branch;
        $query = Product::join('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->leftJoin('product_requests', function ($join) use ($branchId) {
                $join->on('products.id', '=', 'product_requests.product_id')
                    ->where('product_requests.receiver', '=', $this->branch);
            })
            ->leftJoin('stocks', function ($join) use ($branchId) {
                $join->on('products.id', '=', 'stocks.product_id')
                    ->where('stocks.branch_id', '=', $this->branch);
            })->where(function ($q) {
                $q->where('model', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('sku_number', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('description', 'LIKE', '%' . $this->search . '%');
            })->select('products.*', 'suppliers.name as supplier_name', 'suppliers.code', 'product_requests.quantity as requested_quantity', DB::raw('COALESCE(stocks.available, 0) as quantity'))
            ->whereNull('products.deleted_at')
            ->whereNotIn('products.id', $ids)
            ->orderBy('quantity');

        return $query->paginate(3);
    }

    public function newPaymentMethod(): void
    {
        $this->paymentMethods[] = 'Cash';
        $this->referenceNumbers[] = '';
        $this->amounts[] = null;
        $this->credit[] = false;
    }

    public function newPaymentMethodCod(): void
    {
        $this->paymentMethodsCod[] = 'Cash';
        $this->referenceNumbersCod[] = '';
        $this->amountsCod[] = null;
    }


    public function removePaymentMethod(int $index): void
    {
        unset($this->paymentMethods[$index]);
        unset($this->referenceNumbers[$index]);
        unset($this->amounts[$index]);
        unset($this->credit[$index]);

        $this->paymentMethods = array_values($this->paymentMethods);
        $this->referenceNumbers = array_values($this->referenceNumbers);
        $this->amounts = array_values($this->amounts);
        $this->credit = array_values($this->credit);
    }

    public function removePaymentMethodCod(int $index): void
    {
        unset($this->paymentMethodsCod[$index]);
        unset($this->referenceNumbersCod[$index]);
        unset($this->amountsCod[$index]);

        $this->paymentMethodsCod = array_values($this->paymentMethodsCod);
        $this->referenceNumbersCod = array_values($this->referenceNumbersCod);
        $this->amountsCod = array_values($this->amountsCod);
    }

    /**
     * @throws \Throwable
     */
    public function submitPayment(IPaymentService $paymentService, IDeliveryService $deliveryService, IOrderService $orderService): void
    {
        $this->resetErrorBag();
        $this->validate([
            'receiptNumber' => 'required',
            'deliveryAddress' => [
                Rule::requiredIf($this->deliveryType == 'deliver' && !$this->sameAddress)
            ],
            'installmentStartDate' => [
                Rule::requiredIf($this->deliveryType == 'previous' && $this->paymentType == 'installment')
            ]
        ]);

        if($this->getItems()->isEmpty()){
            session()->flash('alert', 'No items added to order.');
            return;
        }

        $order = $this->getOrder();
        $cancelledOrder = $this->getCancelledOrder();

//        if($cancelledOrder && $order->total < $cancelledOrder->total){
//            $this->addError('total', 'Order total should be equal or greater than the previous amount of ' . Money::PHP($cancelledOrder->total));
//            return;
//        }

        if($order->status != 0){
            $this->addError('total', 'Order already processed.');
            return;
        }

        if($this->deliveryType == 'previous'){
            $orderService->setPreviousOrder($order->order_id, new DateTime($this->installmentStartDate));
        }

        if($this->paymentType == 'full'){
            $this->fullPayment($paymentService, $deliveryService);
        }else if($this->paymentType == 'installment'){
            $this->installmentPayment($paymentService, $deliveryService);
        }else if($this->paymentType == 'cod'){
            $this->codPayment($paymentService);
        }
    }

    private function placeDeliveryOrder(IDeliveryService $deliveryService): Result
    {
        $items = [];
        foreach ($this->getItems() as $item) {
            $items[] = [
                'productId' => $item->product_id,
                'title' => $item->title,
                'quantity' => $item->quantity,
                'reservationId' => $item->reservation_id,
            ];
        }

        $address = $this->customer->address;
        if(!$this->sameAddress) $address = $this->deliveryAddress;

        $fee = (float) $this->deliveryFee;

        $deliveryType = $this->deliveryType == 'previous' ? 'pickup' : $this->deliveryType;

        return $deliveryService->placeOrder($this->orderId, $items, $deliveryType, $this->branch, $fee * 100, $address);
    }

    /**
     * @throws \Throwable
     */
    public function codPayment(IPaymentService $paymentService): void
    {
        DB::beginTransaction();

        $order = $this->getOrder();

        $downPayment = [];
        for($i = 0; $i < count($this->amounts); $i++){
            if($this->amounts[$i] > 0) {
                $downPayment[] = [
                    'amount' => $this->amounts[$i] * 100,
                    'reference' => $this->referenceNumbers[$i],
                    'method' => $this->paymentMethods[$i],
                ];
            }
        }

        $result = $paymentService->requestCod(
            $order->customer_id,
            $order->total,
            $order->order_id,
            $downPayment,
            auth()->user()?->id,
            Str::uuid()->toString(),
            $this->receiptNumber,
        );

        if($result->isFailure()){
            DB::rollBack();
            session()->flash('alert', ErrorHandler::getErrorMessage($result->getError()));
            return;
        }

        DB::commit();
        $this->redirect(route('admin.order.details', ['order_id' => $this->orderId]), true);
    }

    /**
     * @throws \Throwable
     */
    public function installmentPayment(IPaymentService $paymentService, IDeliveryService $deliveryService): void
    {
        DB::beginTransaction();

        $order = $this->getOrder();

        $deliveryResult = $this->placeDeliveryOrder($deliveryService);
        if($deliveryResult->isFailure()){
            DB::rollBack();
            session()->flash('alert', ErrorHandler::getErrorMessage($deliveryResult->getError()));
            return;
        }

        $downPayment = [];
        for($i = 0; $i < count($this->amounts); $i++){
            if($this->amounts[$i] > 0) {
                $downPayment[] = [
                    'amount' => $this->amounts[$i] * 100,
                    'reference' => $this->referenceNumbers[$i],
                    'method' => $this->paymentMethods[$i],
                    'credit' => $this->credit[$i] ?? false,
                ];
            }
        }

        $result = $paymentService->initializeInstallment(
            $order->customer_id,
            Str::uuid()->toString(),
            $order->total,
            $this->months,
            $this->rate,
            $order->order_id,
            $downPayment,
            auth()->user()?->id,
            Str::uuid()->toString(),
            $this->receiptNumber,
        );

        if($result->isFailure()){
            DB::rollBack();
            session()->flash('alert', ErrorHandler::getErrorMessage($result->getError()));
            return;
        }

        DB::commit();
        $this->redirect(route('admin.order.details', ['order_id' => $this->orderId]), true);
    }

    /**
     * @throws \Throwable
     */
    public function fullPayment(IPaymentService $paymentService, IDeliveryService $deliveryService): void
    {
        $this->validate([
            'amounts.*' => 'required|numeric',
            'referenceNumbers.*' => 'required',
        ], [
            'amounts.*' => 'Amount is required',
            'referenceNumbers.*' => 'Reference Number is required',
        ]);

        $order = $this->getOrder();

        $total = array_sum($this->amounts) * 100;

        if(($order->total + $this->deliveryFee * 100) != $total)
        {
            $this->addError('total', 'Payment total should be equal to the order total');
            return;
        }

        DB::beginTransaction();

        $deliveryResult = $this->placeDeliveryOrder($deliveryService);
        if($deliveryResult->isFailure()){
            DB::rollBack();
            session()->flash('alert', ErrorHandler::getErrorMessage($deliveryResult->getError()));
            return;
        }

        $fullPayment = [];
        for($i = 0; $i < count($this->amounts); $i++){
            if($this->amounts[$i] > 0) {
                $fullPayment[] = [
                    'amount' => $this->amounts[$i] * 100,
                    'reference' => $this->referenceNumbers[$i],
                    'method' => $this->paymentMethods[$i],
                    'credit' => $this->credit[$i] ?? false,
                ];
            }
        }

        $result = $paymentService->pay(
            $order->customer_id,
            $fullPayment,
            auth()->user()?->id,
            Str::uuid()->toString(),
            $this->receiptNumber,
            $order->order_id
        );

        if($result->isFailure()){
            DB::rollBack();
            session()->flash('alert', ErrorHandler::getErrorMessage($result->getError()));
            return;
        }

        DB::commit();

        session()->flash('success', 'Order successfully processed.');
        $this->redirect(route('admin.order.details', ['order_id' => $this->orderId]), true);
    }

    /**
     * @throws \Throwable
     */
    public function submitCodPayment(IPaymentService $paymentService): void
    {
        $this->validate([
            'receiptNumberCod' => 'required',
            'amountsCod.*' => 'required|numeric',
            'referenceNumbersCod.*' => 'required',
        ], [
            'amountsCod.*' => 'Amount is required',
            'referenceNumbersCod.*' => 'Reference Number is required',
        ]);

        $order = $this->getOrder();

        $total = $order->total - (array_sum($this->amounts) * 100);
        $codTotal = array_sum($this->amountsCod) * 100;

        if($codTotal != $total)
        {
            $this->addError('totalCod', 'Full payment should be equal to the order balance');
            return;
        }

        DB::beginTransaction();

        $downPayment = [];
        for($i = 0; $i < count($this->amountsCod); $i++){
            if($this->amountsCod[$i] > 0) {
                $downPayment[] = [
                    'amount' => $this->amountsCod[$i] * 100,
                    'reference' => $this->referenceNumbersCod[$i],
                    'method' => $this->paymentMethodsCod[$i],
                ];
            }
        }

        $result = $paymentService->payCod(
            $order->customer_id,
            $downPayment,
            auth()->user()?->id,
            Str::uuid()->toString(),
            $this->receiptNumberCod,
            $order->order_id
        );

        if($result->isFailure()){
            DB::rollBack();
            session()->flash('alert', ErrorHandler::getErrorMessage($result->getError()));
            return;
        }

        DB::commit();
        $this->redirect(route('admin.order.details', ['order_id' => $this->orderId]), true);
    }

    public function getTransaction()
    {
        return DB::table('transactions')
            ->where('order_id', $this->orderId)
            ->first();
    }

    public function getReceiptType(): string
    {
        $order = $this->getOrder();

        if($order->payment_type == 'installment') return 'CI';
        if($order->order_type != 'regular') return 'CI';

        $orderTotal = $order->total + $order->delivery_fee;
        if($orderTotal >= 1000000) return 'DR';

        return 'SI';
    }

    public function getPaymentTotalWithoutCod(): int
    {
        return DB::table('payment_methods')
            ->where('order_id', $this->orderId)
            ->where('method', '!=', 'COD')
            ->sum('amount');
    }

    public function getCodPaymentTotal(): int
    {
        return DB::table('payment_methods')
            ->where('order_id', $this->orderId)
            ->where('method', '=', 'COD')
            ->sum('amount');
    }

    public function getPaymentBreakdown(): string
    {
        $order = $this->getOrder();
        $result = '';

        if($order->payment_type == 'installment') $result .= "$order->months MS DP:";

        $paymentMethods = DB::table('payment_methods')
            ->where('order_id', $this->orderId)
            ->get();

        foreach ($paymentMethods as $method) {
            if($method->method != 'COD'){
                $amount = money($method->amount);
                $type = strtoupper($method->method);
                $result .= "$amount THRU $type ";
            }
        }

        $cod = $this->getCodPaymentTotal();
        if($cod > 0){
            $codAmount = money($cod);
            $result .= "COD - $codAmount ";
        }

        return $result;
    }

    public function getProductSupplierCode(string $productId): string
    {
        $product = Product::find($productId);
        if(!$product) return '';

        return $product->supplier->code;
    }

    #[Layout('livewire.admin.base_layout')]
    public function render()
    {
        return view('livewire.admin.order.order-details', [
            'order' => $this->getOrder(),
            'cartItems' => $this->getItems(),
            'cancelled' => $this->getCancelledOrder(),
            'products' => $this->getProducts(),
            'assistant' => $this->getAssistant(),
            'cashier' => $this->getCashier(),
            'transaction' => $this->getTransaction(),
        ]);
    }
}
