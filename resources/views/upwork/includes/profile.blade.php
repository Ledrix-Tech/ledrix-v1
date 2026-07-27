@push('admin-links')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
@endpush

<!-- Modal -->
<div class="modal fade" id="profile" data-backdrop="static" data-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Update Profile</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="seform">
                    <form action="{{ route('update.profile', Auth::user()->id) }}" method="post" enctype="">
                        @csrf
                        <div class="form-group my-2">
                            <input type="file" name="profile" class="form-control">
                            <div style="width:60px; height:60px; background:rgb(145, 148, 145); border-radius:50%;">
                                <img src="{{ url('uploades/users/' . Auth::user()->profile) }}" alt=""
                                    class="w-100">
                            </div>
                        </div>
                        <div class="form-group my-2">
                            <input type="text" name="name" value="{{ Auth::user()->name }}" class="form-control"
                                placeholder="Enter your name..">
                        </div>
                        <div class="form-group my-2">
                            <input type="email" name="email" value="{{ Auth::user()->email }}" class="form-control"
                                placeholder="Enter your email..">
                        </div>
                        <div class="form-group my-2">
                            <input type="number" name="zip" value="{{ Auth::user()->zip }}" class="form-control"
                                placeholder="Enter your zip...">
                        </div>
                </div>
                <button type="submit" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>
