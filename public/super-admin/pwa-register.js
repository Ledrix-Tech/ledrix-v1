/**
 * Registers the Super Admin PWA service worker (scope: /super-admin/).
 * Safe no-op outside secure contexts / unsupported browsers.
 */
(function () {
  if (!('serviceWorker' in navigator)) {
    return;
  }

  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/super-admin/sw.js?v=3', { scope: '/super-admin/' }).catch(function () {
      // Ignore registration failures (HTTP local, unsupported, etc.)
    });
  });
})();
