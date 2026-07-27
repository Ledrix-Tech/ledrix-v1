/**
 * CRM Orders — paylink toggle & copy
 */
(function ($) {
    'use strict';

    $(document).on('click', '.togglePaylink', function (e) {
        e.preventDefault();
        var $el = $(this);
        var id = $el.data('id');
        var newStatus = $el.data('status');

        $.ajax({
            url: window.LedrixPaylinkToggleUrl || '',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id,
                is_active_link: newStatus
            },
            success: function (res) {
                if (res.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message);
                    }
                    setTimeout(function () {
                        location.reload();
                    }, 800);
                }
            }
        });
    });

    $(document).on('click', '.copyBtn', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        if (!url) return;

        navigator.clipboard.writeText(url).then(function () {
            var original = $btn.html();
            $btn.addClass('copied').html('<i class="bi bi-check2"></i> Copied');
            setTimeout(function () {
                $btn.removeClass('copied').html(original);
            }, 2500);
        });
    });
})(jQuery);
