<form class="col-md-12 brief-form p-0" method="POST" action="{{ route('client.brief-form.post') }}"
    enctype="multipart/form-data">
    @csrf

    <input class="form-control" name="order_id" type="hidden" value="{{ $order->id }}" required>

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
                        value="{{ old('query.company_name', $brief->meta['company_name'] ?? '') }}" required>
                </div>

                <div class="col-md-4 form-group mb-3">
                    <label for="tagline">Do you have a tagline or slogan? (Optional)</label>
                    <input class="form-control" name="query[tagline]" id="tagline" type="text"
                        value="{{ old('query.tagline', $brief->meta['tagline'] ?? '') }}">
                </div>

                <div class="col-md-4 form-group mb-3">
                    <label for="industry">What industry or field is your company/brand in? <span>*</span></label>
                    <input class="form-control" name="query[industry]" id="industry" type="text"
                        value="{{ old('query.industry', $brief->meta['industry'] ?? '') }}" required>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="logo_description">Please describe the kind of logo you envision? <span>*</span></label>
                    <textarea class="form-control" name="query[logo_description]" id="logo_description" rows="5" required>{{ old('query.logo_description', $brief->meta['logo_description'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="design_inspiration">Do you have any design inspiration or examples?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[design_inspiration]" id="design_inspiration" rows="5" required>{{ old('query.design_inspiration', $brief->meta['design_inspiration'] ?? '') }}</textarea>
                </div>

                <div class="col-md-12 form-group mb-3">
                    <label for="color_preferences">Do you have any color preferences? (Optional)</label>
                    <textarea class="form-control" name="query[color_preferences]" id="color_preferences" rows="5">{{ old('query.color_preferences', $brief->meta['color_preferences'] ?? '') }}</textarea>
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
                $selectedStyles = old('query.logo_style', $brief->meta['logo_style'] ?? []);
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
                <textarea class="form-control" name="query[logo_graphic]" id="logo_graphic" rows="5">{{ old('query.logo_graphic', $brief->meta['logo_graphic'] ?? '') }}</textarea>
            </div>

            <div class="form-group mb-3">
                <label for="additional_requirements">Any additional requirements or features you would like for your
                    logo?</label>
                <textarea class="form-control" name="query[additional_requirements]" id="additional_requirements" rows="5">{{ old('query.additional_requirements', $brief->meta['additional_requirements'] ?? '') }}</textarea>
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

    <hr>

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
