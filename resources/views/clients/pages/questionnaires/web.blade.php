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
        <div class="card-body">
            <div class="card-title mb-3 text-center">
                <h4>Web Design & Development Brief</h4>
            </div>

            <div class="row">
                <div class="col-md-12 form-group mb-3">
                    <label for="project_name">What is the name of the project or website? <span>*</span></label>
                    <input type="text" name="query[project_name]" id="project_name" class="form-control"
                        value="{{ old('query.project_name', $answers['project_name'] ?? '') }}" required>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="business_type">What type of business or service does the website represent?
                        <span>*</span></label>
                    <input type="text" name="query[business_type]" id="business_type" class="form-control"
                        value="{{ old('query.business_type', $answers['business_type'] ?? '') }}" required>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="website_purpose">What is the main purpose of the website? <span>*</span></label>
                    <textarea class="form-control" name="query[website_purpose]" id="website_purpose" rows="5" required>{{ old('query.website_purpose', $answers['website_purpose'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="features_required">What features and functionalities do you need? <span>*</span></label>
                    <textarea class="form-control" name="query[features_required]" id="features_required" rows="5" required>{{ old('query.features_required', $answers['features_required'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="design_style">Do you have a specific design style or theme in mind?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[design_style]" id="design_style" rows="5" required>{{ old('query.design_style', $answers['design_style'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="color_scheme">Do you have a preferred color scheme or brand guidelines?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[color_scheme]" id="color_scheme" rows="5" required>{{ old('query.color_scheme', $answers['color_scheme'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="examples">Are there any websites you like or would like us to use as inspiration?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[examples]" id="examples" rows="5" required>{{ old('query.examples', $answers['examples'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is your target audience? <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $answers['target_audience'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="mobile_responsive">Do you want the website to be mobile responsive?
                        <span>*</span></label>
                    <select class="form-control" name="query[mobile_responsive]" id="mobile_responsive" required>
                        <option value="yes"
                            {{ old('query.mobile_responsive', $answers['mobile_responsive'] ?? '') == 'yes' ? 'selected' : '' }}>
                            Yes</option>
                        <option value="no"
                            {{ old('query.mobile_responsive', $answers['mobile_responsive'] ?? '') == 'no' ? 'selected' : '' }}>
                            No</option>
                    </select>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="domain_hosting">Do you need help with domain registration and hosting setup?
                        <span>*</span></label>
                    <select class="form-control" name="query[domain_hosting]" id="domain_hosting" required>
                        <option value="yes"
                            {{ old('query.domain_hosting', $answers['domain_hosting'] ?? '') == 'yes' ? 'selected' : '' }}>
                            Yes</option>
                        <option value="no"
                            {{ old('query.domain_hosting', $answers['domain_hosting'] ?? '') == 'no' ? 'selected' : '' }}>
                            No</option>
                    </select>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Do you have a deadline for the project completion? <span>*</span></label>
                    <input type="date" name="query[deadline]" id="deadline" class="form-control"
                        value="{{ old('query.deadline', $answers['deadline'] ?? '') }}" required>
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
                    <textarea class="form-control" name="query[additional_instructions]" id="additional_instructions" rows="5">{{ old('query.additional_instructions', $answers['additional_instructions'] ?? '') }}</textarea>
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
    <div class="text-center mb-4">
        <button type="submit" class="btn btn-gradient w-25">Submit</button>
    </div>
</form>
