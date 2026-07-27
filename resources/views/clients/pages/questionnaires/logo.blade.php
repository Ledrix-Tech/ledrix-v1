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
                <h4>Logo Design Brief</h4>
            </div>
            <hr>

            <div class="row">
                <div class="col-md-4 form-group mb-3">
                    <label for="company_name">What is the name of your company or brand? <span>*</span></label>
                    <input class="form-control" name="query[company_name]" id="company_name" type="text"
                        value="{{ old('query.company_name', $answers['company_name'] ?? '') }}" required>
                </div>

                <div class="col-md-4 form-group mb-3">
                    <label for="tagline">Do you have a tagline or slogan? (Optional)</label>
                    <input class="form-control" name="query[tagline]" id="tagline" type="text"
                        value="{{ old('query.tagline', $answers['tagline'] ?? '') }}">
                </div>

                <div class="col-md-4 form-group mb-3">
                    <label for="industry">What industry or field is your company/brand in? <span>*</span></label>
                    <input class="form-control" name="query[industry]" id="industry" type="text"
                        value="{{ old('query.industry', $answers['industry'] ?? '') }}" required>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="logo_description">Please describe the kind of logo you envision? <span>*</span></label>
                    <textarea class="form-control" name="query[logo_description]" id="logo_description" rows="5" required>{{ old('query.logo_description', $answers['logo_description'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="design_inspiration">Do you have any design inspiration or examples?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[design_inspiration]" id="design_inspiration" rows="5" required>{{ old('query.design_inspiration', $answers['design_inspiration'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="color_preferences">Do you have any color preferences? (Optional)</label>
                    <textarea class="form-control" name="query[color_preferences]" id="color_preferences" rows="5">{{ old('query.color_preferences', $answers['color_preferences'] ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- LOGO STYLE SECTION -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title mb-3">Logo Style & Concept</div>
            <p>Which style do you prefer for your logo? <span>*</span></p>

            @php
                $selectedStyles = old('query.logo_style', $answers['logo_style'] ?? []);
            @endphp

            <div class="row">
                @foreach (['Minimalist', 'Vintage', 'Modern', 'Abstract', 'Illustrative'] as $style)
                    <div class="col-lg-2">
                        <label class="w-100">
                            <div class="formCheck purpose-box font-box">
                                <div class="form-check ml-0 pl-0">
                                    <input type="checkbox" class="form-check-input" name="query[logo_style][]"
                                        value="{{ $style }}"
                                        {{ in_array($style, $selectedStyles) ? 'checked' : '' }}>
                                    {{ $style }}
                                </div>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="form-group mb-3 mt-3">
                <label for="logo_graphic">Do you want the logo to include any specific icon, symbol, or graphic
                    element?</label>
                <textarea class="form-control" name="query[logo_graphic]" id="logo_graphic" rows="5">{{ old('query.logo_graphic', $answers['logo_graphic'] ?? '') }}</textarea>
            </div>

            <div class="form-group mb-3">
                <label for="additional_requirements">Any additional requirements or features you would like for your
                    logo?</label>
                <textarea class="form-control" name="query[additional_requirements]" id="additional_requirements" rows="5">{{ old('query.additional_requirements', $answers['additional_requirements'] ?? '') }}</textarea>
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

    <hr>

    <div class="text-center mb-4">
        <button type="submit" class="btn btn-gradient w-25">Submit</button>
    </div>
</form>
