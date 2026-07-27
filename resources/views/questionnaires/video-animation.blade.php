<form class="col-md-12 brief-form p-0" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="order_id" value="{{ $order->id }}">

    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title mb-3 text-center">
                <h4>Video Animation Brief</h4>
            </div>
            <hr>

            <div class="row">
                <!-- Video Description -->
                <div class="col-md-12 form-group mb-3">
                    <label for="video_description">Can you provide a brief description of the video animation you need? <span>*</span></label>
                    <textarea class="form-control" name="query[video_description]" id="video_description" rows="5" required>{{ old('query.video_description', $brief->meta['video_description'] ?? '') }}</textarea>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is the target audience for this animation? <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $brief->meta['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Animation Style -->
                <div class="col-md-12 form-group mb-3">
                    <label for="animation_style">What style of animation are you looking for? (e.g., 2D, 3D, Motion Graphics, Whiteboard, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[animation_style]" id="animation_style" rows="5" required>{{ old('query.animation_style', $brief->meta['animation_style'] ?? '') }}</textarea>
                </div>

                <!-- Script -->
                <div class="col-md-12 form-group mb-3">
                    <label for="script">Do you have a script ready for the animation? If yes, please provide it or describe the key points. <span>*</span></label>
                    <textarea class="form-control" name="query[script]" id="script" rows="5" required>{{ old('query.script', $brief->meta['script'] ?? '') }}</textarea>
                </div>

                <!-- Duration -->
                <div class="col-md-12 form-group mb-3">
                    <label for="duration">What is the expected duration of the animation? <span>*</span></label>
                    <input class="form-control" name="query[duration]" id="duration" type="text"
                        value="{{ old('query.duration', $brief->meta['duration'] ?? '') }}" required>
                </div>

                <!-- Reference Videos -->
                <div class="col-md-12 form-group mb-3">
                    <label for="reference_videos">Do you have any reference videos or animations that you'd like us to follow in terms of style? <span>*</span></label>
                    <textarea class="form-control" name="query[reference_videos]" id="reference_videos" rows="5" required>{{ old('query.reference_videos', $brief->meta['reference_videos'] ?? '') }}</textarea>
                </div>

                <!-- Color Preferences -->
                <div class="col-md-12 form-group mb-3">
                    <label for="color_preferences">Do you have any color preferences for the animation? <span>*</span></label>
                    <textarea class="form-control" name="query[color_preferences]" id="color_preferences" rows="5" required>{{ old('query.color_preferences', $brief->meta['color_preferences'] ?? '') }}</textarea>
                </div>

                <!-- Music or Voiceover -->
                <div class="col-md-12 form-group mb-3">
                    <label for="music_or_voiceover">Will there be any music or voiceover in the animation? If yes, please provide details. <span>*</span></label>
                    <textarea class="form-control" name="query[music_or_voiceover]" id="music_or_voiceover" rows="5" required>{{ old('query.music_or_voiceover', $brief->meta['music_or_voiceover'] ?? '') }}</textarea>
                </div>

                <!-- Key Elements -->
                <div class="col-md-12 form-group mb-3">
                    <label for="key_elements">Are there any key elements (characters, objects, logos, etc.) that need to be included? <span>*</span></label>
                    <textarea class="form-control" name="query[key_elements]" id="key_elements" rows="5" required>{{ old('query.key_elements', $brief->meta['key_elements'] ?? '') }}</textarea>
                </div>

                <!-- Deadline -->
                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Do you have a specific deadline for completion? <span>*</span></label>
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
                    <label for="additional_instructions">Do you have any additional instructions or requirements for the animation?</label>
                    <textarea class="form-control" name="query[additional_instructions]" id="additional_instructions" rows="5">{{ old('query.additional_instructions', $brief->meta['additional_instructions'] ?? '') }}</textarea>
                </div>
            </div>
        </div>
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

</form>
