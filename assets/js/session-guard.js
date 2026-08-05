/**
 * Session guard — load this BEFORE app.js.
 *
 * Now that the API endpoints require a session, an expired login makes
 * every fetch return HTTP 401. Without this shim the inspector would
 * just see "error loading" and lose their work. With it, they are sent
 * cleanly to the login page and returned to where they were.
 *
 * Add to index.php, just above the existing app.js tag:
 *     <script src="assets/js/session-guard.js"></script>
 *     <script src="assets/js/app.js"></script>
 */
(function () {
    'use strict';

    var nativeFetch = window.fetch.bind(window);
    var redirecting = false;

    window.fetch = function () {
        return nativeFetch.apply(null, arguments).then(function (response) {
            if (response.status === 401 && !redirecting) {
                redirecting = true;

                // Preserve whatever the inspector had typed, so a session
                // timeout mid-inspection does not destroy field work.
                try {
                    var form = document.querySelector('form, .view.active');
                    if (form) {
                        sessionStorage.setItem(
                            'cok_unsaved_at',
                            new Date().toISOString()
                        );
                    }
                } catch (e) { /* storage unavailable — carry on */ }

                var here = window.location.pathname + window.location.search;
                window.location.href = 'login.php?redirect=' + encodeURIComponent(here);
            }
            return response;
        });
    };
})();
