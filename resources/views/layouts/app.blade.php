<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'AMS') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-vellum font-sans antialiased min-h-screen">

    <div class="flex">

        <!-- Sidebar -->
        <x-sidebar />

        <!-- Main Area -->
        <div class="flex-1 min-h-screen">

            <!-- Page Content -->
            <main class="px-11 py-9 w-full {{ $attributes->has('wide') ? 'max-w-none' : 'max-w-[1180px] mx-auto' }}">
                <div id="page-wrapper">
                    @isset($header)
                        <header class="page flex items-end justify-between mb-8 pb-5 border-b border-hairline">
                            <div>
                                {{ $header }}
                            </div>
                            <div class="clock text-right font-mono">
                                <div class="time text-[19px] text-brass tracking-wider" id="clock-time">{{ now()->timezone('Asia/Kolkata')->format('H:i:s') }}</div>
                                <div class="date text-[11px] text-vellum-faint mt-1 tracking-wider" id="clock-date">{{ now()->timezone('Asia/Kolkata')->format('l, d F Y') }}</div>
                            </div>
                        </header>
                    @endisset

                    {{ $slot }}
                </div>
            </main>

        </div>

    </div>

    <!-- Active Clock Ticker Script -->
    <script>
        function tick(){
            var now = new Date();
            var t = now.toLocaleTimeString('en-IN', { hour12:false, timeZone:'Asia/Kolkata' });
            var clockEl = document.getElementById('clock-time');
            if (clockEl) {
                clockEl.textContent = t;
            }
        }
        tick();
        setInterval(tick, 1000);
    </script>

    <!-- Motion and Micro-animations script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Page Transition Fade + Slide
            const pageWrapper = document.getElementById('page-wrapper');
            if (pageWrapper) {
                requestAnimationFrame(() => {
                    pageWrapper.classList.add('page-active');
                });
            }

            // Intercept sidebar links to animate out
            const sidebarLinks = document.querySelectorAll('aside a, .sidebar-nav a');
            sidebarLinks.forEach(link => {
                const href = link.getAttribute('href');
                const onclick = link.getAttribute('onclick');
                if (href && !href.startsWith('#') && !onclick && link.target !== '_blank') {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (pageWrapper) {
                            pageWrapper.classList.remove('page-active');
                        }
                        setTimeout(() => {
                            window.location.href = href;
                        }, 180);
                    });
                }
            });

            // 2. 3D Perspective Tilt and Lift on Hover
            const tiltCards = document.querySelectorAll('.stat-card, .profile-card');
            tiltCards.forEach(card => {
                card.style.transition = 'transform 0.1s ease-out, border-color 0.15s ease, box-shadow 0.15s ease';
                
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const w = rect.width;
                    const h = rect.height;
                    
                    const normX = (x / w) - 0.5;
                    const normY = (y / h) - 0.5;
                    
                    const rotateX = -normY * 6; // max 3 degrees tilt
                    const rotateY = normX * 6;  // max 3 degrees tilt
                    
                    card.style.transform = `translateY(-2px) perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0) perspective(800px) rotateX(0deg) rotateY(0deg)';
                });
            });

            // 3. Count-up Animation on Load (if no query parameters exist)
            const hasQuery = window.location.search !== "";
            if (!hasQuery) {
                const statValues = document.querySelectorAll('.stat-value');
                statValues.forEach(el => {
                    const originalText = el.innerHTML.trim();
                    const match = originalText.match(/([0-9]+(?:\.[0-9]+)?)/);
                    if (match) {
                        const targetVal = parseFloat(match[1]);
                        const prefix = originalText.substring(0, match.index);
                        const suffix = originalText.substring(match.index + match[0].length);
                        const duration = 600; // 600ms count-up
                        const startTime = performance.now();
                        
                        function updateCounter(currentTime) {
                            const elapsed = currentTime - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            const ease = progress * (2 - progress); // easeOutQuad
                            const currentVal = ease * targetVal;
                            
                            const decimalPlaces = match[1].includes('.') ? match[1].split('.')[1].length : 0;
                            const formattedVal = currentVal.toFixed(decimalPlaces);
                            
                            el.innerHTML = prefix + formattedVal + suffix;
                            
                            if (progress < 1) {
                                requestAnimationFrame(updateCounter);
                            } else {
                                el.innerHTML = originalText;
                            }
                        }
                        requestAnimationFrame(updateCounter);
                    }
                });
            }
        });
    </script>
</body>
</html>