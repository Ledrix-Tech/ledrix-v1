(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.jQuery === 'undefined') {
            return;
        }

        var $ = window.jQuery;
        var config = window.SaTenantConfig || {};

        $(document).on('click', '.sa-tenant-status-btn, .banUser, .unbanUser', function () {
            var userId = $(this).data('id');
            var newStatus = $(this).data('status');
            var actionText = newStatus === 'active' ? 'activate' : 'suspend';

            if (!confirm('Are you sure you want to ' + actionText + ' this tenant?')) {
                return;
            }

            $.ajax({
                url: config.statusUrl || '',
                type: 'POST',
                data: {
                    _token: config.csrf || '',
                    user_id: userId,
                    status: newStatus,
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success('Tenant ' + actionText + 'd successfully.');
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        toastr.error('Could not update tenant status.');
                    }
                },
                error: function () {
                    toastr.error('An error occurred. Please try again.');
                },
            });
        });
    });
})();
