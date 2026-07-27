{{-- Pricing-page FAQ accordion (billing & trial questions only) --}}
@php
$faqs = $faqs ?? config('seo.pricing_faq', []);
$accordionId = $accordionId ?? 'pricingFaq';
@endphp

@if (count($faqs) > 0)
<section class="pricing-faq-section" aria-labelledby="pricing-faq-heading">
    <div class="container">
        <h2 class="text-center mb-4" id="pricing-faq-heading">Pricing &amp; trial FAQs</h2>
        <p class="text-center mb-4">Common questions about Ledrix CRM, trials, and multi-tenant sales workflows.</p>
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="accordion" id="{{ $accordionId }}">
                    @foreach ($faqs as $i => $faq)
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="{{ $accordionId }}-heading-{{ $i }}">
                            <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}-body-{{ $i }}"
                                aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                aria-controls="{{ $accordionId }}-body-{{ $i }}">
                                {{ $faq['question'] }}
                            </button>
                        </h3>
                        <div id="{{ $accordionId }}-body-{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                            aria-labelledby="{{ $accordionId }}-heading-{{ $i }}"
                            data-bs-parent="#{{ $accordionId }}">
                            <div class="accordion-body text-secondary">{{ $faq['answer'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif