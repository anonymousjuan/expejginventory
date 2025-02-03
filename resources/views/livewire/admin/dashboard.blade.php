<div class="container-fluid">

    <!-- Page Heading -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-10">
                    <h1 class="h3 text-primary admin-title mb-0"><strong>Dashboard</strong></h1>
                </div>
            </div>

        </div>
    </div>
    <!-- Content Row -->
    <div class="row">
        @php
            $totalIncome = 0;
        @endphp
        <!-- Earnings (Monthly) Card Example -->
        @foreach($branches as $branch)
            @php
                $income = $this->getIncome($branch->id);
                $totalIncome = $totalIncome + $income;
            @endphp
        <div class="col-xl-4 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ $branch->name }} Daily Income</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">@money($income)</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

    </div>

    <div class="row">
        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Income (Daily)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">@money($totalIncome)</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->

    <div class="row">

        <!-- Tables -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Due Today</h6>
                    <div class="form-group">
                        <select class="form-control" id="supplier" wire:model.live="customerBranch">
                            <option selected value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                        @if (auth()->user()->branch_id == $branch->id) selected @endif>
                                    {{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    @hasanyrole('admin|manager')
                    {{-- <button wire:click="notifyDueToday()" type="button" class="btn btn-primary btn-sm">Notify</button> --}}
                    <button wire:click=# type="button" class="btn btn-primary btn-sm">Notify</button>
                    @endhasanyrole

                </div>
                <!-- Card Body -->
                <div class="card-body p-0 dashboard-table-section custom-scrollbar">
                    <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-light">
                          <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Balance</th>
                            <th scope="col">Status</th>
                          </tr>
                        </thead>
                        <tbody>
                        @forelse($customers as $customer)
                          <tr>
                            <td><a href="{{ route('admin.customer.details', ['customer' => $customer->id]) }}">{{ $customer->first_name }} {{ $customer->last_name }}</a></td>
                            <td>{{ $customer->phone }}</td>
                            <td>@money($customer->balance)</td>
                            <td>
                                @if($this->hasNotified($customer->id))
                                    <span class="badge badge-success">Notified</span>
                                @else
                                    <span class="badge badge-warning">Not Notified</span>
                                @endif
                            </td>                   
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">No customers found.</td>
                              </tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">List of Sold Items</h6>
                    <div class="form-group">
                        <select class="form-control" id="supplier" wire:model.live="itemBranch">
                            <option selected value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                        @if (auth()->user()->branch_id == $branch->id) selected @endif>
                                    {{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                </div>
                <!-- Card Body -->
                <div class="card-body p-0 dashboard-table-section custom-scrollbar">
                    <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-light">
                          <tr>
                            <th scope="col">Product ID</th>
                            <th scope="col">Product Name</th>
                            <th scope="col">Price</th>
                          </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $item)
                          <tr>
                            <td>{{ $item->sku_code }}-{{ $item->sku_number }}</td>
                            <td><a href="{{ route('admin.reports.daily.items', ['product' => $item->id]) }}">{{ $item->model }} {{ $item->description }}</a></td>
                            <td>@money($item->regular_price)</td>
                          </tr>
                        @empty
                            <tr>
                                <td colspan="10">No items found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        

    </div>

    <div class="row">

<!-- Tables -->
<div class="col-xl-12 col-lg-12">
    <div class="card shadow mb-4">
        <!-- Card Header - Dropdown -->
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Birthday Celebrants</h6>
            <div class="form-group">
                <select class="form-control" id="birthday-branch" wire:model.live="birthdayBranch">
                    <option selected value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"
                                @if (auth()->user()->branch_id == $branch->id) selected @endif>
                            {{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('birthdayBranch')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            @hasanyrole('admin|manager')
            {{-- <button type="button" wire:click="birthdayCelebrants()" class="btn btn-success btn-sm">Send Greetings!</button> --}}
            <button type="button" wire:click=# class="btn btn-success btn-sm">Send Greetings!</button>
            @endhasanyrole
        </div>
        <!-- Card Body -->
        <div class="card-body p-0 dashboard-table-section custom-scrollbar">
            <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-light">
                  <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Birthdate</th>
                    <th scope="col">Address</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($birthdays as $birthday)
                  <tr>
                    <td><a href="{{ route('admin.customer.details', ['customer' => $birthday->id]) }}">{{ $birthday->first_name }} {{ $birthday->last_name }}</a></td>
                    <td>{{ $birthday->phone }}</td>
                    <td>{{ $birthday->dob }}</td>
                    <td>{{ $birthday->address }}</td>
                  </tr>
                @empty
                    <tr>
                        <td colspan="10">No customers found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>






</div>
    

</div>
