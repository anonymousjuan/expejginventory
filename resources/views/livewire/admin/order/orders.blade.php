<div>
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-10">
                        <h1 class="h3 text-primary admin-title mb-0"><strong>{{ ucfirst($displayStatus) }} {{ ucfirst($type) }} Orders</strong></h1>
                    </div>
                </div>

            </div>
        </div>
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 offset-md-9">
                        <div class="form-group">
                            <input type="text" class="float-left form-control" placeholder="Search..."
                                wire:model.live="search">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    @if (session('alert'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('alert') }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    <table class="table table-bordered table-striped table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr class="bg-secondary font-w">
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Sales Representative</th>
                                @hasrole('admin')
                                    <th>Branch</th>
                                @endhasrole
                                <th>Total</th>
                                <th>Placed At</th>
                                @if($displayStatus == 'processed')
                                    <th>Payment Type</th>
                                    <th>Payment Status</th>
                                    <th>Delivery Status</th>
                                @endif
                                @if($displayStatus != 'processed')
                                    <th>Notes</th>
                                @endif
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td>#{{ str_pad((string) $order->id, 12, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $order->customer_first_name }} {{ $order->customer_last_name }}</td>
                                    <td>{{ $order->assistant_first_name }} {{ $order->assistant_last_name }}</td>
                                    @hasrole('admin')
                                        <td>{{ $order->branch_name }}</td>
                                    @endhasrole
                                    <td>@money($order->total)</td>
                                    <td>{{ date('F j, Y', strtotime($order->placed_at)) }}</td>
                                    @if($displayStatus == 'processed')
                                        <td>{{ ucfirst($order->payment_type) }}</td>
                                        <td>{{ $this->getPaymentStatus($order->order_id) }}</td>
                                        <td>{{ $this->getDeliveryStatus($order->order_id) }}</td>
                                    @endif
                                    @if($displayStatus != 'processed')
                                        <td>{{ $order->notes }}</td>
                                    @endif
                                    <td align = "center">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.order.details', ['order_id' => $order->order_id]) }}" type="button"
                                                class="btn btn-primary">@if($displayStatus != 'pending') View @else Checkout @endif</a>
                                            <button type="button"
                                                class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <span class="sr-only">Toggle Dropdown</span>
                                            </button>
                                            <div class="dropdown-menu">
                                                @if($displayStatus == 'processed')
                                                <a data-toggle="modal" data-target="#cancelModal{{ $order->order_id }}" class="dropdown-item"
                                                    href="#">Cancel</a>
                                                <a data-toggle="modal" data-target="#refundModal{{ $order->order_id }}" class="dropdown-item"
                                                   href="#">Refund</a>
                                                @endif
{{--                                                <a data-toggle="modal" data-target="#deleteModal{{ $order->order_id }}" class="dropdown-item"--}}
{{--                                                   href="#">Delete</a>--}}
                                            </div>
                                            <!-- Cancel Modal -->
                                            <div wire:ignore.self class="modal fade" id="cancelModal{{ $order->order_id }}" tabindex="-1"
                                                role="dialog" aria-labelledby="cancelModal{{ $order->order_id }}Label" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <form class="user" wire:submit="cancelOrder('{{ $order->order_id }}')">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="cancelModal{{ $order->order_id }}Label">Cancel Order #{{ str_pad((string) $order->id, 12, '0', STR_PAD_LEFT) }}
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if (session('alert-auth'))
                                                                <div class="alert alert-danger" role="alert">
                                                                    {{ session('alert-auth') }}
                                                                </div>
                                                            @endif
                                                                <div class="form-group">
                                                                    <input autocomplete="false" wire:model="email" type="email" class="form-control"
                                                                           aria-describedby="emailHelp"
                                                                           placeholder="Enter Email Address...">
                                                                    @error('email')
                                                                    <span class="text-danger">{{ $message }}</span>
                                                                    @enderror
                                                                </div>
                                                                <div class="form-group">
                                                                    <input wire:model="password" type="password" class="form-control"
                                                                           placeholder="Password">
                                                                    @error('password')
                                                                    <span class="text-danger">{{ $message }}</span>
                                                                    @enderror
                                                                </div>

                                                                <div class="form-group">
                                                                    <textarea wire:model="notes" class="form-control" placeholder="Notes"></textarea>
                                                                    @error('notes')
                                                                    <span class="text-danger">{{ $message }}</span>
                                                                    @enderror
                                                                </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">No</button>
                                                            <button type="submit"
                                                                class="btn btn-danger">Yes</button>
                                                        </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Cancel Modal -->

                                                <!-- Delete Modal -->
                                                <div wire:ignore.self class="modal fade" id="deleteModal{{ $order->order_id }}" tabindex="-1"
                                                     role="dialog" aria-labelledby="deleteModal{{ $order->order_id }}Label" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <form class="user" wire:submit="deleteOrder('{{ $order->order_id }}')">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModal{{ $order->order_id }}Label">Delete Order #{{ str_pad((string) $order->id, 12, '0', STR_PAD_LEFT) }}
                                                                    </h5>
                                                                    <button type="button" class="close" data-dismiss="modal"
                                                                            aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    @if (session('alert-auth'))
                                                                        <div class="alert alert-danger" role="alert">
                                                                            {{ session('alert-auth') }}
                                                                        </div>
                                                                    @endif
                                                                    <div class="form-group">
                                                                        <input autocomplete="false" wire:model="email" type="email" class="form-control"
                                                                               aria-describedby="emailHelp"
                                                                               placeholder="Enter Email Address...">
                                                                        @error('email')
                                                                        <span class="text-danger">{{ $message }}</span>
                                                                        @enderror
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <input wire:model="password" type="password" class="form-control"
                                                                               placeholder="Password">
                                                                        @error('password')
                                                                        <span class="text-danger">{{ $message }}</span>
                                                                        @enderror
                                                                    </div>

                                                                    <div class="form-group">
                                                                        <textarea wire:model="notes" class="form-control" placeholder="Notes"></textarea>
                                                                        @error('notes')
                                                                        <span class="text-danger">{{ $message }}</span>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary"
                                                                            data-dismiss="modal">No</button>
                                                                    <button type="submit"
                                                                            class="btn btn-danger">Yes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- End Delete Modal -->

                                                <!-- Refund Modal -->
                                                <div wire:ignore.self class="modal fade" id="refundModal{{ $order->order_id }}" tabindex="-1"
                                                     role="dialog" aria-labelledby="refundModal{{ $order->order_id }}Label" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <form class="user" wire:submit="refundOrder('{{ $order->order_id }}')">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="refundModal{{ $order->order_id }}Label">Refund Order #{{ str_pad((string) $order->id, 12, '0', STR_PAD_LEFT) }}
                                                                    </h5>
                                                                    <button type="button" class="close" data-dismiss="modal"
                                                                            aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    @if (session('alert-auth'))
                                                                        <div class="alert alert-danger" role="alert">
                                                                            {{ session('alert-auth') }}
                                                                        </div>
                                                                    @endif
                                                                    <div class="form-group">
                                                                        <input autocomplete="false" wire:model="email" type="email" class="form-control"
                                                                               aria-describedby="emailHelp"
                                                                               placeholder="Enter Email Address...">
                                                                        @error('email')
                                                                        <span class="text-danger">{{ $message }}</span>
                                                                        @enderror
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <input wire:model="password" type="password" class="form-control"
                                                                               placeholder="Password">
                                                                        @error('password')
                                                                        <span class="text-danger">{{ $message }}</span>
                                                                        @enderror
                                                                    </div>

                                                                        <div class="form-group">
                                                                            <input wire:model="pcv" class="form-control" placeholder="PCV"></input>
                                                                            @error('pcv')
                                                                            <span class="text-danger">{{ $message }}</span>
                                                                            @enderror
                                                                        </div>

                                                                    <div class="form-group">
                                                                        <textarea wire:model="notes" class="form-control" placeholder="Notes"></textarea>
                                                                        @error('notes')
                                                                        <span class="text-danger">{{ $message }}</span>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary"
                                                                            data-dismiss="modal">No</button>
                                                                    <button type="submit"
                                                                            class="btn btn-danger">Yes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- End Refund Modal -->
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" align="center">No orders found</td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                    {{ $orders->links() }}
                </div>
            </div>
        </div>


    </div>
    <!-- /.container-fluid -->

</div>

@script
<script>
    $wire.on('close-modal', () => {
        $('.modal').modal('hide');
    });

    $('.modal').on('hide.bs.modal', function (e) {
        $wire.dispatch('modal-hidden');
    })
</script>
@endscript
