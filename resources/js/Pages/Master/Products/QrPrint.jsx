import { Head, Link, router } from '@inertiajs/react';
import { useState, useMemo, useEffect } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatRupiah } from '@/lib/format';
import Button from '@/Components/ui/button/Button';
import Barcode from 'react-barcode';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout, OwnerLayout };
const route = window.route;

const PX_PER_MM = 96 / 25.4;

// CODE128 wajib punya quiet zone (area kosong) minimal 10 modul di kiri & kanan.
// Tanpa ini scanner sering gagal baca walaupun barnya tercetak sempurna.
const QUIET_ZONE_MODULES = 10;

// Lebar 1 modul (bar tersempit) dalam px CSS. Printer thermal 203 dpi punya
// 1 dot = 0,125 mm, jadi 1 modul minimal ~2 dot (0,25 mm ≈ 0,95 px). Di bawah itu
// printer membulatkan lebar bar secara acak ke 1 atau 2 dot dan rasio barcode rusak.
const MIN_MODULE_PX = 0.9;
const MAX_MODULE_PX = 3;

// Perkiraan jumlah modul CODE128 mengikuti aturan auto-switch JsBarcode:
// deretan angka panjang dikodekan subset C (2 digit jadi 1 simbol), sisanya subset B.
// Tiap simbol = 11 modul, ditutup stop pattern 13 modul.
const estimateCode128Modules = (value) => {
    const s = String(value ?? '');
    if (!s) return 0;
    let symbols = 1;   // start — sekaligus menentukan subset awal, jadi tak perlu switch
    let mode = null;   // 'B' | 'C'
    let i = 0;
    while (i < s.length) {
        const run = /^\d+/.exec(s.slice(i));
        const len = run ? run[0].length : 0;
        const atEdge = i === 0 || i + len === s.length;
        // Subset C baru menguntungkan untuk run >= 4 digit (>= 2 bila di ujung string)
        if (len >= 4 || (atEdge && len >= 2)) {
            const even = len - (len % 2); // digit ganjil terakhir tetap subset B
            if (mode !== 'C') { if (mode !== null) symbols += 1; mode = 'C'; }
            symbols += even / 2;
            i += even;
        } else {
            if (mode !== 'B') { if (mode !== null) symbols += 1; mode = 'B'; }
            symbols += 1;
            i += 1;
        }
    }
    symbols += 1; // checksum
    return symbols * 11 + 13;
};

// Hitung lebar bar terbesar yang masih muat di area label, lalu bulatkan ke
// kelipatan 0,05 px. Mengembalikan juga quiet zone kiri/kanan.
const barcodeProps = (value, areaMm, heightPx) => {
    const modules = estimateCode128Modules(value) + QUIET_ZONE_MODULES * 2;
    const raw = (areaMm * PX_PER_MM) / modules;
    const width = Math.min(MAX_MODULE_PX, Math.max(MIN_MODULE_PX, Math.floor(raw * 20) / 20));
    const quiet = width * QUIET_ZONE_MODULES;
    return {
        width,
        height: heightPx,
        margin: 0,
        marginLeft: quiet,
        marginRight: quiet,
    };
};

// widthMm/heightMm = ukuran fisik satu halaman cetak (untuk roll 3 kolom, satu
// halaman = satu strip berisi 3 label). Nilai ini dipakai sekaligus untuk @page
// dan untuk kotak di layar, supaya keduanya tidak pernah beda.
// barcodeAreaMm = lebar area yang boleh dipakai barcode di dalam satu label.
// barcodeHeight dalam px CSS (1 mm ≈ 3,78 px).
const PAPER_CONFIGS = {
    'thermal-33x15-3line': {
        name: '🏷️ Codeshop / Thermal 33 × 15 mm (3 Line / 3 Kolom Roll)',
        widthMm: 104,
        heightMm: 15,
        barcodeAreaMm: 31.5,
        barcodeHeight: 28,
        is3Line: true,
    },
    'thermal-33x15-1line': {
        name: '🏷️ Direct Thermal 33 × 15 mm (1 Line / Single Label)',
        widthMm: 33,
        heightMm: 15,
        barcodeAreaMm: 31.5,
        barcodeHeight: 28,
        isThermalSingle: true,
    },
    'thermal-40x30': {
        name: '🏷️ Direct Thermal 40 × 30 mm (Standard Barcode Label)',
        widthMm: 40,
        heightMm: 30,
        barcodeAreaMm: 38,
        barcodeHeight: 55,
        isThermalSingle: true,
    },
    'thermal-50x20': {
        name: '🏷️ Direct Thermal 50 × 20 mm (Retail Medium Label)',
        widthMm: 50,
        heightMm: 20,
        barcodeAreaMm: 48,
        barcodeHeight: 38,
        isThermalSingle: true,
    },
    'thermal-33x19': {
        name: '🏷️ Direct Thermal 33 × 19 mm (Small Sticker Minimarket)',
        widthMm: 33,
        heightMm: 19,
        barcodeAreaMm: 31.5,
        barcodeHeight: 40,
        isThermalSingle: true,
    },
    'thermal-100x50': {
        name: '📦 Direct Thermal 100 × 50 mm (Shipping / Box Label)',
        widthMm: 100,
        heightMm: 50,
        barcodeAreaMm: 96,
        barcodeHeight: 110,
        isThermalSingle: true,
    },
    'custom': {
        name: '⚙️ Ukuran Kustom (samakan dengan driver printer)',
        widthMm: 104,
        heightMm: 15,
        barcodeAreaMm: 31.5,
        barcodeHeight: 28,
        isThermalSingle: true,
        isCustom: true,
    },
    'a4-grid': {
        name: '📄 Kertas A4 Grid (5 Kolom - Printer Biasa / Inkjet)',
        pageSize: 'A4 portrait',
        barcodeAreaMm: 34,
        barcodeHeight: 45,
        isThermalSingle: false,
    }
};

// Satu halaman cetak. Bila `rotate` aktif, halaman dibalik menjadi potret dan isinya
// diputar 90°. Ini kompensasi untuk driver printer yang kertasnya terdaftar potret —
// Chrome akan memutar sendiri halaman lanskap agar "muat", dan hasilnya miring.
function PrintPage({ widthMm, heightMm, rotate, isLast, className = '', children }) {
    const pageW = rotate ? heightMm : widthMm;
    const pageH = rotate ? widthMm : heightMm;
    return (
        <div
            className={`relative overflow-hidden bg-white break-inside-avoid box-border print:m-0 ${isLast ? '' : 'page-break-after-always'} ${className}`}
            style={{ width: `${pageW}mm`, height: `${pageH}mm`, pageBreakInside: 'avoid', breakInside: 'avoid' }}
        >
            <div
                className="absolute left-0 top-0 box-border"
                style={{
                    width: `${widthMm}mm`,
                    height: `${heightMm}mm`,
                    transformOrigin: '0 0',
                    // rotate(90°) di origin 0,0 memindahkan isi ke x negatif, jadi
                    // digeser balik sejauh tinggi aslinya.
                    transform: rotate ? `translateX(${heightMm}mm) rotate(90deg)` : undefined,
                }}
            >
                {children}
            </div>
        </div>
    );
}

export default function QrPrint({ products, filters, layout = 'GudangLayout', routePrefix = 'master.' }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const [loading, setLoading] = useState(false);
    const [showPreview, setShowPreview] = useState(false);
    const [paperType, setPaperType] = useState('thermal-33x15-3line');
    // Putar isi 90°: dipakai kalau driver printer mendaftarkan kertasnya potret
    // sehingga Chrome memutar sendiri halaman lanskap kita.
    const [rotate, setRotate] = useState(false);
    const [customSize, setCustomSize] = useState({ width: 104, height: 15 });
    const [form, setForm] = useState({
        search: filters.search ?? '', 
        status: filters.status ?? '',
        per_page: filters.per_page ?? '50'
    });
    
    // State to hold selected products and their quantities: { [productId]: qty }
    const [selected, setSelected] = useState({});

    const hasFilter = form.search || form.status || (form.per_page !== '50');

    const reload = (e) => {
        e?.preventDefault();
        router.get(route(routePrefix + 'products.qr'),
            { search: form.search || undefined, status: form.status || undefined, per_page: form.per_page || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['products', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    const isAllSelected = products.data.length > 0 && products.data.every(p => !!selected[p.id]);

    const handleSelectAllToggle = () => {
        if (isAllSelected) {
            const next = { ...selected };
            products.data.forEach(p => delete next[p.id]);
            setSelected(next);
        } else {
            const next = { ...selected };
            products.data.forEach(p => {
                if (!next[p.id]) next[p.id] = 1;
            });
            setSelected(next);
        }
    };

    const handleSelectToggle = (id) => {
        setSelected(prev => {
            const next = { ...prev };
            if (next[id]) {
                delete next[id];
            } else {
                next[id] = 1;
            }
            return next;
        });
    };

    const handleQtyChange = (id, val) => {
        const qty = parseInt(val, 10);
        setSelected(prev => {
            if (!prev[id]) return prev;
            return {
                ...prev,
                [id]: isNaN(qty) || qty < 1 ? 1 : qty
            };
        });
    };

    const selectClass = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';

    const selectedCount = Object.keys(selected).length;
    const totalQty = Object.values(selected).reduce((acc, curr) => acc + curr, 0);

    const labelsToPrint = useMemo(() => {
        const labels = [];
        Object.entries(selected).forEach(([id, qty]) => {
            const product = products.data.find(p => p.id === parseInt(id, 10));
            if (product) {
                for (let i = 0; i < qty; i++) {
                    labels.push(product);
                }
            }
        });
        return labels;
    }, [selected, products.data]);

    // Grouping for 3-Line roll paper
    const labelRows3Line = useMemo(() => {
        const rows = [];
        for (let i = 0; i < labelsToPrint.length; i += 3) {
            rows.push(labelsToPrint.slice(i, i + 3));
        }
        return rows;
    }, [labelsToPrint]);

    const baseConfig = PAPER_CONFIGS[paperType] || PAPER_CONFIGS['thermal-33x15-3line'];

    // Ukuran kustom menimpa preset, dan area barcode ikut menyesuaikan lebar label.
    const activeConfig = useMemo(() => {
        if (!baseConfig.isCustom) return baseConfig;
        const widthMm = Math.max(10, Number(customSize.width) || 10);
        const heightMm = Math.max(6, Number(customSize.height) || 6);
        return {
            ...baseConfig,
            widthMm,
            heightMm,
            barcodeAreaMm: Math.max(8, widthMm - 1.5),
            // Sisakan ruang untuk baris nama di atas dan baris kode/harga di bawah.
            barcodeHeight: Math.max(14, Math.round((heightMm - 5.6) * PX_PER_MM)),
        };
    }, [baseConfig, customSize.width, customSize.height]);

    const canRotate = !!(activeConfig.is3Line || activeConfig.isThermalSingle);
    const effectiveRotate = canRotate && rotate;

    // Ukuran halaman untuk @page. A4 tetap memakai keyword-nya sendiri.
    const pageSizeCss = activeConfig.pageSize
        ?? (effectiveRotate
            ? `${activeConfig.heightMm}mm ${activeConfig.widthMm}mm`
            : `${activeConfig.widthMm}mm ${activeConfig.heightMm}mm`);

    // Kode yang, pada lebar bar minimum yang masih bisa dipindai, tetap lebih lebar
    // dari area label. Barcode-nya akan terpotong, jadi user perlu diberi tahu.
    const oversizedLabels = useMemo(() => {
        const found = new Map();
        labelsToPrint.forEach((l) => {
            const value = l.barcode || l.code || '';
            if (!value || found.has(value)) return;
            const modules = estimateCode128Modules(value) + QUIET_ZONE_MODULES * 2;
            const neededMm = (modules * MIN_MODULE_PX) / PX_PER_MM;
            if (neededMm > activeConfig.barcodeAreaMm) {
                found.set(value, { value, name: l.name, neededMm });
            }
        });
        return [...found.values()];
    }, [labelsToPrint, activeConfig.barcodeAreaMm]);

    // Inject dynamic @page size into document.head so Chrome print engine reads it globally
    useEffect(() => {
        let styleEl = document.getElementById('dynamic-print-paper-style');
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'dynamic-print-paper-style';
            document.head.appendChild(styleEl);
        }
        styleEl.innerHTML = `
            @media print {
                @page {
                    size: ${pageSizeCss} !important;
                    margin: 0 !important;
                }
                body, html {
                    margin: 0 !important;
                    padding: 0 !important;
                    width: auto !important;
                    height: auto !important;
                    background: white !important;
                }
                .no-print {
                    display: none !important;
                }
                .print-controls {
                    display: none !important;
                }
                /* Overlay dikembalikan ke aliran dokumen biasa. display:block dan
                   overflow:visible WAJIB — selama masih flex + overflow, Chrome
                   memperlakukannya sebagai kotak scroll dan memotong isinya. */
                #print-modal-overlay {
                    position: static !important;
                    display: block !important;
                    inset: auto !important;
                    overflow: visible !important;
                    background: white !important;
                    backdrop-filter: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    width: auto !important;
                    height: auto !important;
                }
                /* Pembungkus scroll preview. Padding p-4/md:p-8 di sini (≈8,5 mm per
                   sisi) membuat total lebar isi melebihi lebar kertas, sehingga tiap
                   baris label dipecah Chrome menjadi 2 halaman — itu sebabnya banyak
                   stiker tercetak kosong / bergeser. */
                #print-scroll-wrap {
                    display: block !important;
                    flex: none !important;
                    overflow: visible !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    width: auto !important;
                    height: auto !important;
                }
                #print-area {
                    position: static !important;
                    display: block !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    box-shadow: none !important;
                    border: 0 !important;
                    width: auto !important;
                    max-width: none !important;
                    background: white !important;
                }
                /* Daftar strip label dibuat block agar tiap strip mulai persis di
                   x=0 halaman; centering flex bisa menggeser strip setengah milimeter. */
                .print-strip-list {
                    display: block !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    gap: 0 !important;
                }
                .page-break-after-always {
                    page-break-after: always !important;
                    break-after: page !important;
                }
                .break-inside-avoid {
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }
                /* Bar barcode harus jatuh tepat di batas dot printer, bukan di-antialias
                   jadi abu-abu — antialias inilah yang bikin bar terlihat tipis/pudar. */
                #print-area svg {
                    shape-rendering: crispEdges !important;
                    image-rendering: pixelated !important;
                }
                #print-area, #print-area * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
            }
        `;
    }, [pageSizeCss]);

    return (
        <Layout title="Cetak Label Barcode" pageTitle="Master Data — Cetak Label">
            <Head title="Cetak Label Barcode" />

            <div className="space-y-6 no-print">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Cetak Label Barcode</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Pilih produk dan tentukan jumlah label yang ingin dicetak</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button 
                            onClick={() => setShowPreview(true)} 
                            disabled={selectedCount === 0} 
                            startIcon={<Icon name="visibility" className="text-[18px]" />}
                        >
                            Preview {totalQty} Label ({selectedCount} Produk)
                        </Button>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[280px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama, kode, atau barcode..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800" />
                            </div>
                            <select value={form.per_page} onChange={(e) => setForm({ ...form, per_page: e.target.value })} className={selectClass}>
                                <option value="20">20 / Halaman</option>
                                <option value="50">50 / Halaman</option>
                                <option value="100">100 / Halaman</option>
                                <option value="200">200 / Halaman</option>
                                <option value="500">500 / Halaman</option>
                                <option value="1000">1000 / Halaman</option>
                                <option value="all">Semua Produk (Tanpa Batas)</option>
                            </select>
                            <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className={selectClass}>
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                            <Button type="submit" size="sm">Terapkan</Button>
                            {hasFilter && <Link href={route(routePrefix + 'products.qr')} className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4.5 w-12 text-center">
                                        <input 
                                            type="checkbox" 
                                            checked={isAllSelected}
                                            onChange={handleSelectAllToggle}
                                            className="h-5 w-5 rounded border-gray-300 text-brand-500 focus:ring-brand-500 cursor-pointer dark:border-gray-700 dark:bg-gray-900" 
                                        />
                                    </th>
                                    <th className="px-6 py-4.5 w-32 text-center">Jumlah Cetak</th>
                                    <th className="px-6 py-4.5">Produk</th>
                                    <th className="px-6 py-4.5">Kode/Barcode</th>
                                    <th className="px-6 py-4.5 text-right">Harga Jual</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={8} columns={5} />
                                    : products.data.length === 0 ? <EmptyState colSpan={5} icon="barcode_scanner" message="Tidak ada data produk." />
                                    : products.data.map((p) => {
                                        const isSelected = !!selected[p.id];
                                        return (
                                        <tr key={p.id} className={`group transition-colors ${isSelected ? 'bg-brand-50/50 dark:bg-brand-900/10' : 'hover:bg-gray-50/50 dark:hover:bg-white/[0.01]'}`}>
                                            <td className="px-6 py-4.5 text-center">
                                                <input 
                                                    type="checkbox" 
                                                    checked={isSelected}
                                                    onChange={() => handleSelectToggle(p.id)}
                                                    className="h-5 w-5 rounded border-gray-300 text-brand-500 focus:ring-brand-500 cursor-pointer dark:border-gray-700 dark:bg-gray-900" 
                                                />
                                            </td>
                                            <td className="px-6 py-4.5 text-center">
                                                <input 
                                                    type="number" 
                                                    min="1"
                                                    max="1000"
                                                    disabled={!isSelected}
                                                    value={isSelected ? selected[p.id] : ''}
                                                    onChange={(e) => handleQtyChange(p.id, e.target.value)}
                                                    className={`w-20 text-center rounded-lg border-gray-300 py-2 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white ${!isSelected && 'bg-gray-100 opacity-50 dark:bg-gray-800'}`}
                                                />
                                            </td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center gap-3">
                                                    {p.image_url && (
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 text-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                                                            <img src={p.image_url} alt="" className="h-full w-full object-cover" />
                                                        </div>
                                                    )}
                                                    <span className="text-sm font-bold text-gray-800 dark:text-white/90">{p.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex flex-col">
                                                    {p.barcode || p.code ? (
                                                        <Barcode 
                                                            value={p.barcode || p.code} 
                                                            width={1} 
                                                            height={24} 
                                                            fontSize={10}
                                                            margin={0}
                                                            displayValue={true}
                                                            background="transparent"
                                                        />
                                                    ) : (
                                                        <span className="text-gray-400">-</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5 text-right text-xs font-bold tabular-nums text-gray-800 dark:text-white/90">
                                                {formatRupiah(p.selling_price)}
                                            </td>
                                        </tr>
                                    )})}
                            </tbody>
                        </table>
                    </div>
                    {products.meta?.links && <div className="border-t border-gray-150 p-5 dark:border-gray-800"><Pagination links={products.meta.links} /></div>}
                </div>
            </div>

            {/* Print Preview Modal / Overlay */}
            {showPreview && (
                <div id="print-modal-overlay" className="fixed inset-0 z-[100] flex flex-col bg-gray-100/95 backdrop-blur-sm dark:bg-gray-950/95">
                    {/* Top Control Bar (Hidden in Print) */}
                    <div className="print-controls flex flex-wrap h-auto min-h-16 shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div className="flex items-center gap-4">
                            <Button variant="secondary" onClick={() => setShowPreview(false)} startIcon={<Icon name="arrow_back" />}>
                                Kembali
                            </Button>
                            <h3 className="text-lg font-bold text-gray-800 dark:text-white/90">Preview ({totalQty} Label)</h3>
                        </div>

                        {/* Paper & Label Type Selector */}
                        <div className="flex flex-wrap items-center gap-3">
                            <label className="text-xs font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                <Icon name="settings_overscan" className="text-[18px]" />
                                Ukuran Kertas & Stiker Label:
                            </label>
                            <select
                                value={paperType}
                                onChange={(e) => setPaperType(e.target.value)}
                                className="h-10 rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-800 shadow-xs outline-hidden transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                {Object.entries(PAPER_CONFIGS).map(([key, cfg]) => (
                                    <option key={key} value={key}>{cfg.name}</option>
                                ))}
                            </select>

                            {activeConfig.isCustom && (
                                <div className="flex items-center gap-1.5 rounded-lg border border-gray-300 px-2 py-1 dark:border-gray-700">
                                    <input
                                        type="number" min="10" max="300" step="0.5"
                                        value={customSize.width}
                                        onChange={(e) => setCustomSize((s) => ({ ...s, width: e.target.value }))}
                                        className="w-16 rounded-md border-gray-300 py-1 text-center text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    />
                                    <span className="text-xs text-gray-500">×</span>
                                    <input
                                        type="number" min="6" max="300" step="0.5"
                                        value={customSize.height}
                                        onChange={(e) => setCustomSize((s) => ({ ...s, height: e.target.value }))}
                                        className="w-16 rounded-md border-gray-300 py-1 text-center text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    />
                                    <span className="text-xs font-semibold text-gray-500">mm</span>
                                </div>
                            )}

                            {canRotate && (
                                <button
                                    type="button"
                                    onClick={() => setRotate((r) => !r)}
                                    title="Pakai kalau hasil cetak keluar miring/berdiri"
                                    className={`flex h-10 items-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition ${
                                        rotate
                                            ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300'
                                            : 'border-gray-300 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200'
                                    }`}
                                >
                                    <Icon name="rotate_90_degrees_cw" className="text-[16px]" />
                                    Putar 90° {rotate ? 'ON' : 'OFF'}
                                </button>
                            )}

                            <Button onClick={() => window.print()} startIcon={<Icon name="print" />}>
                                Cetak Sekarang
                            </Button>
                        </div>
                    </div>

                    {/* Helper Print Tip Banner (Hidden in Print) */}
                    <div className="print-controls bg-amber-50 px-6 py-2 border-b border-amber-200 text-xs font-medium text-amber-800">
                        <div className="flex items-start gap-2">
                            <Icon name="info" className="text-[16px] text-amber-600 shrink-0" />
                            <span>
                                <strong>Ukuran kertas harus sama di 3 tempat.</strong> Set <strong>Printer Preferences → Paper Size</strong> ke{' '}
                                <strong className="font-mono">{pageSizeCss.replace('mm ', ' mm × ')}</strong>, lalu di jendela print browser pilih ukuran yang sama,
                                dengan <strong>Margins: None</strong> dan <strong>Scale: 100%</strong> (jangan "Fit to page").
                                Kalau ukuran driver beda, Chrome akan memutar &amp; menyusutkan halaman sendiri — itu penyebab hasil cetak berdiri dan tidak pas.
                                {canRotate && <> Kalau driver printer Anda hanya mau kertas berdiri, nyalakan <strong>Putar 90°</strong>.</>}
                            </span>
                        </div>
                    </div>

                    {oversizedLabels.length > 0 && (
                        <div className="print-controls bg-red-50 px-6 py-2 border-b border-red-200 text-xs font-medium text-red-800">
                            <div className="flex items-start gap-2">
                                <Icon name="warning" className="text-[16px] text-red-600 shrink-0" />
                                <span>
                                    <strong>{oversizedLabels.length} kode terlalu panjang</strong> untuk label ini —
                                    barcode akan terpotong dan tidak bisa dipindai. Pakai ukuran label yang lebih lebar,
                                    atau isi kolom <strong>barcode</strong> produk dengan angka saja (lebih padat daripada kode huruf).
                                    <span className="ml-1 font-mono">
                                        {oversizedLabels.slice(0, 5).map((o) => o.value).join(', ')}
                                        {oversizedLabels.length > 5 ? `, +${oversizedLabels.length - 5} lagi` : ''}
                                    </span>
                                </span>
                            </div>
                        </div>
                    )}

                    {/* Preview Area (Visible in Print) */}
                    <div id="print-scroll-wrap" className="flex-1 overflow-auto p-4 md:p-8">
                        <div id="print-area" className={`mx-auto bg-white p-4 ${activeConfig.is3Line ? 'w-auto max-w-[106mm]' : activeConfig.isThermalSingle ? 'w-auto max-w-xl' : 'max-w-5xl p-8 shadow-md'}`}>
                            {activeConfig.is3Line ? (
                                /* 3-Line Roll (e.g. 33x15mm 3 Kolom per Baris Roll) */
                                <div className="print-strip-list flex flex-col items-center print:gap-0 gap-4">
                                    {labelRows3Line.map((row, rIdx) => (
                                        <PrintPage
                                            key={rIdx}
                                            widthMm={activeConfig.widthMm}
                                            heightMm={activeConfig.heightMm}
                                            rotate={effectiveRotate}
                                            isLast={rIdx === labelRows3Line.length - 1}
                                        >
                                        <div
                                            className="grid grid-cols-3 gap-x-[2mm] h-full w-full overflow-hidden bg-white items-center box-border"
                                        >
                                            {[0, 1, 2].map((cIdx) => {
                                                const label = row[cIdx];
                                                if (!label) {
                                                    return <div key={cIdx} className="h-full" />;
                                                }
                                                const value = label.barcode || label.code || '';
                                                return (
                                                    <div
                                                        key={cIdx}
                                                        className="h-full px-[1px] flex flex-col items-center justify-between text-center box-border overflow-hidden bg-white break-inside-avoid"
                                                        style={{ pageBreakInside: 'avoid', breakInside: 'avoid' }}
                                                    >
                                                        <div className="text-[6.5px] font-bold leading-none truncate w-full text-black h-[2.8mm] flex items-center justify-center">
                                                            {label.name}
                                                        </div>
                                                        <div className="flex items-center justify-center overflow-hidden h-[8.4mm] max-h-[8.4mm] w-full">
                                                            {value && (
                                                                <Barcode
                                                                    value={value}
                                                                    {...barcodeProps(value, activeConfig.barcodeAreaMm, activeConfig.barcodeHeight)}
                                                                    displayValue={false}
                                                                    background="#ffffff"
                                                                    lineColor="#000000"
                                                                />
                                                            )}
                                                        </div>
                                                        <div className="flex w-full items-center justify-between px-0.5 h-[2.8mm] leading-none">
                                                            <span className="font-mono text-[5.5px] tracking-tighter text-black leading-none">{value}</span>
                                                            <span className="text-[6px] font-bold text-black leading-none">{formatRupiah(label.selling_price)}</span>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        </PrintPage>
                                    ))}
                                </div>
                            ) : activeConfig.isThermalSingle ? (
                                /* Single Thermal Label Continuous Stream */
                                <div className="print-strip-list flex flex-col items-center print:gap-0 gap-2">
                                    {labelsToPrint.map((label, idx) => {
                                        const value = label.barcode || label.code || '';
                                        return (
                                        <PrintPage
                                            key={idx}
                                            widthMm={activeConfig.widthMm}
                                            heightMm={activeConfig.heightMm}
                                            rotate={effectiveRotate}
                                            isLast={idx === labelsToPrint.length - 1}
                                        >
                                        <div className="h-full w-full px-1 flex flex-col items-center justify-between text-center box-border overflow-hidden bg-white">
                                            <div className="text-[8px] font-bold leading-none truncate w-full text-black">
                                                {label.name}
                                            </div>
                                            <div className="flex items-center justify-center overflow-hidden w-full my-0.5">
                                                {value && (
                                                    <Barcode
                                                        value={value}
                                                        {...barcodeProps(value, activeConfig.barcodeAreaMm, activeConfig.barcodeHeight)}
                                                        displayValue={false}
                                                        background="#ffffff"
                                                        lineColor="#000000"
                                                    />
                                                )}
                                            </div>
                                            <div className="flex w-full items-center justify-between px-1 leading-none">
                                                <span className="font-mono text-[7px] tracking-tight text-black leading-none">{value}</span>
                                                <span className="text-[7.5px] font-bold text-black leading-none">{formatRupiah(label.selling_price)}</span>
                                            </div>
                                        </div>
                                        </PrintPage>
                                        );
                                    })}
                                </div>
                            ) : (
                                /* Multi-column Grid Layout (e.g. A4 Sticker Paper) */
                                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                    {labelsToPrint.map((label, idx) => {
                                        const value = label.barcode || label.code || '';
                                        return (
                                        <div key={idx} className="flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 p-2 text-center print:border-solid print:border-gray-200 bg-white break-inside-avoid">
                                            <div className="mb-1 w-full truncate text-[11px] font-bold leading-tight text-gray-800 print:text-black">
                                                {label.name}
                                            </div>
                                            {value && (
                                                <Barcode
                                                    value={value}
                                                    {...barcodeProps(value, activeConfig.barcodeAreaMm, activeConfig.barcodeHeight)}
                                                    displayValue={false}
                                                    background="#ffffff"
                                                    lineColor="#000000"
                                                />
                                            )}
                                            <div className="mt-1 flex w-full items-center justify-between px-1">
                                                <span className="font-mono text-[10px] tracking-tight text-gray-800 print:text-black">{value}</span>
                                                <span className="text-xs font-bold text-gray-800 print:text-black">{formatRupiah(label.selling_price)}</span>
                                            </div>
                                        </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </Layout>
    );
}
