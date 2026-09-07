<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#1d4ed8"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="default"><meta name="apple-mobile-web-app-title" content="SAPA PPKD"><link rel="manifest" href="{{ route('pwa.manifest') }}"><link rel="icon" href="{{ route('pwa.icon', 192) }}" type="image/png"><link rel="apple-touch-icon" href="{{ route('pwa.icon', 192) }}"><title inertia>SAPA PPKD</title>@routes @viteReactRefresh @vite(['resources/css/app.css','resources/js/app.tsx']) @inertiaHead</head>
<body class="font-sans antialiased">@inertia</body>
</html>
