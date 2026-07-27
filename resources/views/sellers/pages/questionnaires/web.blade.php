<form class="col-md-12 brief-form p-0" method="POST" action="{{ route('client.brief-form.post') }}"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="order_id" value="{{ $order->id }}">

    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title mb-3 text-center">
                <h4>Web Design & Development Brief</h4>
            </div>

            <div class="row">
                <div class="col-md-12 form-group mb-3">
                    <label for="project_name">What is the name of the project or website? <span>*</span></label>
                    <input type="text" name="query[project_name]" id="project_name" class="form-control"
                        value="{{ old('query.project_name', $brief->meta['project_name'] ?? '') }}" required>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="business_type">What type of business or service does the website represent?
                        <span>*</span></label>
                    <input type="text" name="query[business_type]" id="business_type" class="form-control"
                        value="{{ old('query.business_type', $brief->meta['business_type'] ?? '') }}" required>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="website_purpose">What is the main purpose of the website? <span>*</span></label>
                    <textarea class="form-control" name="query[website_purpose]" id="website_purpose" rows="5" required>{{ old('query.website_purpose', $brief->meta['website_purpose'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="features_required">What features and functionalities do you need? <span>*</span></label>
                    <textarea class="form-control" name="query[features_required]" id="features_required" rows="5" required>{{ old('query.features_required', $brief->meta['features_required'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="design_style">Do you have a specific design style or theme in mind?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[design_style]" id="design_style" rows="5" required>{{ old('query.design_style', $brief->meta['design_style'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="color_scheme">Do you have a preferred color scheme or brand guidelines?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[color_scheme]" id="color_scheme" rows="5" required>{{ old('query.color_scheme', $brief->meta['color_scheme'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="examples">Are there any websites you like or would like us to use as inspiration?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[examples]" id="examples" rows="5" required>{{ old('query.examples', $brief->meta['examples'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is your target audience? <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $brief->meta['target_audience'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="mobile_responsive">Do you want the website to be mobile responsive?
                        <span>*</span></label>
                    <select class="form-control" name="query[mobile_responsive]" id="mobile_responsive" required>
                        <option value="yes"
                            {{ old('query.mobile_responsive', $brief->meta['mobile_responsive'] ?? '') == 'yes' ? 'selected' : '' }}>
                            Yes</option>
                        <option value="no"
                            {{ old('query.mobile_responsive', $brief->meta['mobile_responsive'] ?? '') == 'no' ? 'selected' : '' }}>
                            No</option>
                    </select>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="domain_hosting">Do you need help with domain registration and hosting setup?
                        <span>*</span></label>
                    <select class="form-control" name="query[domain_hosting]" id="domain_hosting" required>
                        <option value="yes"
                            {{ old('query.domain_hosting', $brief->meta['domain_hosting'] ?? '') == 'yes' ? 'selected' : '' }}>
                            Yes</option>
                        <option value="no"
                            {{ old('query.domain_hosting', $brief->meta['domain_hosting'] ?? '') == 'no' ? 'selected' : '' }}>
                            No</option>
                    </select>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Do you have a deadline for the project completion? <span>*</span></label>
                    <input type="date" name="query[deadline]" id="deadline" class="form-control"
                        value="{{ old('query.deadline', $brief->meta['deadline'] ?? '') }}" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title mb-3">Additional Information</div>
            <div class="row">
                <div class="col-md-12 form-group mb-3">
                    <label for="additional_instructions">Any additional instructions or requirements for the
                        website?</label>
                    <textarea class="form-control" name="query[additional_instructions]" id="additional_instructions" rows="5">{{ old('query.additional_instructions', $brief->meta['additional_instructions'] ?? '') }}</textarea>
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
    <div class="text-center mb-4">
        <button type="submit" class="btn btn-gradient w-25">Submit</button>
    </div>
</form>

<!-- JS for clickable upload -->
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

    #existing-files-list li {
        margin-bottom: 5px;
    }
</style>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadCard = document.getElementById('upload-card');
        const fileInput = document.getElementById('attachment');

        if (uploadCard && fileInput) {
            uploadCard.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', function() {
                const files = Array.from(this.files).map(f => f.name).join(', ');
                if (files) {
                    uploadCard.querySelector('.text-muted').innerText = `Selected: ${files}`;
                } else {
                    uploadCard.querySelector('.text-muted').innerText =
                        'Click anywhere in this box to select files from your computer';
                }
            });
        }
    });
</script>

<style>
    .upload-card:hover {
        border: 2px dashed #007bff;
        background-color: #f8f9fa;
        transition: 0.3s ease;
    }
</style>
