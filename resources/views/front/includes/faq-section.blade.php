{{-- Shared FAQ accordion (marketing pages) --}}
@php
    $faqs = config('seo.faq', []);
    $limit = $limit ?? null;
    if ($limit) {
        $faqs = array_slice($faqs, 0, (int) $limit);
    }
@endphp
@if (count($faqs) > 0)
    <section class="mkt-section mkt-section-alt" id="faq" aria-labelledby="faq-heading">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="mkt-section-title" id="faq-heading">Frequently asked questions</h2>
                <p class="mkt-section-lead">Common questions about Ledrix CRM, trials, and multi-tenant sales workflows.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="accordion mkt-faq-accordion" id="mktFaqAccordion">
                        @foreach ($faqs as $i => $faq)
                            <div class="accordion-item">
                                <h3 class="accordion-header" id="faq-heading-{{ $i }}">
                                    <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq-collapse-{{ $i }}"
                                        aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                        aria-controls="faq-collapse-{{ $i }}">
                                        {{ $faq['question'] }}
                                    </button>
                                </h3>
                                <div id="faq-collapse-{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                                    aria-labelledby="faq-heading-{{ $i }}" data-bs-parent="#mktFaqAccordion">
                                    <div class="accordion-body text-secondary">
                                        {{ $faq['answer'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($limit && count(config('seo.faq', [])) > $limit)
                        <p class="text-center mt-4 mb-0">
                            <a href="{{ route('faq.get') }}" class="fw-semibold text-decoration-none">View all FAQs →</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
