@extends('sellers.layout.layout')

@section('title', 'Seller | Sellers')

@section('sellers-content')



    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <h1 class="fw-bold" style="color: #003C51;">Executives</h1>
                    @if (isAdmin() || isFrontSeller())
                        <div class="d-flex">
                            <div class="d-flex">
                                <button type="submit" class="btn bg-gradient-3" data-toggle="modal"
                                    data-target="#addExecutive">Add Seller</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="text-white" style="background: #000;">
                            <th>#id</th>
                            <th>Name</th>
                            <th>Suedo</th>
                            <th>Email</th>
                            <th>Brand</th>
                            <th>Role</th>
                            {{-- <th>Domain Status</th> --}}
                            <th>Action</th>
                        </thead>
                        <tbody class="border">
                            @php
                                $isAdmin = Auth::guard('admin')->user();
                                $session = session()->get('role', 'Error');
                                $i = 1;
                            @endphp
                            @forelse ($sellers as $seller)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>
                                        @if (isAdmin())
                                            <a href="{{ route('seller.seller-performance.get', $seller->id) }}"
                                                style="color: #b84b19 !important;">
                                                {{ $seller->name }}
                                            </a>
                                        @else
                                            <a href="{{ route('seller.seller-performance.get', $seller->id) }}"
                                                style="color: #1971b8 !important;">
                                                {{ $seller->name }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ $seller->sudo_name }}</td>
                                    <td>{{ $seller->email }}</td>
                                    <td>
                                        @if ($seller->brand)
                                            <a href="{{ $seller->brand->brand_url }}" target="_blank">
                                                {{ $seller->brand->brand_name }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($seller->is_seller)
                                            <h6 class="text-danger">{{ Str::headline($seller->is_seller) }}</h6>
                                        @endif
                                    </td>
                                    <td hidden>
                                        <form method="POST" action="{{ route('seller.seller.changeDomain') }}"
                                            class="domainForm" id="form1" onchange="submit()">
                                            @csrf
                                            <input type="hidden" name="seller_id" value="{{ $seller->id }}">
                                            <div class="form-group mb-3">
                                                <select name="brand_id" class="form-control">
                                                    <option value="">-- select brand --</option>
                                                    @foreach ($brands as $item)
                                                        <option value="{{ $item->id }}"
                                                            {{ $seller->brand_id == $item->id ? 'selected' : '' }}>
                                                            {{ $item->brand_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        @if (isAdmin())
                                            @if ($seller->status === 'Active')
                                                <a href="javascript:void(0);" class="badge badge-success btn-sm banUser"
                                                    data-toggle="tooltip" data-id="{{ $seller->id }}"
                                                    data-status="Inactive" title="Inactive">
                                                    {{ $seller->status }}
                                                </a>
                                            @else
                                                <a href="javascript:void(0);" class="badge badge-danger btn-sm unbanUser"
                                                    data-toggle="tooltip" data-id="{{ $seller->id }}"
                                                    data-status="Active" title="Active">
                                                    {{ $seller->status }}
                                                </a>
                                            @endif
                                        @else
                                            @if ($seller->status === 'Active')
                                                <a href="javascript:void(0);" class="badge badge-success btn-sm"
                                                    data-toggle="tooltip" data-id="{{ $seller->id }}"
                                                    data-status="Inactive"
                                                    title="{{ $seller->status }}">{{ $seller->status }}
                                                </a>
                                            @else
                                                <a href="javascript:void(0);" class="badge badge-danger btn-sm"
                                                    data-toggle="tooltip" data-id="{{ $seller->id }}"
                                                    data-status="Active" title="{{ $seller->status }}">
                                                    {{ $seller->status }}
                                                </a>
                                            @endif
                                        @endif

                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12">
                                        <div class="alert alert-info m-0 text-center">
                                            <h6>You don't have any sellers yet !!!</h6>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="paginate d-flex justify-content-center align-item-center bg-light p-2"
                    style="border-radius:10px;">
                    <div class="text-dark pt-3">
                        {{ $sellers->links() }}
                        <div hidden>
                            @if ($sellers->lastPage() > 1)
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $sellers->currentPage() == 1 ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $sellers->url($sellers->currentPage() - 1) }}">Previous</a>
                                    </li>
                                    @for ($i = $sellers->currentPage(); $i <= $sellers->currentPage() + 8; $i++)
                                        <li class="page-item">
                                            <a class="page-link {{ $sellers->currentPage() == $i ? ' border_active' : 'border_non_active' }} border_none2"
                                                href="{{ $sellers->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor
                                    <li
                                        class="page-item {{ $sellers->currentPage() == $sellers->lastPage() ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $sellers->url($sellers->currentPage() + 1) }}">Next</a>
                                    </li>
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="addExecutive" data-backdrop="true" data-keyboard="true" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Seller Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('seller.seller.post') }}" class="leadform" id="form1">
                        @csrf
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3">
                                    <select name="brand_id" id="" class="form-control">
                                        <option value="">-- select brand --</option>
                                        @foreach ($brands as $item)
                                            <option value="{{ $item->id }}">{{ $item->brand_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3">
                                    <select name="is_seller" id="" class="form-control">
                                        <option value="">-- select role --</option>
                                        <option value="front_seller">Front Seller</option>
                                        <option value="project_manager">Project Manager</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3">
                                    <input type="text" name="name" placeholder="Enter seller name..."
                                        class="form-control" required="required">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3">
                                    <input type="text" name="sudo_name" placeholder="Enter sudo name..."
                                        class="form-control" required="required">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3">
                                    <input type="email" name="email" placeholder="Enter email..."
                                        class="form-control" required="required">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3 d-flex align-items-center">
                                    <input type="password" minlength="6" maxlength="12" name="password" id="password"
                                        placeholder="Enter password..." class="form-control" required>
                                    <button type="button" class="btn btn-secondary ms-2"
                                        onclick="generatePassword()">Generate</button>
                                    <button type="button" class="btn btn-outline-info ms-2"
                                        onclick="togglePassword()">👁</button>
                                </div>
                            </div>
                            <script>
                                function generatePassword() {
                                    const length = 10;
                                    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
                                    let password = "";

                                    for (let i = 0; i < length; i++) {
                                        password += charset.charAt(Math.floor(Math.random() * charset.length));
                                    }
                                    document.getElementById("password").value = password;
                                }

                                function togglePassword() {
                                    const field = document.getElementById("password");
                                    field.type = field.type === "password" ? "text" : "password";
                                }
                            </script>

                            <hr>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="d-flex align-items-center justify-content-center text-center m-auto">
                                    <button class="btn btn-success text-white">Submit</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).on("click", ".banUser, .unbanUser", function() {
            let userId = $(this).data("id");
            let newStatus = $(this).data("status");
            let actionText = newStatus;

            if (confirm(`Are you sure you want to ${actionText} this user?`)) {
                $.ajax({
                    url: "{{ route('seller.seller.updateStatus') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        user_id: userId,
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(`User successfully ${actionText}!`);
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            toastr.error("Error updating user status.");
                        }
                    },
                    error: function() {
                        toastr.error("An error occurred. Please try again.");
                    }
                });
            }
        });
    </script>

    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="/js/app.js"></script> <!-- Laravel Echo setup -->

    <script>
        // Ask for permission
        Notification.requestPermission();

        // Replace with current logged in user ID
        const userId = {{ auth()->id() }};

        // Laravel Echo setup
        import Echo from "laravel-echo"
        window.Pusher = require('pusher-js');

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: "{{ config('broadcasting.connections.pusher.key') }}",
            cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
            forceTLS: true
        });

        // Listen for event on private channel
        window.Echo.private(`user.${userId}`)
            .listen('LeadAssigned', (e) => {
                console.log("Lead assigned:", e);

                if (Notification.permission === "granted") {
                    new Notification("🎯 New Lead Assigned", {
                        body: `${e.name} (${e.email}) has been assigned to you.`,
                    });
                }
            });
    </script>

@endsection
