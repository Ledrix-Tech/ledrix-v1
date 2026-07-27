@extends('clients.layouts.layout')

@section('title', 'Profile | Client Portal')

@section('client-content')
    <div class="crm-page-header">
        <div>
            <h1>Your profile</h1>
            <p>Update your contact details and password.</p>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body">
            <form action="{{ route('client.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <div class="col-lg-4 text-center">
                        <div class="client-profile-photo mb-3">
                            @if ($profile && $profile->profile)
                                <img src="{{ asset('uploads/profiles/' . $profile->profile) }}" id="client-profile-preview"
                                    alt="Profile photo">
                            @else
                                <img src="" id="client-profile-preview" alt="Profile photo" style="display:none">
                                <div class="crm-stat-icon mx-auto mb-3" style="width:120px;height:120px;font-size:3rem;border-radius:16px">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            @endif
                        </div>
                        <label class="client-profile-upload" for="client-profile-upload">
                            <i class="bi bi-cloud-upload"></i> Upload photo
                            <input id="client-profile-upload" type="file" name="profile" accept=".jpeg,.jpg,.png,.webp">
                        </label>
                    </div>

                    <div class="col-lg-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                    value="{{ old('first_name', $firstName ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                    value="{{ old('last_name', $lastName ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email"
                                    value="{{ old('email', $user->email ?? '') }}" disabled readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="alternate_email" class="form-label">Alternative email</label>
                                <input type="email" class="form-control" id="alternate_email" name="alternate_email"
                                    value="{{ old('alternate_email', $profile?->alternate_email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="{{ old('phone', $profile?->phone ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address"
                                    value="{{ old('address', $profile?->address ?? '') }}">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h2 class="h6 fw-bold mb-3">Change password</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">New password</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-crm-primary">
                                <i class="bi bi-check-lg me-1"></i> Save changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var input = document.getElementById('client-profile-upload');
            var preview = document.getElementById('client-profile-preview');
            var placeholder = document.querySelector('.client-profile-photo .crm-stat-icon');
            if (!input || !preview) return;
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush
