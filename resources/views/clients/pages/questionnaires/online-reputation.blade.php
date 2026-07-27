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
                <h4>Online Reputation Management Brief</h4>
            </div>

            <div class="row">
                <!-- Business Name -->
                <div class="col-md-12 form-group mb-3">
                    <label for="business_name">What is the name of your business? <span>*</span></label>
                    <input type="text" name="query[business_name]" id="business_name" class="form-control"
                        value="{{ old('query.business_name', $answers['business_name'] ?? '') }}" required>
                </div>

                <!-- Industry -->
                <div class="col-md-12 form-group mb-3">
                    <label for="industry">What industry does your business belong to? <span>*</span></label>
                    <input type="text" name="query[industry]" id="industry" class="form-control"
                        value="{{ old('query.industry', $answers['industry'] ?? '') }}" required>
                </div>

                <!-- Current Reputation -->
                <div class="col-md-12 form-group mb-3">
                    <label for="current_reputation">How would you describe your current online reputation?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[current_reputation]" id="current_reputation" rows="5" required>{{ old('query.current_reputation', $answers['current_reputation'] ?? '') }}</textarea>
                </div>

                <!-- Goals -->
                <div class="col-md-12 form-group mb-3">
                    <label for="reputation_goals">What are your specific goals regarding online reputation management?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[reputation_goals]" id="reputation_goals" rows="5" required>{{ old('query.reputation_goals', $answers['reputation_goals'] ?? '') }}</textarea>
                </div>

                <!-- Platforms -->
                <div class="col-md-12 form-group mb-3">
                    <label for="platforms">Which platforms or websites do you want us to focus on? (e.g., Google
                        Reviews, Yelp, Social Media, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[platforms]" id="platforms" rows="5" required>{{ old('query.platforms', $answers['platforms'] ?? '') }}</textarea>
                </div>

                <!-- Negative Reviews -->
                <div class="col-md-12 form-group mb-3">
                    <label for="negative_reviews">Are there any specific negative reviews or comments you'd like us to
                        address? <span>*</span></label>
                    <textarea class="form-control" name="query[negative_reviews]" id="negative_reviews" rows="5" required>{{ old('query.negative_reviews', $answers['negative_reviews'] ?? '') }}</textarea>
                </div>

                <!-- Existing Reputation Management -->
                <div class="col-md-12 form-group mb-3">
                    <label for="existing_reputation_management">Do you currently have any reputation management
                        strategies or services in place? <span>*</span></label>
                    <textarea class="form-control" name="query[existing_reputation_management]" id="existing_reputation_management"
                        rows="5" required>{{ old('query.existing_reputation_management', $answers['existing_reputation_management'] ?? '') }}</textarea>
                </div>

                <!-- Competitors -->
                <div class="col-md-12 form-group mb-3">
                    <label for="competitors">Do you have any competitors whose online reputation strategies you are
                        concerned about? <span>*</span></label>
                    <textarea class="form-control" name="query[competitors]" id="competitors" rows="5" required>{{ old('query.competitors', $answers['competitors'] ?? '') }}</textarea>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">What is your target audience? (e.g., local customers, national
                        audience, specific demographics) <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $answers['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Brand Voice -->
                <div class="col-md-12 form-group mb-3">
                    <label for="brand_voice">What is your preferred brand voice for online interactions? (e.g., formal,
                        casual, friendly) <span>*</span></label>
                    <textarea class="form-control" name="query[brand_voice]" id="brand_voice" rows="5" required>{{ old('query.brand_voice', $answers['brand_voice'] ?? '') }}</textarea>
                </div>

                <!-- Desired Outcomes -->
                <div class="col-md-12 form-group mb-3">
                    <label for="desired_outcomes">What outcomes or results would you like to see from this reputation
                        management service? <span>*</span></label>
                    <textarea class="form-control" name="query[desired_outcomes]" id="desired_outcomes" rows="5" required>{{ old('query.desired_outcomes', $answers['desired_outcomes'] ?? '') }}</textarea>
                </div>

                <!-- Metrics -->
                <div class="col-md-12 form-group mb-3">
                    <label for="metrics">What key metrics will you use to measure success? (e.g., improved ratings,
                        positive reviews, increased engagement) <span>*</span></label>
                    <textarea class="form-control" name="query[metrics]" id="metrics" rows="5" required>{{ old('query.metrics', $answers['metrics'] ?? '') }}</textarea>
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

