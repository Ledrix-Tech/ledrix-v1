@php
    // $brief is meta array, $questionnair is model
    $meta = is_array($brief ?? null) ? $brief : $questionnair->meta ?? [];
    $answers = isset($meta['query']) && is_array($meta['query']) ? $meta['query'] : $meta;
@endphp

<form class="col-md-12 brief-form p-0" method="POST"
    action="{{ match ($mode ?? 'dashboard') {
        'token' => route('brief.submit', ['token' => $token]),
        default => route('client.brief-form.post'),
    } }}"
    enctype="multipart/form-data">
    @csrf

    {{-- dashboard needs order_id --}}
    @if (($mode ?? 'dashboard') !== 'token')
        <input type="hidden" name="order_id" value="{{ $order->id }}">
    @endif

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
                        value="{{ old('query.domain_name', $answers['domain_name'] ?? '') }}" required>
                </div>

                <!-- Domain Type -->
                <div class="col-md-12 form-group mb-3">
                    <label for="domain_type">What type of domain are you looking for? (e.g., .com, .net, .org, etc.)
                        <span>*</span></label>
                    <input type="text" name="query[domain_type]" class="form-control" id="domain_type"
                        value="{{ old('query.domain_type', $answers['domain_type'] ?? '') }}" required>
                </div>

                <!-- Hosting Plan -->
                <div class="col-md-12 form-group mb-3">
                    <label for="hosting_plan">What hosting plan are you interested in? <span>*</span></label>
                    <select name="query[hosting_plan]" class="form-control" id="hosting_plan" required>
                        @foreach (['Shared Hosting', 'VPS Hosting', 'Dedicated Hosting', 'Cloud Hosting'] as $plan)
                            <option value="{{ $plan }}"
                                {{ old('query.hosting_plan', $answers['hosting_plan'] ?? '') == $plan ? 'selected' : '' }}>
                                {{ $plan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Hosting Features -->
                <div class="col-md-12 form-group mb-3">
                    <label for="hosting_features">What features do you require with your hosting? (e.g., SSL, Email
                        Accounts, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[hosting_features]" id="hosting_features" rows="5" required>{{ old('query.hosting_features', $answers['hosting_features'] ?? '') }}</textarea>
                </div>

                <!-- Domain Ownership -->
                <div class="col-md-12 form-group mb-3">
                    <label for="domain_ownership">Do you already own this domain or would you like us to assist with the
                        purchase? <span>*</span></label>
                    <select name="query[domain_ownership]" class="form-control" id="domain_ownership" required>
                        @foreach (['Own Domain', 'Need Assistance'] as $option)
                            <option value="{{ $option }}"
                                {{ old('query.domain_ownership', $answers['domain_ownership'] ?? '') == $option ? 'selected' : '' }}>
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
                                {{ old('query.website_type', $answers['website_type'] ?? '') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Website Features -->
                <div class="col-md-12 form-group mb-3">
                    <label for="website_features">What specific features or functionalities would you like your website
                        to have? <span>*</span></label>
                    <textarea class="form-control" name="query[website_features]" id="website_features" rows="5" required>{{ old('query.website_features', $answers['website_features'] ?? '') }}</textarea>
                </div>

                <!-- Email Accounts -->
                <div class="col-md-12 form-group mb-3">
                    <label for="email_accounts">How many email accounts do you need for this domain?
                        <span>*</span></label>
                    <input type="number" name="query[email_accounts]" id="email_accounts" class="form-control"
                        value="{{ old('query.email_accounts', $answers['email_accounts'] ?? '') }}" required>
                </div>

                <!-- SSL Certificate -->
                <div class="col-md-12 form-group mb-3">
                    <label for="ssl_certificate">Would you like to add an SSL certificate to secure your website?
                        <span>*</span></label>
                    <select name="query[ssl_certificate]" class="form-control" id="ssl_certificate" required>
                        @foreach (['Yes', 'No'] as $option)
                            <option value="{{ $option }}"
                                {{ old('query.ssl_certificate', $answers['ssl_certificate'] ?? '') == $option ? 'selected' : '' }}>
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
                        value="{{ old('query.server_location', $answers['server_location'] ?? '') }}" required>
                </div>

                <!-- Other Requirements -->
                <div class="col-md-12 form-group mb-3">
                    <label for="other_requirements">Are there any other specific requirements or preferences for your
                        hosting? <span>*</span></label>
                    <textarea class="form-control" name="query[other_requirements]" id="other_requirements" rows="5" required>{{ old('query.other_requirements', $answers['other_requirements'] ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ATTACHMENT FIELD -->
    <div class="form-group mb-4" style="cursor: pointer;">
        <label class="text-muted mb-0">
            <strong>Upload Files <small>(Optional)</small></strong>
        </label>
        <input type="file" name="attachments[]" class="attachment-input form-control d-block" multiple>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Attach listener to all file inputs in brief forms
            document.querySelectorAll(".attachment-input").forEach(input => {
                input.addEventListener("change", function() {
                    const maxTotal = 25 * 1024 * 1024; // 25 MB limit
                    let totalSize = 0;
                    // Calculate total selected file size
                    for (const file of this.files) {
                        totalSize += file.size;
                        if (file.size > 10 * 1024 * 1024) {
                            alert(`${file.name} exceeds the 10 MB per-file limit.`);
                            this.value = "";
                            return;
                        }
                    }
                    // If total exceeds 25 MB → block immediately
                    if (totalSize > maxTotal) {
                        alert("⚠️ Total file size cannot exceed 25 MB. Please remove some files.");
                        this.value = ""; // clear files
                        return;
                    }

                    // Optional: display preview / filenames
                    const fileNames = Array.from(this.files).map(f => f.name).join(", ");
                    const label = this.closest(".form-group")?.querySelector("label.text-muted");
                    if (label) {
                        label.innerHTML = fileNames ?
                            `<strong>Selected:</strong> ${fileNames}` :
                            "<strong>Upload Files <small>(Optional)</small></strong>";
                    }
                });
            });
        });
    </script>

    <hr>
    <div class="imgBx p-3">
        @include('partials.brief-attachments', [
            'attachments' => $meta['attachments'] ?? [],
            'mode' => $mode ?? 'dashboard',
        ])
    </div>

    <!-- Submit -->
    <div class="text-center mb-4">
        <button type="submit" class="btn btn-gradient w-25">Submit</button>
    </div>
</form>
