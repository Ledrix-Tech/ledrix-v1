<form class="col-md-12 brief-form p-0" method="POST" action="{{ route('client.brief-form.post') }}"
    enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="order_id" value="{{ $order->id }}">

    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title mb-3 text-center">
                <h4>Social Media Marketing Brief</h4>
            </div>

            <div class="row">
                <!-- Business Name -->
                <div class="col-md-12 form-group mb-3">
                    <label for="business_name">What is the name of your business or brand? <span>*</span></label>
                    <input type="text" name="query[business_name]" id="business_name" class="form-control"
                        value="{{ old('query.business_name', $brief->meta['business_name'] ?? '') }}" required>
                </div>

                <!-- Platforms -->
                <div class="col-md-12 form-group mb-3">
                    <label>Which social media platforms are you interested in marketing on? <span>*</span></label>
                    @php
                        $selectedPlatforms = old('query.platforms', $brief->meta['platforms'] ?? []);
                    @endphp
                    <div class="row">
                        @foreach (['Facebook', 'Instagram', 'Twitter', 'LinkedIn', 'TikTok'] as $platform)
                            <div class="col-lg-2">
                                <div class="formCheck font-box">
                                    <div class="form-check pl-0">
                                        <input type="checkbox" class="form-check-input" id="{{ strtolower($platform) }}"
                                            name="query[platforms][]" value="{{ $platform }}"
                                            {{ in_array($platform, $selectedPlatforms) ? 'checked' : '' }}>
                                        <label for="{{ strtolower($platform) }}"
                                            class="genre">{{ $platform }}</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Marketing Goals -->
                <div class="col-md-12 form-group mb-3">
                    <label for="marketing_goals">What are your marketing goals for social media? <span>*</span></label>
                    <textarea class="form-control" name="query[marketing_goals]" id="marketing_goals" rows="5" required>{{ old('query.marketing_goals', $brief->meta['marketing_goals'] ?? '') }}</textarea>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is your target audience for the campaign? <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $brief->meta['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Budget -->
                <div class="col-md-12 form-group mb-3">
                    <label for="budget">What is your estimated budget for social media marketing?
                        <span>*</span></label>
                    <input type="text" name="query[budget]" id="budget" class="form-control"
                        value="{{ old('query.budget', $brief->meta['budget'] ?? '') }}" required>
                </div>

                <!-- Previous Campaigns -->
                <div class="col-md-12 form-group mb-3">
                    <label for="previous_campaigns">Have you run any previous social media marketing campaigns?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[previous_campaigns]" id="previous_campaigns" rows="5" required>{{ old('query.previous_campaigns', $brief->meta['previous_campaigns'] ?? '') }}</textarea>
                </div>

                <!-- Content Preference -->
                <div class="col-md-12 form-group mb-3">
                    <label for="content_preference">What type of content do you prefer for your campaigns?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[content_preference]" id="content_preference" rows="5" required>{{ old('query.content_preference', $brief->meta['content_preference'] ?? '') }}</textarea>
                </div>

                <!-- Competitors -->
                <div class="col-md-12 form-group mb-3">
                    <label for="competitors">Who are your main competitors? <span>*</span></label>
                    <textarea class="form-control" name="query[competitors]" id="competitors" rows="5" required>{{ old('query.competitors', $brief->meta['competitors'] ?? '') }}</textarea>
                </div>

                <!-- Campaign Duration -->
                <div class="col-md-12 form-group mb-3">
                    <label for="campaign_duration">How long do you want the campaign to run? <span>*</span></label>
                    <input type="text" name="query[campaign_duration]" id="campaign_duration" class="form-control"
                        value="{{ old('query.campaign_duration', $brief->meta['campaign_duration'] ?? '') }}" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title mb-3">Additional Information</div>
            <div class="row">
                <div class="col-md-12 form-group mb-3">
                    <label for="additional_instructions">Any additional instructions or requirements for the
                        campaign?</label>
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

    <!-- Submit -->
    <div class="text-center mb-4">
        <button type="submit" class="btn btn-gradient w-25">Submit</button>
    </div>
</form>


<!-- JS for clickable upload area -->
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
