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
                <h4>Video Animation Brief</h4>
            </div>
            <hr>

            <div class="row">
                <!-- Video Description -->
                <div class="col-md-12 form-group mb-3">
                    <label for="video_description">Can you provide a brief description of the video animation you need?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[video_description]" id="video_description" rows="5" required>{{ old('query.video_description', $answers['video_description'] ?? '') }}</textarea>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is the target audience for this animation? <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $answers['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Animation Style -->
                <div class="col-md-12 form-group mb-3">
                    <label for="animation_style">What style of animation are you looking for? (e.g., 2D, 3D, Motion
                        Graphics, Whiteboard, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[animation_style]" id="animation_style" rows="5" required>{{ old('query.animation_style', $answers['animation_style'] ?? '') }}</textarea>
                </div>

                <!-- Script -->
                <div class="col-md-12 form-group mb-3">
                    <label for="script">Do you have a script ready for the animation? If yes, please provide it or
                        describe the key points. <span>*</span></label>
                    <textarea class="form-control" name="query[script]" id="script" rows="5" required>{{ old('query.script', $answers['script'] ?? '') }}</textarea>
                </div>

                <!-- Duration -->
                <div class="col-md-12 form-group mb-3">
                    <label for="duration">What is the expected duration of the animation? <span>*</span></label>
                    <input class="form-control" name="query[duration]" id="duration" type="text"
                        value="{{ old('query.duration', $answers['duration'] ?? '') }}" required>
                </div>

                <!-- Reference Videos -->
                <div class="col-md-12 form-group mb-3">
                    <label for="reference_videos">Do you have any reference videos or animations that you'd like us to
                        follow in terms of style? <span>*</span></label>
                    <textarea class="form-control" name="query[reference_videos]" id="reference_videos" rows="5" required>{{ old('query.reference_videos', $answers['reference_videos'] ?? '') }}</textarea>
                </div>

                <!-- Color Preferences -->
                <div class="col-md-12 form-group mb-3">
                    <label for="color_preferences">Do you have any color preferences for the animation?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[color_preferences]" id="color_preferences" rows="5" required>{{ old('query.color_preferences', $answers['color_preferences'] ?? '') }}</textarea>
                </div>

                <!-- Music or Voiceover -->
                <div class="col-md-12 form-group mb-3">
                    <label for="music_or_voiceover">Will there be any music or voiceover in the animation? If yes,
                        please provide details. <span>*</span></label>
                    <textarea class="form-control" name="query[music_or_voiceover]" id="music_or_voiceover" rows="5" required>{{ old('query.music_or_voiceover', $answers['music_or_voiceover'] ?? '') }}</textarea>
                </div>

                <!-- Key Elements -->
                <div class="col-md-12 form-group mb-3">
                    <label for="key_elements">Are there any key elements (characters, objects, logos, etc.) that need to
                        be included? <span>*</span></label>
                    <textarea class="form-control" name="query[key_elements]" id="key_elements" rows="5" required>{{ old('query.key_elements', $answers['key_elements'] ?? '') }}</textarea>
                </div>

                <!-- Deadline -->
                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Do you have a specific deadline for completion? <span>*</span></label>
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
                    <label for="additional_instructions">Do you have any additional instructions or requirements for the
                        animation?</label>
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
