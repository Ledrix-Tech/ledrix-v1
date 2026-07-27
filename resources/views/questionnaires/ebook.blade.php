<form class="col-md-12 brief-form p-0"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="order_id" value="{{ $order->id }}">

    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title mb-3 text-center">
                <h4>Ebook Design & Formatting Brief</h4>
            </div>
            <hr>

            <div class="row">
                <!-- Ebook Title -->
                <div class="col-md-12 form-group mb-3">
                    <label for="ebook_title">Title of the Ebook <span>*</span></label>
                    <input type="text" name="query[ebook_title]" id="ebook_title" class="form-control"
                        value="{{ old('query.ebook_title', $brief->meta['ebook_title'] ?? '') }}" required>
                </div>

                <!-- Author Name -->
                <div class="col-md-12 form-group mb-3">
                    <label for="author_name">Author Name <span>*</span></label>
                    <input type="text" name="query[author_name]" id="author_name" class="form-control"
                        value="{{ old('query.author_name', $brief->meta['author_name'] ?? '') }}" required>
                </div>

                <!-- Genre -->
                <div class="col-md-12 form-group mb-3">
                    <label for="genre">Genre or Category <span>*</span></label>
                    <input type="text" name="query[genre]" id="genre" class="form-control"
                        value="{{ old('query.genre', $brief->meta['genre'] ?? '') }}" required>
                </div>

                <!-- Description -->
                <div class="col-md-12 form-group mb-3">
                    <label for="description">Briefly describe what your ebook is about <span>*</span></label>
                    <textarea class="form-control" name="query[description]" id="description" rows="5" required>{{ old('query.description', $brief->meta['description'] ?? '') }}</textarea>
                </div>

                <!-- Formatting Style -->
                <div class="col-md-12 form-group mb-3">
                    <label for="formatting_style">What formatting style do you prefer? (e.g., Kindle, EPUB, Print-ready,
                        PDF, etc.) <span>*</span></label>
                    <textarea class="form-control" name="query[formatting_style]" id="formatting_style" rows="5" required>{{ old('query.formatting_style', $brief->meta['formatting_style'] ?? '') }}</textarea>
                </div>

                <!-- Page Size -->
                <div class="col-md-12 form-group mb-3">
                    <label for="page_size">Preferred Page Size (e.g., A4, 6x9 inch, etc.) <span>*</span></label>
                    <input type="text" name="query[page_size]" id="page_size" class="form-control"
                        value="{{ old('query.page_size', $brief->meta['page_size'] ?? '') }}" required>
                </div>

                <!-- Design Preferences -->
                <div class="col-md-12 form-group mb-3">
                    <label for="design_preferences">Do you have any specific design preferences (fonts, layout, color
                        theme)? <span>*</span></label>
                    <textarea class="form-control" name="query[design_preferences]" id="design_preferences" rows="5" required>{{ old('query.design_preferences', $brief->meta['design_preferences'] ?? '') }}</textarea>
                </div>

                <!-- Images -->
                <div class="col-md-12 form-group mb-3">
                    <label for="images">Do you have any images, illustrations, or graphics to include?
                        <span>*</span></label>
                    <textarea class="form-control" name="query[images]" id="images" rows="5" required>{{ old('query.images', $brief->meta['images'] ?? '') }}</textarea>
                </div>

                <!-- Word Count -->
                <div class="col-md-12 form-group mb-3">
                    <label for="word_count">Approximate Word Count <span>*</span></label>
                    <input type="text" name="query[word_count]" id="word_count" class="form-control"
                        value="{{ old('query.word_count', $brief->meta['word_count'] ?? '') }}" required>
                </div>

                <!-- Deadline -->
                <div class="col-md-12 form-group mb-3">
                    <label for="deadline">Project Deadline <span>*</span></label>
                    <input type="date" name="query[deadline]" id="deadline" class="form-control"
                        value="{{ old('query.deadline', $brief->meta['deadline'] ?? '') }}" required>
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
                    <label for="additional_instructions">Any additional instructions or requirements for your
                        ebook?</label>
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

