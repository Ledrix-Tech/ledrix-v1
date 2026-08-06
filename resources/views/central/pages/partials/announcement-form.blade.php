@php
    $a = $announcement;
@endphp
<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control" required maxlength="255"
        value="{{ old('title', $a->title ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">Message</label>
    <textarea name="message" rows="4" class="form-control" required maxlength="5000">{{ old('message', $a->message ?? '') }}</textarea>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" required>
            @foreach (['info', 'warning', 'success', 'danger'] as $type)
                <option value="{{ $type }}" @selected(old('type', $a->type ?? 'info') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Target</label>
        <select name="target" class="form-select" required>
            <option value="all" @selected(old('target', $a->target ?? 'all') === 'all')>All tenants</option>
            @foreach ($plans as $plan)
                @php $target = 'plan_' . $plan->slug; @endphp
                <option value="{{ $target }}" @selected(old('target', $a->target ?? '') === $target)>
                    Plan: {{ $plan->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            <option value="active" @selected(old('status', $a->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $a->status ?? '') === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Show from</label>
        <input type="datetime-local" name="show_from" class="form-control"
            value="{{ old('show_from', optional($a?->show_from)->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Show until</label>
        <input type="datetime-local" name="show_until" class="form-control"
            value="{{ old('show_until', optional($a?->show_until)->format('Y-m-d\TH:i')) }}">
    </div>
</div>
<div class="form-check">
    <input class="form-check-input" type="checkbox" name="is_dismissible" value="1" id="dismissible_{{ $a->id ?? 'new' }}"
        @checked(old('is_dismissible', $a->is_dismissible ?? true))>
    <label class="form-check-label" for="dismissible_{{ $a->id ?? 'new' }}">Tenants can dismiss</label>
</div>
