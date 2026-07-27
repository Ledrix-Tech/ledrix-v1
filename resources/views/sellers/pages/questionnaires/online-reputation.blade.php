<form class="col-md-12 brief-form p-0" method="POST" action="{{ route('client.brief-form.post') }}"
    enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="order_id" value="{{ $order->id }}">

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
                        value="{{ old('query.business_name', $brief->meta['business_name'] ?? '') }}" required>
                </div>

                <!-- Industry -->
                <div class="col-md-12 form-group mb-3">
                    <label for="industry">What industry does your business belong to? <span>*</span></label>
                    <input type="text" name="query[industry]" id="industry" class="form-control"
                        value="{{ old('query.industry', $brief->meta['industry'] ?? '') }}" required>
                </div>

                <!-- Current Reputation -->
                <div class="col-md-12 form-group mb-3">
                    <label for="current_reputation">How would you describe your current online reputation?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[current_reputation]" id="current_reputation" rows="5" required>{{ old('query.current_reputation', $brief->meta['current_reputation'] ?? '') }}</textarea>
                </div>

                <!-- Goals -->
                <div class="col-md-12 form-group mb-3">
                    <label for="reputation_goals">What are your specific goals regarding online reputation management?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[reputation_goals]" id="reputation_goals" rows="5" required>{{ old('query.reputation_goals', $brief->meta['reputation_goals'] ?? '') }}</textarea>
                </div>

                <!-- Platforms -->
                <div class="col-md-12 form-group mb-3">
                    <label for="platforms">Which platforms or websites do you want us to focus on? (e.g., Google
                        Reviews, Yelp, Social Media, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[platforms]" id="platforms" rows="5" required>{{ old('query.platforms', $brief->meta['platforms'] ?? '') }}</textarea>
                </div>

                <!-- Negative Reviews -->
                <div class="col-md-12 form-group mb-3">
                    <label for="negative_reviews">Are there any specific negative reviews or comments you'd like us to
                        address? <span>*</span></label>
                    <textarea class="form-control" name="query[negative_reviews]" id="negative_reviews" rows="5" required>{{ old('query.negative_reviews', $brief->meta['negative_reviews'] ?? '') }}</textarea>
                </div>

                <!-- Existing Reputation Management -->
                <div class="col-md-12 form-group mb-3">
                    <label for="existing_reputation_management">Do you currently have any reputation management
                        strategies or services in place? <span>*</span></label>
                    <textarea class="form-control" name="query[existing_reputation_management]" id="existing_reputation_management"
                        rows="5" required>{{ old('query.existing_reputation_management', $brief->meta['existing_reputation_management'] ?? '') }}</textarea>
                </div>

                <!-- Competitors -->
                <div class="col-md-12 form-group mb-3">
                    <label for="competitors">Do you have any competitors whose online reputation strategies you are
                        concerned about? <span>*</span></label>
                    <textarea class="form-control" name="query[competitors]" id="competitors" rows="5" required>{{ old('query.competitors', $brief->meta['competitors'] ?? '') }}</textarea>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">What is your target audience? (e.g., local customers, national
                        audience, specific demographics) <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $brief->meta['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Brand Voice -->
                <div class="col-md-12 form-group mb-3">
                    <label for="brand_voice">What is your preferred brand voice for online interactions? (e.g., formal,
                        casual, friendly) <span>*</span></label>
                    <textarea class="form-control" name="query[brand_voice]" id="brand_voice" rows="5" required>{{ old('query.brand_voice', $brief->meta['brand_voice'] ?? '') }}</textarea>
                </div>

                <!-- Desired Outcomes -->
                <div class="col-md-12 form-group mb-3">
                    <label for="desired_outcomes">What outcomes or results would you like to see from this reputation
                        management service? <span>*</span></label>
                    <textarea class="form-control" name="query[desired_outcomes]" id="desired_outcomes" rows="5" required>{{ old('query.desired_outcomes', $brief->meta['desired_outcomes'] ?? '') }}</textarea>
                </div>

                <!-- Metrics -->
                <div class="col-md-12 form-group mb-3">
                    <label for="metrics">What key metrics will you use to measure success? (e.g., improved ratings,
                        positive reviews, increased engagement) <span>*</span></label>
                    <textarea class="form-control" name="query[metrics]" id="metrics" rows="5" required>{{ old('query.metrics', $brief->meta['metrics'] ?? '') }}</textarea>
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


<!-- JS for clickable file upload -->
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
