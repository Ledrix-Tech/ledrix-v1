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
                <h4>Merchandise Design Brief</h4>
            </div>

            <div class="row">
                <!-- Business Name -->
                <div class="col-md-12 form-group mb-3">
                    <label for="business_name">What is the name of your business or brand? <span>*</span></label>
                    <input type="text" name="query[business_name]" id="business_name" class="form-control"
                        value="{{ old('query.business_name', $answers['business_name'] ?? '') }}" required>
                </div>

                <!-- Product Type -->
                <div class="col-md-12 form-group mb-3">
                    <label for="product_type">What type of merchandise are you interested in? (e.g., T-shirts, Mugs,
                        Tote Bags, etc.) <span>*</span></label>
                    <input type="text" name="query[product_type]" id="product_type" class="form-control"
                        value="{{ old('query.product_type', $answers['product_type'] ?? '') }}" required>
                </div>

                <!-- Target Audience -->
                <div class="col-md-12 form-group mb-3">
                    <label for="target_audience">Who is your target audience for this merchandise?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[target_audience]" id="target_audience" rows="5" required>{{ old('query.target_audience', $answers['target_audience'] ?? '') }}</textarea>
                </div>

                <!-- Design Concept -->
                <div class="col-md-12 form-group mb-3">
                    <label for="design_concept">Do you have a specific design concept in mind? Please describe it.
                        <span>*</span></label>
                    <textarea class="form-control" name="query[design_concept]" id="design_concept" rows="5" required>{{ old('query.design_concept', $answers['design_concept'] ?? '') }}</textarea>
                </div>

                <!-- Design Elements -->
                <div class="col-md-12 form-group mb-3">
                    <label for="design_elements">Any specific design elements you would like to include? (e.g., logo,
                        brand colors, slogans, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[design_elements]" id="design_elements" rows="5" required>{{ old('query.design_elements', $answers['design_elements'] ?? '') }}</textarea>
                </div>

                <!-- Quantity -->
                <div class="col-md-12 form-group mb-3">
                    <label for="quantity">How many units of each item do you need? <span>*</span></label>
                    <input type="number" name="query[quantity]" id="quantity" class="form-control"
                        value="{{ old('query.quantity', $answers['quantity'] ?? '') }}" required>
                </div>

                <!-- Budget -->
                <div class="col-md-12 form-group mb-3">
                    <label for="budget">What is your budget for the merchandise production? <span>*</span></label>
                    <input type="text" name="query[budget]" id="budget" class="form-control"
                        value="{{ old('query.budget', $answers['budget'] ?? '') }}" required>
                </div>

                <!-- Deadline -->
                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Do you have a specific deadline for the merchandise delivery?
                        <span>*</span></label>
                    <input type="date" name="query[deadline]" id="deadline" class="form-control"
                        value="{{ old('query.deadline', $answers['deadline'] ?? '') }}" required>
                </div>

                <!-- Competitors -->
                <div class="col-md-12 form-group mb-3">
                    <label for="competitors">Are there any competitors or brands whose merchandise you admire?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[competitors]" id="competitors" rows="5" required>{{ old('query.competitors', $answers['competitors'] ?? '') }}</textarea>
                </div>

                <!-- Marketing Channels -->
                <div class="col-md-12 form-group mb-3">
                    <label for="marketing_channels">Where will you be selling or promoting your merchandise? (e.g.,
                        online, at events, in retail stores, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[marketing_channels]" id="marketing_channels" rows="5" required>{{ old('query.marketing_channels', $answers['marketing_channels'] ?? '') }}</textarea>
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
