@extends('central.layout.layout')

@section('title', 'Ledrix | Pricing Packages')

@section('central-content')

    <div class="sa-page-header">
        <div>
            <h1>Pricing Packages</h1>
            <p>Configure plans shown on the pricing page</p>
        </div>
        <button type="button" class="btn btn-sa-primary" data-bs-toggle="modal" data-bs-target="#addPackage">
            <i class="bi bi-plus-lg me-1"></i> Add package
        </button>
    </div>

    <div class="row g-4">
        @forelse ($packages as $package)
            <div class="col-lg-6">
                <div class="sa-card sa-package-card">
                    <div class="sa-card-header">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <h4 class="mb-0">{{ $package->name }}</h4>
                            <form method="POST" action="{{ route('super-admin.pricing-packages.destroy', $package->id) }}"
                                onsubmit="return confirm('Delete or archive this package?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                        @if ($package->is_popular)
                            <span class="badge bg-primary">Popular</span>
                        @endif
                    </div>
                    <div class="sa-card-body">
                        <form method="POST" action="{{ route('super-admin.pricing-packages.update', $package->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">Package Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $package->name) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Package Slug</label>
                                <select name="slug" class="form-select" required>
                                    <option value="crm-basic" @selected(old('slug', $package->slug) == 'crm-basic')>CRM Basic</option>
                                    <option value="crm-standard" @selected(old('slug', $package->slug) == 'crm-standard')>CRM Standard</option>
                                    <option value="crm-premium" @selected(old('slug', $package->slug) == 'crm-premium')>CRM Premium</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Monthly Price (USD)</label>
                                    <input type="number" step="0.01" name="monthly_price" class="form-control"
                                        value="{{ old('monthly_price', $package->monthly_price) }}" required>
                                    <div class="form-text">Stripe / international</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Yearly Price (USD)</label>
                                    <input type="number" step="0.01" name="yearly_price" class="form-control"
                                        value="{{ old('yearly_price', $package->yearly_price) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Monthly Price (PKR)</label>
                                    <input type="number" step="0.01" name="monthly_price_pkr" class="form-control"
                                        value="{{ old('monthly_price_pkr', $package->monthly_price_pkr) }}">
                                    <div class="form-text">Meezan / PayFast (Pakistan)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Yearly Price (PKR)</label>
                                    <input type="number" step="0.01" name="yearly_price_pkr" class="form-control"
                                        value="{{ old('yearly_price_pkr', $package->yearly_price_pkr) }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Package Description</label>
                                <textarea name="description" rows="3" class="form-control" placeholder="Short package description...">{{ old('description', $package->description ?? '') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Package Features / Description</label>
                                <textarea name="features_html" id="features_html_{{ $package->id }}" rows="8"
                                    class="sa-wysiwyg form-control">{{ old('features_html', $package->features_html) }}</textarea>
                            </div>

                            <hr>
                            <h5 class="mb-3">Usage Limits</h5>
                            <div class="row">
                                @foreach ([
                                    'max_brands' => 'Max Brands',
                                    'max_sellers' => 'Max Sellers',
                                    'max_admins' => 'Max Admins',
                                    'max_clients' => 'Max Clients',
                                    'max_leads_per_month' => 'Max Leads / Month',
                                    'max_orders' => 'Max Orders',
                                    'max_payment_links' => 'Max Payment Links',
                                    'max_account_keys' => 'Max Account Keys',
                                    'max_projects' => 'Max Projects',
                                    'max_storage_mb' => 'Storage (MB)',
                                ] as $field => $label)
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ $label }}</label>
                                        <input type="number" name="{{ $field }}" class="form-control"
                                            value="{{ old($field, $package->$field) }}">
                                    </div>
                                @endforeach
                            </div>

                            <hr>
                            <h5 class="mb-3">Package Features</h5>
                            <div class="row">
                                @php
                                    $features = [
                                        'feature_ppc_module' => 'PPC Module',
                                        'feature_upwork_module' => 'Upwork Module',
                                        'feature_milestone_payments' => 'Milestone Payments',
                                        'feature_stripe' => 'Stripe',
                                        'feature_paypal' => 'PayPal',
                                        'feature_webhooks' => 'Webhooks',
                                        'feature_chargeback_tracking' => 'Chargeback Tracking',
                                        'feature_dual_invoicing' => 'Dual Invoicing',
                                        'feature_client_portal' => 'Client Portal',
                                        'feature_lead_prediction' => 'Lead Prediction',
                                        'feature_seller_leaderboard' => 'Seller Leaderboard',
                                        'feature_performance_bonus' => 'Performance Bonus',
                                        'feature_projects' => 'Projects',
                                        'feature_support_tickets' => 'Support Tickets',
                                        'feature_api_access' => 'API Access',
                                        'feature_custom_domain' => 'Custom Domain',
                                        'feature_white_label' => 'White Label',
                                    ];
                                @endphp
                                @foreach ($features as $field => $label)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="{{ $field }}" value="1"
                                                id="edit_{{ $field }}_{{ $package->id }}"
                                                @checked(old($field, $package->$field))>
                                            <label class="form-check-label" for="edit_{{ $field }}_{{ $package->id }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <hr>
                            <h5 class="mb-3">Display Settings</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Badge Text</label>
                                    <input type="text" name="badge_text" class="form-control" value="{{ old('badge_text', $package->badge_text) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $package->sort_order) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" @selected(old('status', $package->status) == 'active')>Active</option>
                                        <option value="inactive" @selected(old('status', $package->status) == 'inactive')>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" class="form-check-input" name="is_public" value="1"
                                            id="edit_is_public_{{ $package->id }}" @checked(old('is_public', $package->is_public))>
                                        <label class="form-check-label" for="edit_is_public_{{ $package->id }}">Public Package</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" class="form-check-input" name="is_popular" value="1"
                                            id="edit_is_popular_{{ $package->id }}" @checked(old('is_popular', $package->is_popular))>
                                        <label class="form-check-label" for="edit_is_popular_{{ $package->id }}">Popular Package</label>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-success btn-lg">Update Package</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">No packages found yet. Add your first package to get started.</div>
            </div>
        @endforelse
    </div>

    <div class="modal fade" id="addPackage" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addPackageLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPackageLabel">Add New Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('super-admin.pricing-packages.post') }}" id="addPackageForm">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Package Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter package name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Package Slug</label>
                            <select name="slug" class="form-select" required>
                                <option value="">— Select Package —</option>
                                <option value="crm-basic">CRM Basic</option>
                                <option value="crm-standard">CRM Standard</option>
                                <option value="crm-premium">CRM Premium</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monthly Price (USD)</label>
                                <input type="number" step="0.01" name="monthly_price" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yearly Price (USD)</label>
                                <input type="number" step="0.01" name="yearly_price" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monthly Price (PKR)</label>
                                <input type="number" step="0.01" name="monthly_price_pkr" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yearly Price (PKR)</label>
                                <input type="number" step="0.01" name="yearly_price_pkr" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Package Description</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Short package description..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Package Features</label>
                            <textarea name="features_html" id="features_html_add" rows="6" class="sa-wysiwyg form-control"></textarea>
                        </div>

                        <hr>
                        <h5 class="mb-3">Usage Limits</h5>
                        <div class="row">
                            @foreach ([
                                'max_brands' => ['Max Brands', 1],
                                'max_sellers' => ['Max Sellers', 2],
                                'max_admins' => ['Max Admins', 1],
                                'max_clients' => ['Max Clients', 50],
                                'max_leads_per_month' => ['Max Leads / Month', 50],
                                'max_orders' => ['Max Orders', 50],
                                'max_payment_links' => ['Max Payment Links', 50],
                                'max_account_keys' => ['Max Account Keys', 1],
                                'max_projects' => ['Max Projects', 0],
                                'max_storage_mb' => ['Storage (MB)', 512],
                            ] as $field => [$label, $default])
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" class="form-control" value="{{ $default }}">
                                </div>
                            @endforeach
                        </div>

                        <hr>
                        <h5 class="mb-3">Package Features</h5>
                        <div class="row">
                            @foreach ([
                                'feature_ppc_module' => ['PPC Module', true],
                                'feature_upwork_module' => ['Upwork Module', false],
                                'feature_milestone_payments' => ['Milestone Payments', false],
                                'feature_stripe' => ['Stripe', true],
                                'feature_paypal' => ['PayPal', false],
                                'feature_webhooks' => ['Webhooks', false],
                                'feature_chargeback_tracking' => ['Chargeback Tracking', false],
                                'feature_dual_invoicing' => ['Dual Invoicing', false],
                                'feature_client_portal' => ['Client Portal', false],
                                'feature_lead_prediction' => ['Lead Prediction', false],
                                'feature_seller_leaderboard' => ['Seller Leaderboard', false],
                                'feature_performance_bonus' => ['Performance Bonus', false],
                                'feature_projects' => ['Projects', false],
                                'feature_support_tickets' => ['Support Tickets', false],
                                'feature_api_access' => ['API Access', false],
                                'feature_custom_domain' => ['Custom Domain', false],
                                'feature_white_label' => ['White Label', false],
                            ] as $field => [$label, $checked])
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="{{ $field }}" value="1"
                                            id="add_{{ $field }}" @checked($checked)>
                                        <label class="form-check-label" for="add_{{ $field }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-success">Save Package</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js"></script>
    <script src="{{ asset('super-admin-assets/js/pricing.js') }}" defer></script>
@endpush
