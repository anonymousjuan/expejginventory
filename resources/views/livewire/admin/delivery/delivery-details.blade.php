<div x-data="{ edit: false }">
    <!-- Begin Page Content -->
    <div class="container-fluid">
        @php
            switch ($delivery->status) {
                case 0:
                    $class = 'primary';
                    break;

                    case 1:
                    $class = 'secondary';
                    break;

                    case 2:
                    $class = 'success';
                    break;

                    case 3:
                    $class = 'danger';
                    break;
                default:
                    $class = 'primary';
            }
        @endphp

    <!-- Page Heading -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-10">
                    <h1 class="h3 text-primary admin-title mb-0"><strong>Delivery #{{ str_pad((string) $delivery->id, 12, '0', STR_PAD_LEFT) }} <span class="badge badge-{{ $class }}">{{ $this->getStatus($delivery->status) }}</span></strong></h1>
                </div>
                <div class="col-md-2">
                    <div class="d-flex justify-content-end">
                        <a href = "#" class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <!-- DataTales Example -->
        <form wire:submit="setAsDelivered">
            <div class="card shadow mb-4">
                <div class="card-body">
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
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Driver</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $delivery->driver_first_name }} {{ $delivery->driver_last_name }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Truck</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $delivery->truck }}</div>
                                </div>
                            </div>
                        </div>
                        @hasrole('admin')
                        <div class="col-md-4">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Branch</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $delivery->branch_name }}</div>
                                </div>
                            </div>
                        </div>
                        @endhasrole
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Notes
                                        <a x-show="!edit" x-on:click="edit = true" href="#" class="btn btn-success btn-circle btn-sm ml-2">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                    </div>
                                    <div x-show="!edit" class="h5 mb-0 font-weight-bold text-gray-800">{{ $notes }}</div>
                                    <div x-show="edit" class="form-group" style = "display:none;">
                                        <textarea wire:model="notes" class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
                                        @error('notes')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                        <a x-on:click="edit = false" href="#" class="btn btn-secondary mt-2 ml-2 float-right">
                                            Cancel
                                        </a>
                                        <a wire:click="updateNotes" x-on:click="edit = false" href="#" class="btn btn-success mt-2 float-right">
                                            Save
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <div class="mb-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Date</div>
                        <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                            <button wire:click="changeDate('decrement')" type="button" class="btn btn-primary">
                                <i class="fas fa-chevron-left"></i></button>
    
                            <input class = "form-control" value="{{ $date ?? date('Y-m-d') }}" id="datepicker" style ="border-radius: 0;">
    
                            <button wire:click="changeDate('increment')" type="button" class="btn btn-primary">
                                <i class="fas fa-chevron-right"></i></button>
                                
                        </div>
                        <div class="mt-3 alert alert-info text-center" role="alert">
                            <medium>Wait for the page to reload after setting the date</medium>
                        </div>
                        </div>
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Address</th>
                               
                            </tr>
                            </thead>
                            <tbody>
                            @php
                            @endphp
                            @forelse ($orders as $order)
                                <tr wire:key="{{ $order->id }}">
                                    <td><a href="{{ route('admin.order.details', ['order_id' => $order->order_id]) }}">
                                        #{{ str_pad((string) $order->id, 12, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        {{ $order->customer_first_name }} {{ $order->customer_last_name }}</td>
                                    <td>
                                        @foreach($this->getItems($order->order_id) as $item)
                                            <div wire:key="{{ $item->order_id }}.{{ $item->product_id }}" class="form-group row">
                                                <label for="colFormLabel" class="col-sm-5 col-form-label">{{ $item->delivered }}/{{ $item->quantity }} {{ $this->getProductSupplierCode($item->product_id) }} {{ $this->getProductTitle($order->order_id, $item->product_id) }}</label>
                                                @if($delivery->status == 0)
                                                <div class="col-sm-5">
                                                    <input wire:model="quantities.{{ $order->order_id }}.{{ $item->product_id }}" type="number" min="0" max="{{ $item->quantity }}" class="form-control" id="colFormLabel" placeholder="Enter quantity">
                                                    @error("quantities.$order->order_id.$item->product_id")
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                    <td>{{ $order->delivery_address }}</td>
                                    {{-- <td>{{ $order->complete_date }}</td> --}}
                                </tr>
                                @php
                                    $date = $order->complete_date;
                                @endphp
                            @empty
                                <tr>
                                    <td colspan="10" style="text-align: center;">No orders found</td>
                                </tr>
                            @endforelse

                            </tbody>
                        </table>
                        @error('toShip')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                        @if($delivery->status == 0)
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Set as Delivered</button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>

    </div>
    <!-- /.container-fluid -->

</div>


<x-slot:print>
    <div class="printable">
        <table>
            <tr>
                <td style = "width: 38%">
                    <img src="{{ asset('assets/img/left_logo.png') }}" alt="" style = "width:350px">
                </td>
                <td align = "center">
                    <h2><strong>{{ $delivery->branch_name }}</strong></h2>
                    <h6><strong>DAILY MONITORING DELIVERY REPORT</strong></h6>
                    <h6><strong>Date: {{ $this->date }}</strong></h6>
                </td>
            </tr>
        </table>
        <table class="table table-bordered receipt-table">
            <thead>
            <tr>
                <th rowspan="2" style = "vertical-align:middle;text-align:center ">Date Purchased</th>
                <th rowspan="2" style = "vertical-align:middle;text-align:center ">Invoice/Sales</th>
                <th rowspan="2" style = "vertical-align:middle;text-align:center ">Customer</th>
                <th colspan="3" style = "text-align:center ">Items</th>
                <th colspan="2" style = "text-align:center ">Time</th>
                <th rowspan="2" style = "vertical-align:middle;text-align:center ">COD</th>
                <th rowspan="2" style = "vertical-align:middle;text-align:center; width: 80px ">Customer Signature</th>
                <th rowspan="2" style = "vertical-align:middle;text-align:center ">Comments</th>
            </tr>
            <tr>
                <th style = "text-align:center ">QTY</th>
                <th style = "text-align:center; width: 250px; ">Particulars</th>
                <th style = "text-align:center">B/A</th>
                <th style = "text-align:center; width: 50px ">IN</th>
                <th style = "text-align:center; width: 50px">OUT</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($orders as $order)
                @php
                    $items = $this->getItems($order->order_id);
                    $rowspan = count($items);
                @endphp
                <tr>
                    <td rowspan="{{ $rowspan }}" style="text-align: center;">{{ date('m/d/y', strtotime($order->completed_at)) }}</td>
                    <td rowspan="{{ $rowspan }}" style="text-align: center;">{{ $order->receipt_number }}<br>{{ $order->assistant_first_name }}</td>
                    <td rowspan="{{ $rowspan }}">
                        <strong>{{ $order->customer_first_name }} {{ $order->customer_last_name }}</strong><br>
                        {{ $order->delivery_address }}<br>
                        {{ $order->customer_phone }}
                    </td>
                    <td style="text-align: center;">{{ $items[0]->quantity }}</td>
                    <td>{{ $this->getProductSupplierCode($items[0]->product_id) }} {{ $this->getProductTitle($order->order_id, $items[0]->product_id) }}</td>
                    <td rowspan="{{ $rowspan }}"></td>
                    <td rowspan="{{ $rowspan }}"></td>
                    <td rowspan="{{ $rowspan }}"></td>
                    <td rowspan="{{ $rowspan }}" style="text-align: center;">{{ $this->getCodAmount($order->order_id) }}</td>
                    <td rowspan="{{ $rowspan }}"></td>
                    <td rowspan="{{ $rowspan }}"></td>
                </tr>
                @foreach($items as $item)
                    @if($loop->index != 0)
                    <tr>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td>{{ $this->getProductSupplierCode($item->product_id) }} {{ $this->getProductTitle($order->order_id, $item->product_id) }}</td>
                    </tr>
                    @endif
                @endforeach
            @endforeach
            </tbody>
        </table>

        <table class="table table-bordered receipt-table" style = "margin-top:50px;">
            <thead>
                <tr>
                    <th colspan = "6"><center>SIGNATURE OVER PRINTED NAME</center></th>
                </tr>
                <tr>
                    <th><center>CHECKED</center></th>
                    <th><center>DRIVER</center></th>
                    <th><center>HELPER</center></th>
                    <th><center>TIME IN</center></th>
                    <th><center>TIME OUT</center></th>
                    <th><center>VERIFIED BY</center></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><br><br></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</x-slot>

@assets
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .receipt-container {
            width: 600px;
            margin: 0;
            padding: 0;
            float: left;
        }

        .receipt-header {
            text-align: right;
        }

        .receipt-details {
            margin-top: 20px;
            margin-left: 40px;
        }

        .receipt-details p {
            margin: 2px 0;
        }

        .receipt-table-container {
            margin-top: 20px;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            border:1px solid #000 !important;
        }

        .receipt-table th,
        .receipt-table td {
            border:1px solid #000 !important;
            padding: 8px;
            text-align: left;
            color:#000;
            font-weight: 700;
        }

        .receipt-totals {
            margin-top: 20px;
            float: right;
        }

        .receipt-totals p {
            margin: 2px 0;
        }

        .receipt-footer {
            margin-top: 80px;
            margin-left: 50px;
            max-width:250px;
            word-wrap:break-word;
        }

        .receipt-footer p {
            margin: 2px 0;
        }
        .no-print {
            display: block;
        }
        .printable {
            display: none;
        }
        @media print {
            @page {
                size: landscape; /* Ensures the page is printed in landscape */
                margin: 1cm; /* Set margins to ensure no content is cut off */
            }

            .no-print {
                display: none;
            }

            .printable {
                display: block;
            }

            .receipt-table{
                font-size: 10px;
            }
            .receipt-table th,td{
                padding:1px;
            }
            /* Apply a scaling factor to fit the table within the page */
            body {
                transform: scale(0.99); /* Scale down the entire content */
                transform-origin: top left; /* Ensure scaling starts from top-left corner */
            }
        }
    </style>
@endassets
@script
        <script>
            $('#datepicker').datepicker({
                    format: 'yyyy-mm-dd',
                    weekStart: 1,
                    daysOfWeekHighlighted: "6,0",
                    autoclose: true,
                    todayHighlight: true,
            }).on('change', function (){
                var date = $(this).val();
                $wire.dispatch('date-set', {date: date});
            });
    
            $wire.on('date-changed', (event) => {
                $('#datepicker').datepicker('setDate', $wire.get('date'));
            });
        </script>
    @endscript
    