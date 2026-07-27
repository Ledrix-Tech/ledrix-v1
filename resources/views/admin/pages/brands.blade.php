@extends('admin.layout.layout')

@section('title', 'Admin | Domains')

@section('admin-content')


    <div class="crm-page-header">
        <div>
            <h1>Brands</h1>
            <p>Manage brand domains and modules</p>
        </div>
        @if (isAdmin() || isFrontSeller())
            <button type="button" class="btn btn-crm-teal" data-toggle="modal" data-target="#addBrand">
                <i class="bi bi-plus-lg me-1"></i> Add Brand
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
                            <th>Module</th>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = 1; @endphp
                        @forelse ($brands as $brand)
                            <tr>
                                <td data-label="#">{{ $i++ }}</td>
                                <td data-label="Module"><span class="crm-status crm-status-info">{{ strtoupper($brand->module ?? '—') }}</span></td>
                                <td data-label="Name"><strong>{{ $brand->brand_name ?? '—' }}</strong></td>
                                <td data-label="URL">
                                    <a href="{{ $brand->brand_url }}" target="_blank">{{ Str::limit($brand->brand_url, 50) }}</a>
                                </td>
                                <td data-label="Actions">
                                    <div class="crm-action-group">
                                        @if (isAdmin())
                                            <a href="javascript:void(0);" class="crm-icon-btn danger deleteBrand"
                                                data-toggle="tooltip" title="Delete" data-id="{{ $brand->id }}">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                            @if ($brand->status === 'Pending')
                                                <a href="javascript:void(0);" class="crm-status crm-status-danger actDomain"
                                                    data-id="{{ $brand->id }}" data-status="Active">{{ $brand->status }}</a>
                                            @else
                                                <a href="javascript:void(0);" class="crm-status crm-status-success inActDomain"
                                                    data-id="{{ $brand->id }}" data-status="Pending">{{ $brand->status }}</a>
                                            @endif
                                        @else
                                            <span class="crm-status crm-status-success">{{ $brand->status }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="crm-empty">
                                        <i class="bi bi-globe d-block"></i>
                                        No brands yet. Add your first brand.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($brands->hasPages())
                <div class="crm-pagination">{{ $brands->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addBrand" data-backdrop="true" data-keyboard="true" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Brand Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('admin.brand.post') }}" class="leadform" id="form1">
                        @csrf
                        <div class="row">
                            <!-- Brand (Domain) Select -->
                            <div class="col-lg-12 mb-3">
                                <select name="module" class="form-control">
                                    <option selected disabled>-- select model --</option>
                                    <option value="ppc">
                                        PPC
                                    </option>
                                    <option value="upwork">
                                        Upwork
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3">
                                    <input type="text" name="brand_name" placeholder="Enter brand name..."
                                        class="form-control" required="required">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group mb-3">
                                    <input type="url" name="brand_url" placeholder="Enter url..." class="form-control"
                                        required="required">
                                </div>
                            </div>
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
        $(document).on("click", ".actDomain, .inActDomain", function() {
            let domainId = $(this).data("id");
            let newStatus = $(this).data("status");

            let actionText = newStatus;

            if (confirm(`Are you sure you want to ${actionText} this Domain?`)) {
                $.ajax({
                    url: "{{ route('admin.domain.updateStatus') }}", // Ensure the route exists
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        domain_id: domainId,
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(`Domain successfully ${actionText}!`);
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            toastr.error("Error updating domain status.");
                        }
                    },
                    error: function() {
                        toastr.error("An error occurred. Please try again.");
                    }
                });
            }
        });

        $(document).on("click", ".deleteBrand", function() {
            let brandId = $(this).data("id");

            if (confirm("Are you sure you want to delete this Domain?")) {
                $.ajax({
                    url: "{{ route('admin.domain.delete') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        domain_id: brandId
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success("Domain deleted successfully!");
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            toastr.error("Error deleting Domain.");
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
