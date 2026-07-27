@extends('admin.layout.layout')

@section('title', 'Admin | Profile Details')

@section('admin-content')

    <style>
        .profile-left-parent.doted-border {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .upload-profile-pic {
            display: flex;
            background: black;
            align-items: center;
            justify-content: center;
            width: 60%;
            height: 50px;
            border-radius: 25px;
            cursor: pointer;
        }

        .large-profile {
            width: 280px;
            height: 350px;
            overflow: hidden;
            margin: 0 auto;
            border: 1px dotted red;
        }

        .large-profile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .custom-file-upload {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            width: 100%;
            height: 50px;
        }

        /* Chrome, Safari, Edge, Opera */
        .form-group ::placeholder {
            color: #999;
            opacity: 1;
        }
    </style>

    <div class="d-xl-flex justify-content-between align-items-start">
        <h1 class="fw-bold" style="color: #003C51;">Profile</h1>
    </div>

    <div class="row">
        <div class="col-md-12">
            <hr>
            <div class="tab-content tab-transparent-content">
                <div class="tab-pane fade show active" id="business-1" role="tabpanel" aria-labelledby="business-tab">
                    <div class="row">
                        <div class="col-xl-10 col-lg-10 col-sm-10 grid-margin stretch-card m-auto">
                            <div class="card rounded p-5">
                                <form action="{{ route('auth.profile.update') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="row align-items-center">
                                        <div class="col-lg-4">
                                            <div class="profile-left-parent doted-border text-center">
                                                <div class="large-profile mb-3">
                                                    @if (!$profile)
                                                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS-3afJuXe5nCNIn9j5xfMQZ4Ov3ObgCY9w4PjCXHJivkldezhENEs-W2L6sWvFiT-ae-w&usqp=CAU"
                                                            class="img-fluid" id="preview_image" alt="">
                                                    @else
                                                        <img src="{{ asset('uploads/profiles/' . $profile->profile) }}"
                                                            class="img-fluid" id="preview_image" alt="">
                                                    @endif
                                                </div>
                                                <div class="upload-profile-pic">
                                                    <label for="file-upload" class="custom-file-upload">
                                                        <div class="mx-2">
                                                            <i class="mdi mdi-upload text-info"></i>
                                                        </div>
                                                        <div class="mb-0">
                                                            Click to upload
                                                        </div>
                                                    </label>
                                                    <input id="file-upload" type="file" name="profile"
                                                        accept=".jpeg, .jpg, .png, .webp"
                                                        value="{{ old('profile', $profile->profile ?? $user->profile) }}"
                                                        hidden>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <div class="profile-right-parent">
                                                <div class="parent-profile-details">
                                                    <div class="row">
                                                        <!-- Left Inputs -->
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label for="first_name">First Name</label>
                                                                <input type="text" class="form-control" id="first_name"
                                                                    name="first_name"
                                                                    value="{{ old('first_name', $firstName) }}">
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="email">Email</label>
                                                                <input type="email" class="form-control" id="email"
                                                                    value="{{ old('email', $profile->email ?? $user->email) }}"
                                                                    disabled readonly>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="contact">Phone</label>
                                                                <input type="tel" class="form-control" id="contact"
                                                                    placeholder="+1 000 ***" name="phone"
                                                                    value="{{ old('phone', $profile->phone ?? '') }}">
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label for="last_name">Last Name</label>
                                                                <input type="text" class="form-control" id="last_name"
                                                                    name="last_name"
                                                                    value="{{ old('last_name', $lastName) }}">
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="alternate_email">Alternative Email</label>
                                                                <input type="email" class="form-control"
                                                                    id="alternate_email" name="alternate_email"
                                                                    value="{{ old('alternate_email', $profile->alternate_email ?? '') }}">
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="address">Address</label>
                                                                <input type="text" class="form-control" id="address"
                                                                    name="address"
                                                                    value="{{ old('address', $profile->address ?? '') }}">
                                                            </div>
                                                        </div>

                                                        <!-- Password Change -->
                                                        <div class="col-lg-6">
                                                            <h6>Change password</h6>
                                                            <div class="form-group">
                                                                <label for="password">New password</label>
                                                                <input type="password" class="form-control" id="password"
                                                                    name="password">
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-6">
                                                            <h6>&nbsp;</h6>
                                                            <div class="form-group">
                                                                <label for="confirm_password">Confirm password</label>
                                                                <input type="password" class="form-control"
                                                                    id="confirm_password" name="confirm_password">
                                                            </div>
                                                        </div>

                                                        <hr>
                                                        <div class="col-lg-2 m-auto">
                                                            <button type="submit"
                                                                class="btn btn-success custom-btn blue">
                                                                Save Changes
                                                            </button>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
