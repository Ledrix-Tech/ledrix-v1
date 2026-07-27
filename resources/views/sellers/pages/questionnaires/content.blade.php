<form class="col-md-12 brief-form p-0" method="POST" action="{{ route('client.brief-form.post') }}"
    enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="order_id" value="{{ $order->id }}">

    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title mb-3 text-center">
                <h4>Content Writing Brief</h4>
            </div>

            <div class="row">
                <!-- Title -->
                <div class="col-md-12 form-group mb-3">
                    <label for="title">Title or Topic of the Content <span>*</span></label>
                    <input type="text" name="query[title]" class="form-control" id="title"
                        value="{{ old('query.title', $brief->meta['title'] ?? '') }}" required>
                </div>

                <!-- Purpose -->
                <div class="col-md-12 form-group mb-3">
                    <label for="purpose">What is the purpose of this content? <span>*</span></label>
                    <textarea class="form-control" name="query[purpose]" id="purpose" rows="5" required>{{ old('query.purpose', $brief->meta['purpose'] ?? '') }}</textarea>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is the target audience for this content? <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $brief->meta['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Word Count -->
                <div class="col-md-12 form-group mb-3">
                    <label for="word_count">What is the desired word count? <span>*</span></label>
                    <input type="text" name="query[word_count]" class="form-control" id="word_count"
                        value="{{ old('query.word_count', $brief->meta['word_count'] ?? '') }}" required>
                </div>

                <!-- Tone Style -->
                <div class="col-md-12 form-group mb-3">
                    <label for="tone_style">What tone and writing style would you like for the content?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[tone_style]" id="tone_style" rows="5" required>{{ old('query.tone_style', $brief->meta['tone_style'] ?? '') }}</textarea>
                </div>

                <!-- Keywords -->
                <div class="col-md-12 form-group mb-3">
                    <label for="keywords">Are there any specific keywords you would like to include?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[keywords]" id="keywords" rows="5" required>{{ old('query.keywords', $brief->meta['keywords'] ?? '') }}</textarea>
                </div>

                <!-- Reference Material -->
                <div class="col-md-12 form-group mb-3">
                    <label for="reference_material">Do you have any reference materials or sources?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[reference_material]" id="reference_material" rows="5" required>{{ old('query.reference_material', $brief->meta['reference_material'] ?? '') }}</textarea>
                </div>

                <!-- Structure -->
                <div class="col-md-12 form-group mb-3">
                    <label for="structure">Would you like the content to follow a specific structure or format?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[structure]" id="structure" rows="5" required>{{ old('query.structure', $brief->meta['structure'] ?? '') }}</textarea>
                </div>

                <!-- Deadline -->
                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Do you have a specific deadline for the content? <span>*</span></label>
                    <input type="text" name="query[deadline]" class="form-control" id="deadline"
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
                    <label for="additional_instructions">Any additional instructions or specific requirements?</label>
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
</style>
