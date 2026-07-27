<form class="col-md-12 brief-form p-0" method="POST" action="{{ route('client.brief-form.post') }}"
    enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="order_id" value="{{ $order->id }}">

    <div class="card mb-4">
        <div class="card-body mb-4">
            <div class="card-title mb-3 text-center">
                <h4>Merchandise Design Brief</h4>
            </div>

            <div class="row">
                <!-- Business Name -->
                <div class="col-md-12 form-group mb-3">
                    <label for="business_name">What is the name of your business or brand? <span>*</span></label>
                    <input type="text" name="query[business_name]" id="business_name" class="form-control"
                        value="{{ old('query.business_name', $brief->meta['business_name'] ?? '') }}" required>
                </div>

                <!-- Product Type -->
                <div class="col-md-12 form-group mb-3">
                    <label for="product_type">What type of merchandise are you interested in? (e.g., T-shirts, Mugs,
                        Tote Bags, etc.) <span>*</span></label>
                    <input type="text" name="query[product_type]" id="product_type" class="form-control"
                        value="{{ old('query.product_type', $brief->meta['product_type'] ?? '') }}" required>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is your target audience for this merchandise?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $brief->meta['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Design Concept -->
                <div class="col-md-12 form-group mb-3">
                    <label for="design_concept">Do you have a specific design concept in mind? Please describe it.
                        <span>*</span></label>
                    <textarea class="form-control" name="query[design_concept]" id="design_concept" rows="5" required>{{ old('query.design_concept', $brief->meta['design_concept'] ?? '') }}</textarea>
                </div>

                <!-- Design Elements -->
                <div class="col-md-12 form-group mb-3">
                    <label for="design_elements">Any specific design elements you would like to include? (e.g., logo,
                        brand colors, slogans, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[design_elements]" id="design_elements" rows="5" required>{{ old('query.design_elements', $brief->meta['design_elements'] ?? '') }}</textarea>
                </div>

                <!-- Quantity -->
                <div class="col-md-12 form-group mb-3">
                    <label for="quantity">How many units of each item do you need? <span>*</span></label>
                    <input type="number" name="query[quantity]" id="quantity" class="form-control"
                        value="{{ old('query.quantity', $brief->meta['quantity'] ?? '') }}" required>
                </div>

                <!-- Budget -->
                <div class="col-md-12 form-group mb-3">
                    <label for="budget">What is your budget for the merchandise production? <span>*</span></label>
                    <input type="text" name="query[budget]" id="budget" class="form-control"
                        value="{{ old('query.budget', $brief->meta['budget'] ?? '') }}" required>
                </div>

                <!-- Deadline -->
                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Do you have a specific deadline for the merchandise delivery?
                        <span>*</span></label>
                    <input type="date" name="query[deadline]" id="deadline" class="form-control"
                        value="{{ old('query.deadline', $brief->meta['deadline'] ?? '') }}" required>
                </div>

                <!-- Competitors -->
                <div class="col-md-12 form-group mb-3">
                    <label for="competitors">Are there any competitors or brands whose merchandise you admire?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[competitors]" id="competitors" rows="5" required>{{ old('query.competitors', $brief->meta['competitors'] ?? '') }}</textarea>
                </div>

                <!-- Marketing Channels -->
                <div class="col-md-12 form-group mb-3">
                    <label for="marketing_channels">Where will you be selling or promoting your merchandise? (e.g.,
                        online, at events, in retail stores, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[marketing_channels]" id="marketing_channels" rows="5" required>{{ old('query.marketing_channels', $brief->meta['marketing_channels'] ?? '') }}</textarea>
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
