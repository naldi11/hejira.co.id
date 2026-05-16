<div {{ $attributes->merge(['class' => 'flex items-center justify-center']) }}>
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-full h-full drop-shadow-md">
        <defs>
            <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#2563eb;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:1" />
            </linearGradient>
        </defs>
        <!-- Background Circle with border -->
        <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="1.5" class="text-blue-200" />
        
        <!-- Combined Entity Symbol (B for Bisnis) -->
        <path d="M35 25V75H55C65 75 70 70 70 62.5C70 57.5 67.5 52.5 60 50C65 47.5 67.5 42.5 67.5 37.5C67.5 30 62.5 25 52.5 25H35Z" 
              fill="url(#logoGradient)" 
              class="drop-shadow-sm" />
        
        <!-- Inner Details -->
        <path d="M42 32H52.5C57.5 32 60 35 60 37.5C60 40 57.5 43 52.5 43H42V32Z" fill="white" opacity="0.9" />
        <path d="M42 50H55C60 50 62.5 53 62.5 57.5C62.5 62 60 68 55 68H42V50Z" fill="white" opacity="0.9" />

        <!-- Highlight Dot -->
        <circle cx="75" cy="25" r="8" fill="#f59e0b" class="animate-pulse" />
    </svg>
</div>
