(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var phoneInput = document.querySelector('#phone');

        if (!phoneInput || !window.intlTelInput) {
            return;
        }

        window.intlTelInput(phoneInput, {
            initialCountry: 'pk',
            preferredCountries: ['pk', 'ae', 'us', 'gb', 'ca'],
            separateDialCode: true,
            nationalMode: false,
            autoPlaceholder: 'aggressive',
        });

        var wrapper = phoneInput.closest('.iti');
        if (wrapper) {
            wrapper.style.width = '100%';
            wrapper.style.maxWidth = '100%';
        }

        phoneInput.style.width = '100%';
    });
})();
