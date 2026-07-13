<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payroll · {{ config('app.name', 'AMS-V1') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            scrollbar-width: thin;
            scrollbar-color: #D6C79A transparent;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background: #D6C79A;
            border-radius: 8px;
        }

        body {
            background: #F6F1E1; /* ivory bg */
        }

        .num {
            font-family: 'IBM Plex Mono', monospace;
            font-variant-numeric: tabular-nums;
        }

        .fade-in {
            animation: fadeIn .35s ease both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }

        .drawer-enter {
            transition: transform .38s cubic-bezier(.16, 1, .3, 1);
        }

        .brass-underline {
            background-image: linear-gradient(#C6941C, #C6941C);
            background-size: 0% 2px;
            background-repeat: no-repeat;
            background-position: left bottom;
            transition: background-size .25s ease;
        }

        .brass-underline:hover {
            background-size: 100% 2px;
        }

        [x-cloak] {
            display: none !important;
        }

        .tooltip-trigger:hover .tooltip-bubble {
            opacity: 1;
            transform: translateY(0) translateX(-50%);
            pointer-events: auto;
        }

        .tooltip-bubble {
            opacity: 0;
            transform: translateY(4px) translateX(-50%);
            transition: all .18s ease;
            pointer-events: none;
        }

        .pipeline-line {
            background: repeating-linear-gradient(90deg, #E3D8B9 0 6px, transparent 6px 12px);
        }
    </style>
</head>
<body class="font-sans antialiased text-ink">
    @yield('content')
</body>
</html>
