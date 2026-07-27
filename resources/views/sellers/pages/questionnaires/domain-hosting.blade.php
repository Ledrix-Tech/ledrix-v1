<form class="col-md-12 brief-form p-0" method="POST" action="{{ route('client.brief-form.post') }}"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="order_id" value="{{ $order->id }}">

    <div class="card mb-4">
        <div class="card-body mb-4">
            <div class="card-title mb-3 text-center">
                <h4>Domain & Hosting Brief</h4>
            </div>

            <div class="row">
                <!-- Domain Name -->
                <div class="col-md-12 form-group mb-3">
                    <label for="domain_name">What domain name do you want to register? <span>*</span></label>
                    <input type="text" name="query[domain_name]" class="form-control" id="domain_name"
                        value="{{ old('query.domain_name', $brief->meta['domain_name'] ?? '') }}" required>
                </div>

                <!-- Domain Type -->
                <div class="col-md-12 form-group mb-3">
                    <label for="domain_type">What type of domain are you looking for? (e.g., .com, .net, .org, etc.)
                        <span>*</span></label>
                    <input type="text" name="query[domain_type]" class="form-control" id="domain_type"
                        value="{{ old('query.domain_type', $brief->meta['domain_type'] ?? '') }}" required>
                </div>

                <!-- Hosting Plan -->
                <div class="col-md-12 form-group mb-3">
                    <label for="hosting_plan">What hosting plan are you interested in? <span>*</span></label>
                    <select name="query[hosting_plan]" class="form-control" id="hosting_plan" required>
                        @foreach (['Shared Hosting', 'VPS Hosting', 'Dedicated Hosting', 'Cloud Hosting'] as $plan)
                            <option value="{{ $plan }}"
                                {{ old('query.hosting_plan', $brief->meta['hosting_plan'] ?? '') == $plan ? 'selected' : '' }}>
                                {{ $plan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Hosting Features -->
                <div class="col-md-12 form-group mb-3">
                    <label for="hosting_features">What features do you require with your hosting? (e.g., SSL, Email
                        Accounts, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[hosting_features]" id="hosting_features" rows="5" required>{{ old('query.hosting_features', $brief->meta['hosting_features'] ?? '') }}</textarea>
                </div>

                <!-- Domain Ownership -->
                <div class="col-md-12 form-group mb-3">
                    <label for="domain_ownership">Do you already own this domain or would you like us to assist with the
                        purchase? <span>*</span></label>
                    <select name="query[domain_ownership]" class="form-control" id="domain_ownership" required>
                        @foreach (['Own Domain', 'Need Assistance'] as $option)
                            <option value="{{ $option }}"
                                {{ old('query.domain_ownership', $brief->meta['domain_ownership'] ?? '') == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Website Type -->
                <div class="col-md-12 form-group mb-3">
                    <label for="website_type">What type of website will this domain host? <span>*</span></label>
                    <select name="query[website_type]" class="form-control" id="website_type" required>
                        @foreach (['Business', 'E-commerce', 'Blog', 'Portfolio', 'Other'] as $type)
                            <option value="{{ $type }}"
                                {{ old('query.website_type', $brief->meta['website_type'] ?? '') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Website Features -->
                <div class="col-md-12 form-group mb-3">
                    <label for="website_features">What specific features or functionalities would you like your website
                        to have? <span>*</span></label>
                    <textarea class="form-control" name="query[website_features]" id="website_features" rows="5" required>{{ old('query.website_features', $brief->meta['website_features'] ?? '') }}</textarea>
                </div>

                <!-- Email Accounts -->
                <div class="col-md-12 form-group mb-3">
                    <label for="email_accounts">How many email accounts do you need for this domain?
                        <span>*</span></label>
                    <input type="number" name="query[email_accounts]" id="email_accounts" class="form-control"
                        value="{{ old('query.email_accounts', $brief->meta['email_accounts'] ?? '') }}" required>
                </div>

                <!-- SSL Certificate -->
                <div class="col-md-12 form-group mb-3">
                    <label for="ssl_certificate">Would you like to add an SSL certificate to secure your website?
                        <span>*</span></label>
                    <select name="query[ssl_certificate]" class="form-control" id="ssl_certificate" required>
                        @foreach (['Yes', 'No'] as $option)
                            <option value="{{ $option }}"
                                {{ old('query.ssl_certificate', $brief->meta['ssl_certificate'] ?? '') == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Server Location -->
                <div class="col-md-12 form-group mb-3">
                    <label for="server_location">Do you have any preference for the location of the hosting server?
                        (e.g., USA, Europe, Asia) <span>*</span></label>
                    <input type="text" name="query[server_location]" id="server_location" class="form-control"
                        value="{{ old('query.server_location', $brief->meta['server_location'] ?? '') }}" required>
                </div>

                <!-- Other Requirements -->
                <div class="col-md-12 form-group mb-3">
                    <label for="other_requirements">Are there any other specific requirements or preferences for your
                        hosting? <span>*</span></label>
                    <textarea class="form-control" name="query[other_requirements]" id="other_requirements" rows="5" required>{{ old('query.other_requirements', $brief->meta['other_requirements'] ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ATTACHMENT FIELD -->
    <div class="card mb-4 upload-card" style="cursor: pointer;">
        <div class="card-body text-center" id="upload-card">
            <div class="card-title mb-2">
                <strong>Upload Files (Optional)</strong>
            </div>
            <p class="text-muted mb-0">Click anywhere in this box to select files from your computer</p>
            <input type="file" name="attachments[]" id="attachment" class="d-none" multiple>
        </div>
        <hr>
        <div class="imgBx p-3">
            <!-- Prefilled attachments -->
            @if (!empty($brief->meta['attachments']))
                <div class="mt-3 text-start">
                    <strong>Previously Uploaded Files:</strong>
                    <ul id="existing-files-list">
                        @foreach ($brief->meta['attachments'] as $file)
                            <li>
                                <a href="{{ asset($file) }}" target="_blank">{{ basename($file) }}</a>
                                <label class="text-danger ms-2" style="cursor:pointer;">
                                    <input type="checkbox" name="remove_attachments[]" value="{{ $file }}">
                                    Remove
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <!-- Submit -->
    <div class="text-center mb-4">
        <button type="submit" class="btn btn-gradient w-25">Submit</button>
    </div>
</form>

<!-- JS for Clickable File Upload -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadCard = document.getElementById('upload-card');
        const fileInput = document.getElementById('attachment');

        uploadCard.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', function() {
            const files = Array.from(this.files).map(f => f.name).join(', ');
            if (files) {
                uploadCard.querySelector('.text-muted').innerText = `Selected: ${files}`;
            }
        });
    });
</script>

<style>
    .upload-card:hover {
        border: 2px dashed #007bff;
        background-color: #f8f9fa;
        transition: 0.3s ease;
    }
</style>
