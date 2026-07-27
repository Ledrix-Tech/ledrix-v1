@extends('admin.layout.layout')

@section('title', 'Admin | Executives')

@section('admin-content')



    <div class="crm-page-header">
        <div>
            <h1>Sellers</h1>
            <p>Manage your sales team and assignments</p>
        </div>
        @if (isAdmin() || isFrontSeller())
            <button type="button" class="btn btn-crm-teal" data-toggle="modal" data-target="#addExecutive">
                <i class="bi bi-person-plus me-1"></i> Add Seller
            </button>
        @endif
    </div>

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Pseudo</th>
                            <th>Email</th>
                            <th>Brand</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = 1; @endphp
                        @foreach ($sellers as $seller)
                            <tr>
                                <td data-label="#">{{ $i++ }}</td>
                                <td data-label="Name">
                                    <a href="{{ route('admin.seller-performance.get', $seller->id) }}" class="fw-semibold">
                                        {{ $seller->name }}
                                    </a>
                                </td>
                                <td data-label="Pseudo">{{ $seller->sudo_name }}</td>
                                <td data-label="Email">{{ $seller->email }}</td>
                                <td data-label="Brand">
                                    @if ($seller->brand)
                                        <a href="{{ $seller->brand->brand_url }}" target="_blank">{{ $seller->brand->brand_name }}</a>
                                    @else — @endif
                                </td>
                                <td data-label="Role">
                                    @if ($seller->is_seller)
                                        <span class="crm-status crm-status-info">{{ Str::headline($seller->is_seller) }}</span>
                                    @endif
                                </td>
                                <td data-label="Actions">
                                    <div class="crm-action-group">
                                        @if (isAdmin())
                                            @if ($seller->status === 'Active')
                                                <a href="javascript:void(0);" class="crm-status crm-status-success banUser"
                                                    data-id="{{ $seller->id }}" data-status="Inactive">{{ $seller->status }}</a>
                                            @else
                                                <a href="javascript:void(0);" class="crm-status crm-status-danger unbanUser"
                                                    data-id="{{ $seller->id }}" data-status="Active">{{ $seller->status }}</a>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($sellers->hasPages())
                <div class="crm-pagination">{{ $sellers->links() }}</div>
            @endif
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
                    <form method="POST" action="{{ route('admin.seller.post') }}" class="leadform" id="form1">
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
                    url: "{{ route('admin.seller.updateStatus') }}", // Ensure the route exists
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
