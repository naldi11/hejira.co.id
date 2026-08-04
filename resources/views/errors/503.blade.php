{{--
    Halaman maintenance yang dilihat klien saat `php artisan down`.

    SENGAJA berdiri sendiri: tanpa @vite, tanpa font eksternal, tanpa aset apa pun
    dari luar. Halaman ini justru ditampilkan pada saat aplikasi paling rapuh —
    di tengah deploy, ketika manifest build bisa saja belum tersinkron. Kalau ia
    bergantung pada aset, klien akan melihat error mentah, bukan pesan maintenance.
    Jangan menambahkan @vite atau <link> ke sini.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    {{-- Muat ulang otomatis, supaya klien tidak perlu menekan refresh terus. --}}
    <meta http-equiv="refresh" content="60">
    <title>Sedang Pemeliharaan — HEJIRA</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand: #465fff;
            --brand-dark: #3641f5;
            --ink: #1d2939;
            --muted: #667085;
            --line: #e4e7ec;
            --surface: #ffffff;
            --bg: #f9fafb;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --ink: #f2f4f7;
                --muted: #98a2b3;
                --line: #1d2939;
                --surface: #101828;
                --bg: #0c111d;
            }
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: var(--bg);
            color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .card {
            width: 100%;
            max-width: 480px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 12px 32px rgba(16, 24, 40, 0.06);
        }

        .brand {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 2px;
            color: var(--brand);
            margin-bottom: 28px;
        }

        .icon-wrap {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(70, 95, 255, 0.1);
        }

        .icon-wrap svg { width: 34px; height: 34px; stroke: var(--brand); }

        /* Perputaran lambat: menandakan "sedang dikerjakan", bukan "rusak". */
        .spin { transform-origin: 50% 50%; animation: spin 4s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { .spin { animation: none; } }

        h1 { font-size: 22px; font-weight: 700; margin-bottom: 10px; letter-spacing: -0.3px; }
        .lead { color: var(--muted); font-size: 15px; margin-bottom: 24px; }

        .note {
            border: 1px dashed var(--line);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 24px;
        }
        .note strong { color: var(--ink); font-weight: 600; }

        .retry {
            display: inline-block;
            padding: 11px 26px;
            border-radius: 10px;
            background: var(--brand);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        .retry:hover { background: var(--brand-dark); }

        .foot { margin-top: 24px; font-size: 12px; color: var(--muted); }

        @media (max-width: 420px) {
            .card { padding: 32px 22px; border-radius: 16px; }
            h1 { font-size: 19px; }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">HEJIRA</div>

        <div class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <g class="spin">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1.08-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </g>
            </svg>
        </div>

        <h1>Sedang Dalam Pemeliharaan</h1>

        <p class="lead">
            Sistem HEJIRA sementara tidak dapat diakses karena sedang kami perbarui.
            Mohon maaf atas ketidaknyamanannya.
        </p>

        <div class="note">
            Data Anda <strong>aman</strong> dan tidak ada yang hilang.
            Layanan akan kembali normal dalam waktu singkat.
        </div>

        <a href="{{ url()->current() }}" class="retry">Coba Muat Ulang</a>

        <p class="foot">Halaman ini akan menyegarkan sendiri setiap 60 detik.</p>
    </main>
</body>
</html>
