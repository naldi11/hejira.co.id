import InteractiveWallpaper from '@/Components/InteractiveWallpaper';

/** Centered card layout for the auth pages (login, register, password, etc.). */
export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-950 px-4 py-10 font-sans antialiased select-none">
            {/* Interactive Futuristic OS Wallpaper */}
            <div className="absolute inset-0 z-0">
                <InteractiveWallpaper />
            </div>

            {/* White Glassmorphic Auth Card */}
            <div className="relative z-10 w-full max-w-md overflow-hidden rounded-3xl border border-slate-100 bg-white/95 backdrop-blur-md px-8 py-10 shadow-2xl">
                {children}
            </div>
        </div>
    );
}

