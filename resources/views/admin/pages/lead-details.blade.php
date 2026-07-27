@extends('admin.layout.layout')

@section('title', 'Admin | Lead Details')

@section('admin-content')

    <style>
        .card-body h4 {
            color: #003C51 !important;
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <div>
                        <h1 class="fw-bold" style="color: #003C51;">Lead Details</h1>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body">

                        <!-- Lead Basic Info -->
                        <h4 class="fw-bold mb-3 text-primary">Basic Information</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 200px;">Name</th>
                                <td>{{ $lead->client->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $lead->client->email }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>{{ $lead->client->phone }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span
                                        class="badge
                                    @if ($lead->status == 'Hot') bg-danger
                                    @elseif($lead->status == 'Warm') bg-warning
                                    @else bg-secondary @endif">
                                        {{ ucfirst($lead->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Source</th>
                                <td><a href="{{ $lead->brand->brand_url }}"
                                        target="_blank">{{ $lead->brand->brand_url ?? 'N/A' }}</a></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>{{ $lead->created_at->format('d M, Y h:i A') }}</td>
                            </tr>
                        </table>

                        @if ($showLeadPrediction ?? false)
                            <h4 class="fw-bold mt-5 mb-3 text-primary">AI Lead Prediction</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">Quality</th>
                                    <td>
                                        @include('admin.includes.lead-prediction', [
                                            'prediction' => $lead->prediction ?? null,
                                        ])
                                    </td>
                                </tr>
                                @php $prediction = is_array($lead->prediction ?? null) ? $lead->prediction : null; @endphp
                                @if ($prediction)
                                    @if (($prediction['status'] ?? null) !== 'fake')
                                        <tr>
                                            <th>Score</th>
                                            <td>{{ $prediction['score'] ?? '—' }}/100</td>
                                        </tr>
                                        <tr>
                                            <th>Strength</th>
                                            <td class="text-capitalize">{{ $prediction['strength'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Category</th>
                                            <td class="text-capitalize">
                                                {{ str_replace('_', ' ', $prediction['category'] ?? '—') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Sentiment</th>
                                            <td class="text-capitalize">{{ $prediction['sentiment'] ?? '—' }}</td>
                                        </tr>
                                    @endif
                                @else
                                    <tr>
                                        <td colspan="2" class="text-muted">No prediction data for this lead.</td>
                                    </tr>
                                @endif
                            </table>
                        @endif

                        <!-- Seller Info -->
                        <h4 class="fw-bold mt-5 mb-3 text-primary">Assigned Seller</h4>
                        <p><strong>{{ $lead->seller->name ?? 'Not Assigned' }}</strong></p>

                        <!-- Notes -->
                        <h4 class="fw-bold mt-5 mb-3 text-primary">Notes / Remarks</h4>
                        <p>{{ $lead->message ?? 'No notes yet.' }}</p>

                        <hr>
                        <h4 class="fw-bold mt-5 mb-3 text-primary">Extra Information</h4>
                        @if (!empty($lead->meta))
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped text-start align-middle">
                                    <tbody>
                                        @foreach ($lead->meta as $key => $value)
                                            <tr>
                                                <th class="bg-light text-capitalize" style="width: 25%;">
                                                    {{ str_replace('_', ' ', $key) }}
                                                </th>
                                                <td>
                                                    @if (is_array($value))
                                                        <pre class="mb-0 small">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    @else
                                                        {{ $value ?? '—' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">No extra data available.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
