@extends('central.layout.layout')

@section('title', 'Ledrix | Subscription Payment Accounts')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>Subscription Payment Accounts</h1>
            <p>Enable or disable PayFast, Stripe, and Meezan for tenant subscription billing. Edit keys here — whichever providers are ON appear on the tenant billing page.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($migrationRequired)
        <div class="alert alert-warning">
            <strong>Migration required.</strong>
            Run on the server:
            <code>php artisan migrate --database=central --path=database/migrations/central --force</code>
        </div>
    @else
        <div class="row g-4">
            @foreach ($definitions as $providerKey => $definition)
                @php
                    $state = $providers[$providerKey] ?? ['enabled' => false, 'masked' => [], 'ready' => false, 'configured' => false];
                    $masked = $state['masked'] ?? [];
                @endphp
                <div class="col-lg-12">
                    <div class="sa-card">
                        <div class="sa-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h4 class="mb-1">{{ $definition['label'] }}</h4>
                                <p class="text-muted small mb-0">{{ $definition['help'] }}</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if ($state['ready'])
                                    <span class="badge bg-success">Live on billing</span>
                                @elseif ($state['enabled'])
                                    <span class="badge bg-warning text-dark">Enabled — missing keys</span>
                                @else
                                    <span class="badge bg-secondary">Off</span>
                                @endif
                            </div>
                        </div>
                        <div class="sa-card-body">
                            <form method="POST" action="{{ route('super-admin.billing-settings.update', $providerKey) }}">
                                @csrf
                                @method('PUT')

                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="enabled_{{ $providerKey }}" name="enabled" value="1"
                                        @checked(old('enabled', $state['enabled']))>
                                    <label class="form-check-label fw-semibold" for="enabled_{{ $providerKey }}">
                                        Accept subscription payments via {{ $definition['label'] }}
                                    </label>
                                </div>

                                <div class="row">
                                    @foreach ($definition['fields'] as $fieldKey => $field)
                                        @php
                                            $isSensitive = ! empty($field['sensitive']);
                                            $value = old("credentials.{$fieldKey}", $isSensitive ? '' : ($masked[$fieldKey] ?? ''));
                                            $alreadySet = $isSensitive && ! empty($masked[$fieldKey . '_set']);
                                        @endphp
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                {{ $field['label'] }}
                                                @if (! empty($field['required']))
                                                    <span class="text-danger">*</span>
                                                @endif
                                            </label>
                                            <input
                                                type="{{ $field['type'] ?? 'text' }}"
                                                name="credentials[{{ $fieldKey }}]"
                                                class="form-control"
                                                value="{{ $value }}"
                                                placeholder="{{ $alreadySet ? '•••• saved (leave blank to keep)' : ($field['placeholder'] ?? '') }}"
                                                autocomplete="off"
                                            >
                                            @if ($alreadySet)
                                                <div class="form-text">A value is already saved. Leave blank to keep it.</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <button type="submit" class="btn btn-sa-primary">
                                    Save {{ $definition['label'] }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
