@extends('admin.layout.layout')

@section('title', 'Admin | Clients')

@section('admin-content')

    <div class="crm-page-header">
        <div>
            <h1>Clients</h1>
            <p>Manage client accounts and portal access</p>
        </div>
        <div class="crm-page-actions">
            @if (isAdmin())
                <a href="{{ route('export.csv', ['table' => 'clients', 'columns' => 'id,name,email,phone,created_at']) }}"
                    class="btn btn-sm btn-crm-teal">
                    <i class="fa fa-file-excel-o me-1"></i> Export CSV
                </a>
            @endif
            <form action="{{ route('admin.clients.get') }}" method="GET" class="crm-search-bar mb-0">
                <input type="text" placeholder="Search clients..." value="{{ $search ?? '' }}" name="search"
                    class="form-control">
                <button type="submit" class="btn btn-crm-teal"><i class="fa fa-search"></i></button>
            </form>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created</th>
                            <th>Account</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($clients->isEmpty())
                            <tr>
                                <td colspan="7">
                                    <div class="crm-empty">
                                        <i class="bi bi-people d-block"></i>
                                        No clients assigned yet.
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach ($clients as $client)
                                <tr>
                                    <td data-label="#">{{ $client->id }}</td>
                                    <td data-label="Name"><strong>{{ $client->name }}</strong></td>
                                    <td data-label="Email">{{ $client->email }}</td>
                                    <td data-label="Phone">{{ $client->phone }}</td>
                                    <td data-label="Created">{{ $client->created_at->format('M d, Y') }}</td>
                                    <td data-label="Account">
                                        <button type="button"
                                            class="btn btn-sm {{ $client->hasPortalAccess() ? 'btn-crm-green' : 'btn-crm-outline' }}"
                                            data-toggle="modal" data-target="#addClientAccess"
                                            data-id="{{ $client->id }}"
                                            data-action="{{ $client->hasPortalAccess() ? 'update' : 'add' }}">
                                            {{ $client->hasPortalAccess() ? 'Update Password' : 'Add Account' }}
                                        </button>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="crm-action-group">
                                            @if (isAdmin() || isFrontSeller())
                                                <a href="javascript:void(0);" class="crm-icon-btn danger deleteUser"
                                                    data-id="{{ $client->id }}" title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                                @if ($client->status === 'Active')
                                                    <a href="javascript:void(0);"
                                                        class="crm-status crm-status-success banUser"
                                                        data-toggle="tooltip" data-id="{{ $client->id }}"
                                                        data-status="Inactive">{{ $client->status }}</a>
                                                @else
                                                    <a href="javascript:void(0);"
                                                        class="crm-status crm-status-danger unbanUser"
                                                        data-toggle="tooltip" data-id="{{ $client->id }}"
                                                        data-status="Active">{{ $client->status }}</a>
                                                @endif
                                            @else
                                                <span class="crm-status {{ $client->status === 'Active' ? 'crm-status-success' : 'crm-status-danger' }}">
                                                    {{ $client->status }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            @if ($clients->hasPages())
                <div class="crm-pagination">{{ $clients->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addClientAccess" data-backdrop="true" data-keyboard="true" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Account Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('client.account-access') }}" class="leadform" id="form1">
                        @csrf
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <input type="hidden" name="client_id" class="form-control">
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script>
        $(document).on("click", ".banUser, .unbanUser", function() {
            let userId = $(this).data("id");
            let newStatus = $(this).data("status");

            let actionText = newStatus;

            if (confirm(`Are you sure you want to ${actionText} this user?`)) {
                $.ajax({
                    url: "{{ route('admin.client.updateStatus') }}", // Ensure the route exists
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


        $(document).on('click', '[data-target="#addClientAccess"]', function() {
            let leadId = $(this).data('id'); // get lead id from button
            $('input[name="client_id"]').val(leadId); // set into hidden input
        });

        $(document).on("click", ".deleteUser", function() {
            let userId = $(this).data("id");

            console.log('Client id', userId);

            if (confirm("Are you sure you want to delete this user?")) {
                $.ajax({
                    url: "{{ route('admin.client.delete') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        user_id: userId
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success("Client Account deleted successfully!");
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            toastr.error("Error deleting account.");
                        }
                    },
                    error: function() {
                        toastr.error("An error occurred. Please try again.");
                    }
                });
            }
        });
    </script>

@endsection
