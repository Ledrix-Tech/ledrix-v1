
<main class="py-5">
        <div class="container" style="max-width: 720px;">
            <a href="{{ org_route('support.index') }}" class="text-muted small text-decoration-none">&larr; Back</a>
            <h4 class="mt-1 mb-4">New support ticket</h4>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ org_route('support.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject') }}" required maxlength="255">
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select" required>
                                    @foreach (['billing', 'technical', 'feature_request', 'account', 'other'] as $cat)
                                        <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select" required>
                                    @foreach (['low', 'medium', 'high', 'urgent'] as $p)
                                        <option value="{{ $p }}" @selected(old('priority', 'medium') === $p)>{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="6" class="form-control @error('description') is-invalid @enderror"
                                required maxlength="5000">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Submit ticket</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
