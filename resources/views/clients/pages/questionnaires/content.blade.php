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
                <h4>Content Writing Brief</h4>
            </div>

            <div class="row">
                <!-- Title -->
                <div class="col-md-12 form-group mb-3">
                    <label for="title">Title or Topic of the Content <span>*</span></label>
                    <input type="text" name="query[title]" class="form-control" id="title"
                        value="{{ old('query.title', $answers['title'] ?? '') }}" required>
                </div>

                <!-- Purpose -->
                <div class="col-md-12 form-group mb-3">
                    <label for="purpose">What is the purpose of this content? <span>*</span></label>
                    <textarea class="form-control" name="query[purpose]" id="purpose" rows="5" required>{{ old('query.purpose', $answers['purpose'] ?? '') }}</textarea>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is the target audience for this content? <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $answers['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Word Count -->
                <div class="col-md-12 form-group mb-3">
                    <label for="word_count">What is the desired word count? <span>*</span></label>
                    <input type="text" name="query[word_count]" class="form-control" id="word_count"
                        value="{{ old('query.word_count', $answers['word_count'] ?? '') }}" required>
                </div>

                <!-- Tone Style -->
                <div class="col-md-12 form-group mb-3">
                    <label for="tone_style">What tone and writing style would you like for the content?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[tone_style]" id="tone_style" rows="5" required>{{ old('query.tone_style', $answers['tone_style'] ?? '') }}</textarea>
                </div>

                <!-- Keywords -->
                <div class="col-md-12 form-group mb-3">
                    <label for="keywords">Are there any specific keywords you would like to include?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[keywords]" id="keywords" rows="5" required>{{ old('query.keywords', $answers['keywords'] ?? '') }}</textarea>
                </div>

                <!-- Reference Material -->
                <div class="col-md-12 form-group mb-3">
                    <label for="reference_material">Do you have any reference materials or sources?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[reference_material]" id="reference_material" rows="5" required>{{ old('query.reference_material', $answers['reference_material'] ?? '') }}</textarea>
                </div>

                <!-- Structure -->
                <div class="col-md-12 form-group mb-3">
                    <label for="structure">Would you like the content to follow a specific structure or format?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[structure]" id="structure" rows="5" required>{{ old('query.structure', $answers['structure'] ?? '') }}</textarea>
                </div>

                <!-- Deadline -->
                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Do you have a specific deadline for the content? <span>*</span></label>
                    <input type="text" name="query[deadline]" class="form-control" id="deadline"
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
                    <label for="additional_instructions">Any additional instructions or specific requirements?</label>
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

    <!-- Submit -->
    <div class="text-center mb-4">
        <button type="submit" class="btn btn-gradient w-25">Submit</button>
    </div>
</form>
