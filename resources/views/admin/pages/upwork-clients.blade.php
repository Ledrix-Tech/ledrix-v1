@extends('admin.layout.layout')

@section('title', 'Admin | Upwork Clients')

@section('admin-content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <div>
                        <h1 class="fw-bold" style="color: #003C51;">Clients</h1>
                    </div>
                    <div class="examplesearch-form mx-3">
                        <form action="" method="" class="example">
                            <div class="d-flex">
                                <input type="text" placeholder="Search.." value="" name="search"
                                    class="form-control">
                                <button type="submit" class="btn text-white bg-gradient-3"><i
                                        class="fa fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5">
            <div class="col-lg-12">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class=" text-white" style="background: #000;">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Created At</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="border text-center">
                            @if ($clients->isEmpty())
                                <tr>
                                    <td colspan="6">
                                        <div class="alert alert-info m-0">
                                            <h6>You don't have any clients assigned yet !!!</h6>
                                        </div>
                                    </td>
                                </tr>
                            @else
                        <tbody>
                            @php
                                $isAdmin = Auth::guard('admin')->user();
                                $session = session()->get('role', 'Error');
                                $i = 1;
                            @endphp
                            @foreach ($clients as $client)
                                <tr>
                                    <td>{{ $client->id }}</td>
                                    <td>{{ $client->name }}</td>
                                    <td>{{ $client->email }}</td>
                                    <td>{{ $client->phone }}</td>
                                    <td>{{ $client->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        @if ($client->status === 'Active')
                                            <a href="javascript:void(0);" class="badge badge-success btn-sm"
                                                data-toggle="tooltip" data-id="{{ $client->id }}" data-status="Inactive"
                                                title="Inactive">
                                                {{ $client->status }}
                                            </a>
                                        @else
                                            <a href="javascript:void(0);" class="badge badge-danger btn-sm"
                                                data-toggle="tooltip" data-id="{{ $client->id }}" data-status="Active"
                                                title="Active">
                                                {{ $client->status }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @endif
                    </table>
                </div>
                <div class="paginate d-flex justify-content-center align-item-center bg-light p-2"
                    style="border-radius:10px;">
                    <div class="text-dark pt-3">
                        {{ $clients->links() }}
                        <div hidden>
                            @if ($clients->lastPage() > 1)
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $clients->currentPage() == 1 ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $clients->url($clients->currentPage() - 1) }}">Previous</a>
                                    </li>
                                    @for ($i = $clients->currentPage(); $i <= $clients->currentPage() + 8; $i++)
                                        <li class="page-item">
                                            <a class="page-link {{ $clients->currentPage() == $i ? ' border_active' : 'border_non_active' }} border_none2"
                                                href="{{ $clients->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor
                                    <li
                                        class="page-item {{ $clients->currentPage() == $clients->lastPage() ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $clients->url($clients->currentPage() + 1) }}">Next</a>
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
                    url: "", // Ensure the route exists
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
