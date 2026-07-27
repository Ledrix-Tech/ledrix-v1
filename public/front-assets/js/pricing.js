(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('pricing-billing-toggle');
        if (!toggle) {
            return;
        }

        var monthlyLabel = document.querySelector('[data-billing-label="monthly"]');
        var yearlyLabel = document.querySelector('[data-billing-label="yearly"]');

        function formatPrice(value) {
            var num = parseFloat(value);
            if (Number.isNaN(num)) {
                return '0';
            }

            return num % 1 === 0 ? num.toFixed(0) : num.toFixed(2);
        }

        function updatePrices(isYearly) {
            document.querySelectorAll('[data-price-monthly]').forEach(function (el) {
                var monthly = parseFloat(el.dataset.priceMonthly);
                var yearly = parseFloat(el.dataset.priceYearly);
                var amountEl = el.querySelector('.amount');
                var periodEl = el.querySelector('.period');

                if (!amountEl) {
                    return;
                }

                if (isYearly && yearly > 0) {
                    amountEl.textContent = '$' + formatPrice(yearly);
                    if (periodEl) {
                        periodEl.textContent = '/year';
                    }
                } else {
                    amountEl.textContent = '$' + formatPrice(monthly);
                    if (periodEl) {
                        periodEl.textContent = '/month';
                    }
                }
            });

            if (monthlyLabel) {
                monthlyLabel.classList.toggle('active', !isYearly);
            }

            if (yearlyLabel) {
                yearlyLabel.classList.toggle('active', isYearly);
            }
        }

        toggle.addEventListener('change', function () {
            updatePrices(toggle.checked);
        });

        updatePrices(false);
    });
})();
