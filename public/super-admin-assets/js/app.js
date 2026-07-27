(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        initToastr();
        initClock();
    });

    function initSidebar() {
        var toggle = document.getElementById('saMenuToggle');
        var sidebar = document.getElementById('saSidebar');
        var overlay = document.getElementById('saSidebarOverlay');

        if (!toggle || !sidebar) {
            return;
        }

        function open() {
            sidebar.classList.add('is-open');
            overlay?.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function close() {
            sidebar.classList.remove('is-open');
            overlay?.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', function () {
            sidebar.classList.contains('is-open') ? close() : open();
        });

        overlay?.addEventListener('click', close);
    }

    function initToastr() {
        if (typeof window.toastr === 'undefined') {
            return;
        }

        toastr.options.timeOut = 8000;
        var flash = window.LedrixFlash || {};

        ['success', 'info', 'warning', 'error', 'status'].forEach(function (type) {
            if (flash[type]) {
                toastr[type === 'status' ? 'info' : type](flash[type]);
            }
        });
    }

    function initClock() {
        var el = document.getElementById('sa-live-time');
        if (!el) {
            return;
        }

        function tick() {
            var now = new Date();
            el.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }

        tick();
        setInterval(tick, 1000);
    }
})();
