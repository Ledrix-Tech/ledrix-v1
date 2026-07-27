(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        initToastr();
        initClock();
        initLegacyBootstrap();
        initTooltips();
        initModalClickOutside();
    });

    function initSidebar() {
        var toggle = document.getElementById('crmMenuToggle');
        var sidebar = document.getElementById('crmSidebar');
        var overlay = document.getElementById('crmSidebarOverlay');

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
        var el = document.getElementById('crm-live-time');
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

    /** Map BS4 data-* attributes to BS5 for existing page markup */
    function initLegacyBootstrap() {
        document.querySelectorAll('[data-toggle]').forEach(function (el) {
            var toggle = el.getAttribute('data-toggle');
            if (toggle && !el.hasAttribute('data-bs-toggle')) {
                el.setAttribute('data-bs-toggle', toggle);
            }
        });

        document.querySelectorAll('[data-target]').forEach(function (el) {
            var target = el.getAttribute('data-target');
            if (target && !el.hasAttribute('data-bs-target')) {
                el.setAttribute('data-bs-target', target);
            }
        });

        document.querySelectorAll('[data-dismiss]').forEach(function (el) {
            if (!el.hasAttribute('data-bs-dismiss')) {
                el.setAttribute('data-bs-dismiss', el.getAttribute('data-dismiss'));
            }
        });

        if (typeof window.jQuery !== 'undefined' && window.bootstrap) {
            var $ = window.jQuery;

            if (!$.fn.modal || !$.fn.modal._crmPatched) {
                $.fn.modal = function (action) {
                    return this.each(function () {
                        var instance = bootstrap.Modal.getOrCreateInstance(this);
                        if (action === 'show') {
                            instance.show();
                        } else if (action === 'hide') {
                            instance.hide();
                        } else if (action === 'toggle') {
                            instance.toggle();
                        } else if (!action) {
                            instance.show();
                        }
                    });
                };
                $.fn.modal._crmPatched = true;
            }
        }
    }

    function initTooltips() {
        if (typeof window.bootstrap === 'undefined') {
            return;
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]').forEach(function (el) {
            if (!el.hasAttribute('data-bs-toggle')) {
                el.setAttribute('data-bs-toggle', 'tooltip');
            }
            bootstrap.Tooltip.getOrCreateInstance(el);
        });
    }

    function initModalClickOutside() {
        if (typeof window.jQuery === 'undefined') {
            return;
        }

        var $ = window.jQuery;
        $(document).on('click', function (event) {
            var modal = $('#ticketInfo');
            if (modal.length && modal.hasClass('show') && $(event.target).closest('.modal-content').length === 0) {
                modal.modal('hide');
            }
        });
    }
})();
