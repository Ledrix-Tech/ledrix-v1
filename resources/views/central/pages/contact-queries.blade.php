@extends('central.layout.layout')

@section('title', 'Ledrix | Contact Queries')

@section('central-content')

    <div class="sa-page-header">
        <div>
            <h1>Contact Queries</h1>
            <p>Manage inbound contact form submissions</p>
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Contact</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Inquiry</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($queries as $query)
                            <tr>
                                <td data-label="#">{{ $query->id }}</td>
                                <td data-label="Contact">
                                    <strong>{{ $query->name }}</strong><br>
                                    <small><a href="mailto:{{ $query->email }}">{{ $query->email }}</a></small>
                                </td>
                                <td data-label="Company">
                                    {{ $query->company ?? '—' }}
                                    @if ($query->company_size)
                                        <br><small class="text-muted">{{ $query->company_size }} employees</small>
                                    @endif
                                </td>
                                <td data-label="Phone">{{ $query->phone ?? '—' }}</td>
                                <td data-label="Inquiry">
                                    <span class="badge bg-primary">{{ ucwords(str_replace('_', ' ', $query->inquiry_type)) }}</span>
                                    @if ($query->message)
                                        @php
                                            $plain = preg_replace('/\n*\s*\[Marketing\].*$/s', '', (string) $query->message) ?? '';
                                        @endphp
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit(trim($plain), 50) }}</small>
                                    @endif
                                </td>
                                <td data-label="Source">
                                    @php $mkt = \App\Support\MarketingAttribution::fromEmbeddedNotes($query->message); @endphp
                                    @if ($mkt['source'])
                                        <span class="badge bg-info text-dark">{{ str_replace('_', ' ', $mkt['source']) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                    @if ($mkt['landing'])
                                        <br><small class="text-muted">{{ $mkt['landing'] }}</small>
                                    @endif
                                    @if ($mkt['pairs'] !== [])
                                        <br><small class="text-muted">{{ collect($mkt['pairs'])->except(['source', 'referrer'])->map(fn ($v, $k) => $k.'='.\Illuminate\Support\Str::limit($v, 18))->take(4)->implode(' · ') }}</small>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    @php
                                        $statusClass = match ($query->status) {
                                            'new' => 'warning',
                                            'contacted' => 'info',
                                            'in_progress' => 'primary',
                                            'replied' => 'success',
                                            'closed' => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $query->status)) }}</span>
                                </td>
                                <td data-label="Submitted">
                                    {{ $query->created_at->format('d M, Y') }}<br>
                                    <small class="text-muted">{{ $query->created_at->diffForHumans() }}</small>
                                </td>
                                <td data-label="Action">
                                    <form method="POST" action="{{ route('super-admin.contact-status.post') }}">
                                        @csrf
                                        <input type="hidden" name="contact_id" value="{{ $query->id }}">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="new" @selected($query->status == 'new')>New</option>
                                            <option value="contacted" @selected($query->status == 'contacted')>Contacted</option>
                                            <option value="in_progress" @selected($query->status == 'in_progress')>In Progress</option>
                                            <option value="replied" @selected($query->status == 'replied')>Replied</option>
                                            <option value="closed" @selected($query->status == 'closed')>Closed</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No contact inquiries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($queries->hasPages())
                <div class="sa-pagination">{{ $queries->links() }}</div>
            @endif
        </div>
    </div>

@endsection
