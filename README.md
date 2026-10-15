# HEJIRA

Aplikasi operasional multiunit untuk Gudang, Hendhys, Jihans, dan Izzi. Backend menggunakan Laravel 13, autentikasi session, dan role/permission Spatie. Antarmuka utama menggunakan React 19 dan Inertia 3, dibangun dengan Vite 8 dan Tailwind 4.

## Peta proyek

| Lokasi | Tanggung jawab |
| --- | --- |
| `routes/gudang.php` | Purchase order, penerimaan, stok, transfer, retur, master data |
| `routes/hendhys.php`, `routes/jihans.php` | Produksi, distribusi, POS, shift, transaksi, laporan |
| `routes/izzi.php` | Bahan baku, resep/menu, penerimaan, POS, shift |
| `routes/owner.php` | Dashboard, laporan, pemantauan produksi, penghapusan stok |
| `routes/web.php` | Pemilihan cabang, transfer antarcabang, bahan produksi, stock opname |
| `app/Services` | Logika operasional stok, produksi, penjualan, dan laporan |
| `resources/js/Pages` | Halaman React per modul |
| `resources/js/lib/date.js`, `format.js` | Kalender WIB, parsing waktu, format tanggal dan Rupiah |
| `lang/id` | Pesan validasi, autentikasi, reset kata sandi, dan pagination Indonesia |

Akses halaman bergantung pada role, unit (`entity`), cabang aktif, dan shift kasir. Login yang berhasil diarahkan ke dashboard sesuai role.

## Pengaturan wilayah dan waktu

Konfigurasi regional untuk instalasi ini:

```dotenv
APP_NAME=HEJIRA
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID
DB_TIMEZONE=+07:00
```

- **Waktu operasional:** WIB, UTC+07:00. PHP/Laravel mengikuti `APP_TIMEZONE`; koneksi MySQL dan MariaDB mengikuti `DB_TIMEZONE`.
- **Bahasa:** kode Laravel `id`, kode browser `id-ID`. Fallback `en` menyediakan pesan framework ketika terjemahan Indonesia belum tersedia.
- **Angka dan uang:** format Indonesia, misalnya `1.234` dan `Rp 1.234.567`.
- **Tampilan tanggal:** misalnya `15 Okt 2026`; nama hari/bulan dari Carbon memakai locale Indonesia. Jam yang diformat helper frontend memiliki label `WIB`.
- **Input/form dan filter:** tanggal kalender tetap `YYYY-MM-DD`, bulan `YYYY-MM`, dan waktu operasional `HH:mm` dalam WIB.

Konfigurasi ini untuk aplikasi yang beroperasi di WIB. Frontend dan sejumlah aturan bisnis juga eksplisit memakai `Asia/Jakarta`; mengganti `APP_TIMEZONE` saja belum membuat aplikasi mendukung zona waktu per cabang.

### Kontrak data tanggal

Tanggal transaksi (`date`) adalah tanggal kalender bisnis. Tanggal pencatatan (`created_at`) adalah waktu kejadian pencatatan; keduanya dapat berbeda untuk transaksi yang dicatat belakangan.

Database dan query operasional proyek ini memakai sesi WIB. Timestamp SQL tanpa offset, seperti `2026-10-15 09:30:00`, dibaca frontend sebagai WIB. Timestamp ISO dengan `Z` atau offset adalah sebuah waktu absolut dan dikonversi ke WIB untuk ditampilkan. Laravel memang [menserialisasi cast tanggal Eloquent ke ISO UTC](https://laravel.com/framework/docs/13.x/eloquent-mutators#date-casting-serialization-and-timezones), sehingga `Z` pada respons JSON tetap valid meskipun aplikasi memakai WIB.

Gunakan helper `todayWib`, `weekRangeWib`, `endOfMonth`, `dateTimeLocalWib`, `formatDate`, `formatTime`, dan `formatDateTime` untuk kode baru. Hindari mengambil tanggal operasional dengan `new Date().toISOString().slice(0, 10)` karena hasilnya tanggal UTC. Tanggal `YYYY-MM-DD` harus tetap pada hari kalender yang sama, termasuk bila perangkat pengguna berada di luar WIB.

Command lama `app:fix-timezone`, `app:revert-timezone-shift`, `app:undo-double-timezone`, `app:fix-absolute`, dan `app:fix-audit` mengubah data historis secara langsung. Command tersebut bukan langkah setup. Koreksi data lama memerlukan audit terhadap data sumber dan backup tersendiri.

## Runtime

- **PHP 8.4**, dengan ekstensi yang diminta `composer check-platform-reqs`. Runtime lokal yang diverifikasi adalah **8.4.26**; `.php-version` juga menandai seri 8.4. Dependensi ekspor Excel yang terpasang (`phpoffice/phpspreadsheet`) membatasi PHP ke versi di bawah 8.5. Periksa runtime CLI dan web server secara terpisah.
- **Node.js:** `^20.19.0` atau `>=22.12.0`, sesuai engines Vite di lockfile. Node 24 dapat menjalankan toolchain ini.
- **MySQL/MariaDB:** koneksi dikonfigurasi di `.env`. Gunakan database khusus proyek dan pastikan servicenya berjalan.
- **Composer dan npm**, dengan dependency dipasang dari lockfile yang ada.
- **PDO SQLite** untuk test suite, yang memakai database `:memory:`.

## Setup lokal

Jalankan perintah dari direktori proyek setelah runtime tersedia di `PATH`.

1. Untuk checkout baru, salin `.env.example` menjadi `.env`. Untuk instalasi yang sudah memiliki `.env`, pertahankan key, kredensial, dan konfigurasi yang ada; sesuaikan variabel regional di atas.
2. Pasang dependency sesuai lockfile:

   ```sh
   composer install
   npm ci --ignore-scripts
   ```

3. Atur `DB_*` ke database lokal yang benar. Buat application key dengan `php artisan key:generate` hanya jika `APP_KEY` belum diisi. Pertahankan key instalasi yang sudah digunakan.
4. Bersihkan cache konfigurasi setelah mengubah `.env`:

   ```sh
   php artisan config:clear
   ```

5. Untuk database baru yang memang disiapkan untuk proyek ini, jalankan `php artisan migrate`. Pada database berisi transaksi, tinjau migrasi yang belum diterapkan dan backup sebelum perubahan skema.
6. Buat akses foto/berkas public dan bangun frontend:

   ```sh
   php artisan storage:link
   npm run build
   ```

7. Jalankan aplikasi:

   ```sh
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   Sesuaikan `APP_URL` dengan alamat akses yang dipakai, misalnya `http://127.0.0.1:8000`. Jika memakai virtual host Laragon, arahkan document root ke direktori `public`, lalu pakai URL virtual host tersebut pada `APP_URL`. Saat mengembangkan frontend, jalankan `npm run dev` di terminal terpisah.

### PHP 8.4 pada Windows/Laragon

Runtime lokal berada di `C:\laragon\bin\php\php-8.4.26-Win32-vs17-x64`.
Pilihan PHP Laragon dan konfigurasi FastCGI Apache menggunakan direktori ini.
Ekstensi `zip`, `gd`, `mbstring`, `intl`, `pdo_mysql`, `pdo_sqlite`, dan `sqlite3`
sudah diaktifkan. PHP CLI juga memakai `date.timezone=Asia/Jakarta`.

Terminal yang sudah terbuka dapat tetap mewarisi PATH versi lama. Wrapper proyek
memilih PHP 8.4 secara eksplisit, sehingga dapat langsung dipakai:

```powershell
.\scripts\php84.cmd -v
.\scripts\php84.cmd artisan serve --host=127.0.0.1 --port=8000
.\scripts\php84.cmd artisan test
```

Untuk menjadikan PHP 8.4 sebagai perintah `php` bawaan, script
`scripts/set-php84-path.ps1` memperbarui entri PHP Laragon pada Machine PATH
dengan hak Administrator. Script menyimpan backup PATH di
`storage/app/php-runtime` dan mempertahankan entri lain. Setelah perubahan,
buka ulang aplikasi terminal dan periksa `php -v` serta `where.exe php`.

Buka aplikasi melalui **http://127.0.0.1:8000** atau **http://localhost:8000**.
`0.0.0.0` adalah alamat bind untuk menerima koneksi pada semua antarmuka;
jangan memakainya sebagai alamat tujuan browser. Hentikan server lama sebelum
menjalankan ulang agar port 8000 tersedia.

phpMyAdmin lokal telah diperbarui ke **5.2.3** untuk kompatibilitas PHP 8.4.
Konfigurasi koneksi lama dipertahankan. Pengaturan `.user.ini` khusus phpMyAdmin
menyimpan sesi di `C:\laragon\tmp\phpmyadmin-sessions`, karena direktori sementara
Windows tidak dapat ditulis oleh proses FastCGI lokal. Instalasi sebelumnya
tersimpan di `C:\laragon\etc\apps\phpMyAdmin-5.2.0-backup-20261015`.

Script `composer setup` bawaan proyek menjalankan `key:generate` serta `migrate --force` tanpa membedakan instalasi baru dan instalasi berisi data. Untuk proyek yang sudah berjalan, gunakan langkah terpisah di atas.

## Queue, notifikasi, dan scheduler

`.env.example` memakai `BROADCAST_CONNECTION=log` agar instalasi baru tidak membutuhkan WebSocket. Untuk notifikasi realtime, isi konfigurasi Reverb beserta variabel `VITE_REVERB_*`, set `BROADCAST_CONNECTION=reverb`, bangun ulang frontend, lalu jalankan `php artisan reverb:start`.

Jika `QUEUE_CONNECTION=database`, jalankan worker `php artisan queue:work`. Restart worker setelah perubahan konfigurasi agar proses yang berjalan lama membaca pengaturan terbaru.

`routes/console.php` menjadwalkan `production:check-stock` setiap menit, dengan logika batas distribusi Hendhys pukul **17.00 WIB**. Jalankan `php artisan schedule:work` saat pengembangan, atau pasang pemicu `php artisan schedule:run` setiap menit pada penjadwal server. Pemicu tersebut harus berjalan agar monitoring otomatis aktif.

## Pemeriksaan

```sh
composer check-platform-reqs
php artisan about --only=environment,cache,drivers
node --test tests/js/date.test.mjs tests/Frontend/*.test.mjs
php artisan test
npm run build
```

Test PHP menggunakan SQLite di memori sesuai `phpunit.xml`. Aktifkan `pdo_sqlite` dan `sqlite3` pada PHP pengujian. Untuk PHP Laragon dengan DLL SQLite yang sudah tersedia, ekstensi juga dapat diaktifkan hanya untuk invocation pengujian:

```sh
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit
```

Test tanggal mencakup pergantian hari WIB, pergantian bulan/tahun, timestamp SQL dan ISO, serta hasil yang sama ketika zona perangkat berbeda. Tes terjemahan memeriksa pesan validasi, nama field tanggal/waktu, autentikasi, dan reset kata sandi.

`tests/Feature/Exports/ExcelExportTest.php` mengunduh XLSX asli melalui endpoint
yang diautentikasi dan membukanya kembali dengan PhpSpreadsheet. Cakupannya
template produk, customer, dan supplier di Gudang, Jihans, dan Hendhys, beserta
header, contoh data, nol di depan nomor telepon, dropdown, dan nilai numerik.
Rekap produksi menyediakan CSV/XLSX; laporan Owner menyediakan CSV/PDF/XLSX.
Pengujian CSV memastikan filter tanggal dan jumlah produksi aktual benar.

### Ekspor laporan XLSX

Di **Owner → Laporan Omset**, gunakan **Ekspor Excel (.xlsx)**. Di
**Jihans → Rekap Produksi**, tersedia **Ekspor Excel (.xlsx)** dan **Ekspor CSV**.
Semua unduhan mengikuti filter yang sudah diterapkan pada tabel; sesudah mengedit
filter, tekan **Terapkan** sebelum mengekspor.

| Laporan | Isi XLSX | Aturan data |
| --- | --- | --- |
| Omset Owner | Periode, Jihans, Hendhys, Izzi bersih, total, filter, waktu dibuat | Hanya transaksi lunas Jihans/Hendhys; Izzi memakai nilai bersih setelah pembatalan |
| Rekap produksi Jihans | Nama produk, jumlah produksi aktual, batas tanggal, waktu dibuat, jumlah jenis produk | Prediksi tidak dihitung; produk dengan ID berbeda tetap terpisah meskipun namanya sama |

Endpoint yang ada menerima `format=xlsx`:

```text
/owner/reports/export?format=xlsx&unit_bisnis=all&periode=harian&date_from=2026-10-01&date_to=2026-10-15
/jihans/production/recap/export?format=xlsx&date_from=2026-10-01&date_to=2026-10-15
```

Tanggal harian dan metadata tanggal disimpan sebagai nilai tanggal Excel,
bukan teks. Waktu dibuat dikonversi ke `Asia/Jakarta` sebelum disimpan dan
ditampilkan dengan label WIB. Batas awal dan akhir mencakup kedua hari tersebut;
filter produksi hari/minggu/bulan juga dihitung dalam WIB.

Nominal Owner tetap numerik dengan format Rupiah `[$Rp-421] #,##0.00`.
Dua angka desimal mempertahankan pecahan nominal yang mungkin ada. Pemisah
ribuan/desimal mengikuti pengaturan regional aplikasi spreadsheet. Kuantitas
produksi memakai angka biasa, tanpa mata uang; footer menghitung jenis produk
karena jumlah antarproduk dapat memiliki satuan berbeda.

Workbook menyertakan header, filter, panel beku, lebar kolom, dan pengaturan
cetak. Teks seperti nama produk yang diawali `=` tetap disimpan sebagai teks.
Data kosong menghasilkan workbook yang tetap dapat dibuka.

Tes `OwnerReportXlsxTest` dan `ProductionRecapXlsxTest` mengunduh file XLSX asli,
membukanya kembali, lalu mencocokkan nilainya dengan laporan di layar/CSV,
filter periode, tipe angka/tanggal, format Rupiah, batas tengah malam WIB,
akses pengguna, dan data kosong. Seluruh fixture memakai SQLite di memori.

## Satuan master dan mutasi Gudang

Satuan pada master produk menjadi acuan saldo saat ini dan mutasi. Kartu stok
lama diselaraskan ke `master_products.unit_id`, dengan angka saldo tetap;
penyelarasan tidak mengalikan atau membagi jumlah. Perubahan satuan melalui
edit/impor master juga menyelaraskan kartu stok saat ini dan lot yang masih
bersaldo. Snapshot rincian transaksi lama tetap dipertahankan.

Mutasi mendukung Gudang Utama ke/dari cabang Hendhys, Jihans, dan Izzi.
Gudang memakai saldo yang sudah ada di `jihans_gudang_stock`, beserta kartu
stok dan pencatatan lot produksi yang terkait. Gudang menggunakan satu saldo
bersama; hanya satu lokasi Gudang ditawarkan di formulir. Pengirim tetap
dibatasi pada lokasi yang boleh dikelola akun tersebut. Sumber Gudang dikelola
Super Admin Gudang/semua unit. Owner tetap tidak mencatat transaksi mutasi.

Setelah memasang kode dan membangun frontend, jalankan migrasi baru:

```sh
php artisan migrate --path=database/migrations/2026_10_15_170000_use_master_units_and_enable_warehouse_transfers.php
```

Migrasi menambahkan relasi dokumen mutasi pada kartu stok Gudang, menyediakan
identitas Gudang bila belum ada, serta menyelaraskan satuan kartu stok.
Simpan backup database sebelum penerapan pada instalasi berisi transaksi.
Rollback skema tidak mengembalikan satuan master atau menghapus identitas
Gudang yang sudah dipakai. Pembatalan mutasi selesai tetap mengikuti alur
aplikasi yang sudah ada; pencatatan ganda dicegah oleh kunci permintaan.

Tes `WarehouseTransferAndMasterUnitsTest` mencakup enam arah mutasi
Gudang/cabang, satuan master, saldo tanpa konversi otomatis, akses pengirim,
pencatatan ganda, stok tidak cukup, lot produksi, serta visibilitas barang
yang diterima pada halaman stok Gudang.

## Tutorial Izzi Kebab

Tutorial untuk pemula terdiri dari 41 slide dan tersedia dalam dua format:

- [PowerPoint yang dapat diedit](output/tutorial-izzi/Panduan_Pemula_Izzi_Kebab_Lengkap.pptx)
- [PDF untuk dibaca dan dibagikan](output/tutorial-izzi/Panduan_Pemula_Izzi_Kebab.pdf)

Tutorial menggunakan screenshot aplikasi dengan data latihan yang terpisah
dari database operasional. Materinya mencakup admin dan kasir: bahan, satuan
kemasan, resep, penerimaan, stok, pembayaran, mutasi ke Gudang, pembatalan,
penutupan laci, dan stock opname dengan izin Owner.
