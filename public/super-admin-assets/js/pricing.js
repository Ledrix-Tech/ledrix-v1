(function () {
    'use strict';

    var editorDefaults = {
        license_key: 'gpl',
        menubar: false,
        statusbar: false,
        promotion: false,
        branding: false,
        plugins: 'lists link autolink code table',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
        height: 280,
        content_style: 'body { font-family: Segoe UI, system-ui, sans-serif; font-size: 14px; }',
    };

    function initEditor(textarea) {
        if (!textarea || !textarea.id || typeof window.tinymce === 'undefined') {
            return;
        }

        if (window.tinymce.get(textarea.id)) {
            return;
        }

        window.tinymce.init(Object.assign({}, editorDefaults, {
            target: textarea,
        }));
    }

    function destroyEditor(id) {
        var editor = window.tinymce.get(id);
        if (editor) {
            editor.remove();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.tinymce === 'undefined') {
            return;
        }

        document.querySelectorAll('textarea.sa-wysiwyg').forEach(function (textarea) {
            if (textarea.closest('#addPackage')) {
                return;
            }
            initEditor(textarea);
        });

        var modal = document.getElementById('addPackage');
        if (!modal) {
            return;
        }

        modal.addEventListener('shown.bs.modal', function () {
            initEditor(document.getElementById('features_html_add'));
        });

        modal.addEventListener('hidden.bs.modal', function () {
            destroyEditor('features_html_add');
        });
    });
})();
