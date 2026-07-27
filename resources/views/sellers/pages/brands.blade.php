@extends('sellers.layout.layout')

@section('title', 'Seller | Brands')

@section('sellers-content')


    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <h1 class="fw-bold" style="color: #003C51;">Brands</h1>
                    <div class="d-flex">
                        @php
                            $loggedAdmin = Auth::guard('admin')->user();
                            $loggedSeller = Auth::guard('seller')->user();
                        @endphp
                        @if (isAdmin())
                            <button type="submit" class="btn bg-gradient-3" data-toggle="modal" data-target="#addBrand">
                                Add Brand
                            </button>
                        @endif

                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">
                <!-- Add table-responsive wrapper -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="text-white" style="background: #000;">
                            <th>#id</th>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Action</th>
                        </thead>
                        <tbody class="border">
                            @php
                                $isAdmin = Auth::guard('admin')->user();
                                $session = session()->get('role', 'Error');
                                $i = 1;
                            @endphp
                            @forelse ($brands as $brand)
                                <tr>
                                    <td data-label="#">{{ $i++ }}</td>
                                    <td data-label="Title :">{{ $brand->brand_name }}</td>
                                    <td data-label="Domain :"><a href="{{ $brand->brand_url }}"
                                            target="_blank">{{ Str::limit($brand->brand_url, 150) }}</a></td>
                                    <td>
                                        @if (isAdmin())
                                            <a href="javascript:void(0);" class="badge btn-sm deleteBrand"
                                                data-toggle="tooltip" title="Delete" data-id="{{ $brand->id }}">
                                                <i class="fa fa-trash text-danger" style="font-size: 20px;"></i>
                                            </a>
                                            @if ($brand->status === 'Pending')
                                                <a href="javascript:void(0);" class="badge badge-danger btn-sm actDomain"
                                                    data-toggle="tooltip" data-id="{{ $brand->id }}" data-status="Active"
                                                    title="Active">
                                                    {{ $brand->status }}
                                                </a>
                                            @else
                                                <a href="javascript:void(0);" class="badge badge-success btn-sm inActDomain"
                                                    data-toggle="tooltip" data-id="{{ $brand->id }}"
                                                    data-status="Pending" title="Pending">
                                                    {{ $brand->status }}
                                                </a>
                                            @endif
                                        @else
                                            <a href="javascript:void(0);" class="badge badge-success btn-sm"
                                                data-toggle="tooltip" data-status="Active" title="Active">
                                                {{ $brand->status }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12">
                                        <div class="alert alert-info m-0 text-center">
                                            <h6>You don't have any domains yet !!!</h6>
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
                        {{ $brands->links() }}
                        <div hidden>
                            @if ($brands->lastPage() > 1)
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $brands->currentPage() == 1 ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $brands->url($brands->currentPage() - 1) }}">Previous</a>
                                    </li>
                                    @for ($i = $brands->currentPage(); $i <= $brands->currentPage() + 8; $i++)
                                        <li class="page-item">
                                            <a class="page-link {{ $brands->currentPage() == $i ? ' border_active' : 'border_non_active' }} border_none2"
                                                href="{{ $brands->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor
                                    <li
                                        class="page-item {{ $brands->currentPage() == $brands->lastPage() ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $brands->url($brands->currentPage() + 1) }}">Next</a>
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
                    <form method="POST" action="{{ route('seller.brand.post') }}" class="leadform" id="form1">
                        @csrf
                        <div class="row">
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
                    url: "{{ route('seller.domain.updateStatus') }}",
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
                    url: "{{ route('seller.domain.delete') }}",
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
