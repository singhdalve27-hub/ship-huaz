<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <script>
            // Suppress external browser extension runtime errors (e.g. Chrome Web Vitals extension reportAllChanges bug)
            (function () {
                function shouldSuppress(err, msg) {
                    var str = (msg || '') + ' ' + (err && err.message ? err.message : '') + ' ' + (err && err.stack ? err.stack : '');
                    return str.indexOf('startTime') !== -1 || str.indexOf('reportAllChanges') !== -1;
                }
                var prevOnError = window.onerror;
                window.onerror = function (msg, url, line, col, error) {
                    if (shouldSuppress(error, msg)) return true;
                    if (prevOnError) return prevOnError.apply(this, arguments);
                };
                window.addEventListener('error', function (e) {
                    if (shouldSuppress(e.error, e.message)) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        return true;
                    }
                }, true);
                window.addEventListener('unhandledrejection', function (e) {
                    if (shouldSuppress(e.reason, e.reason && e.reason.message)) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                });
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com"/>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
