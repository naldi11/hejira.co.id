import { Head, Link, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
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

const PAPER_CONFIGS = {
    'thermal-33x15-3line': {
        name: '🏷️ Codeshop / Thermal 33 × 15 mm (3 Line / 3 Kolom Roll)',
        pageStyle: '@page { size: 104mm 15mm; margin: 0; }',
        gridClass: 'grid grid-cols-3 gap-x-[2mm] w-[104mm] h-[15mm] overflow-hidden page-break-after-always bg-white items-center',
        labelClass: 'w-[33mm] h-[15mm] px-[1px] py-[0.5px] flex flex-col items-center justify-between text-center box-border overflow-hidden bg-white',
        barcodeWidth: 0.75,
        barcodeHeight: 13,
        fontSize: 6,
        nameClass: 'text-[6.5px] font-bold leading-none truncate w-full text-black',
        priceClass: 'text-[6.5px] font-bold text-black',
        codeClass: 'font-mono text-[5.5px] tracking-tighter text-black',
        is3Line: true,
    },
    'thermal-33x15-1line': {
        name: '🏷️ Direct Thermal 33 × 15 mm (1 Line / Single Label)',
        pageStyle: '@page { size: 33mm 15mm; margin: 0; }',
        labelClass: 'w-[33mm] h-[15mm] px-[1px] py-[0.5px] flex flex-col items-center justify-between text-center box-border page-break-after-always overflow-hidden bg-white',
        barcodeWidth: 0.75,
        barcodeHeight: 13,
        fontSize: 6,
        nameClass: 'text-[6.5px] font-bold leading-none truncate w-full text-black',
        priceClass: 'text-[6.5px] font-bold text-black',
        codeClass: 'font-mono text-[5.5px] tracking-tighter text-black',
        isThermalSingle: true,
    },
    'thermal-40x30': {
        name: '🏷️ Direct Thermal 40 × 30 mm (Standard Barcode Label)',
        pageStyle: '@page { size: 40mm 30mm; margin: 0; }',
        labelClass: 'w-[40mm] h-[30mm] p-1 flex flex-col items-center justify-between text-center box-border page-break-after-always overflow-hidden bg-white',
        barcodeWidth: 1.0,
        barcodeHeight: 22,
        fontSize: 9,
        nameClass: 'text-[9px] font-bold leading-tight truncate w-full text-black',
        priceClass: 'text-[9px] font-bold text-black',
        codeClass: 'font-mono text-[8px] tracking-tight text-black',
        isThermalSingle: true,
    },
    'thermal-50x20': {
        name: '🏷️ Direct Thermal 50 × 20 mm (Retail Medium Label)',
        pageStyle: '@page { size: 50mm 20mm; margin: 0; }',
        labelClass: 'w-[50mm] h-[20mm] px-1 py-[2px] flex flex-col items-center justify-between text-center box-border page-break-after-always overflow-hidden bg-white',
        barcodeWidth: 1.1,
        barcodeHeight: 18,
        fontSize: 8,
        nameClass: 'text-[8px] font-bold leading-none truncate w-full text-black',
        priceClass: 'text-[8px] font-bold text-black',
        codeClass: 'font-mono text-[7px] tracking-tight text-black',
        isThermalSingle: true,
    },
    'thermal-33x19': {
        name: '🏷️ Direct Thermal 33 × 19 mm (Small Sticker Minimarket)',
        pageStyle: '@page { size: 33mm 19mm; margin: 0; }',
        labelClass: 'w-[33mm] h-[19mm] px-[2px] py-[1px] flex flex-col items-center justify-between text-center box-border page-break-after-always overflow-hidden bg-white',
        barcodeWidth: 0.85,
        barcodeHeight: 16,
        fontSize: 7,
        nameClass: 'text-[7px] font-bold leading-none truncate w-full text-black',
        priceClass: 'text-[7px] font-bold text-black',
        codeClass: 'font-mono text-[6px] tracking-tight text-black',
        isThermalSingle: true,
    },
    'thermal-100x50': {
        name: '📦 Direct Thermal 100 × 50 mm (Shipping / Box Label)',
        pageStyle: '@page { size: 100mm 50mm; margin: 0; }',
        labelClass: 'w-[100mm] h-[50mm] p-3 flex flex-col items-center justify-between text-center box-border page-break-after-always overflow-hidden bg-white',
        barcodeWidth: 1.8,
        barcodeHeight: 48,
        fontSize: 12,
        nameClass: 'text-sm font-bold leading-tight truncate w-full text-black',
        priceClass: 'text-xs font-bold text-black',
        codeClass: 'font-mono text-xs tracking-tight text-black',
        isThermalSingle: true,
    },
    'a4-grid': {
        name: '📄 Kertas A4 Grid (5 Kolom - Printer Biasa / Inkjet)',
        pageStyle: '@page { size: A4 portrait; margin: 5mm; }',
        labelClass: 'flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 p-2.5 text-center print:border-solid print:border-gray-200 bg-white',
        barcodeWidth: 1.1,
        barcodeHeight: 36,
        fontSize: 10,
        nameClass: 'mb-1 w-full truncate text-[11px] font-bold leading-tight text-gray-800 print:text-black',
        priceClass: 'text-xs font-bold text-gray-800 print:text-black',
        codeClass: 'font-mono text-[10px] tracking-tight text-gray-800 print:text-black',
        isThermalSingle: false,
    }
};

export default function QrPrint({ products, filters, layout = 'GudangLayout', routePrefix = 'master.' }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const [loading, setLoading] = useState(false);
    const [showPreview, setShowPreview] = useState(false);
    const [paperType, setPaperType] = useState('thermal-33x15-3line');
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

    const activeConfig = PAPER_CONFIGS[paperType] || PAPER_CONFIGS['thermal-33x15-3line'];

    return (
        <Layout title="Cetak Label Barcode" pageTitle="Master Data — Cetak Label">
            <Head title="Cetak Label Barcode">
                <style>{`
                    @media print {
                        body * {
                            visibility: hidden;
                        }
                        #print-area, #print-area * {
                            visibility: visible;
                        }
                        #print-area {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 100%;
                            padding: 0;
                            margin: 0;
                            background: white;
                        }
                        .print-controls {
                            display: none !important;
                        }
                        .page-break-after-always {
                            page-break-after: always;
                            break-after: page;
                        }
                        ${activeConfig.pageStyle}
                    }
                `}</style>
            </Head>

            <div className="space-y-6">
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
                                                    max="100"
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
                <div className="fixed inset-0 z-[100] flex flex-col bg-gray-100/95 backdrop-blur-sm dark:bg-gray-950/95">
                    {/* Top Control Bar (Hidden in Print) */}
                    <div className="print-controls flex flex-wrap h-auto min-h-16 shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div className="flex items-center gap-4">
                            <Button variant="secondary" onClick={() => setShowPreview(false)} startIcon={<Icon name="arrow_back" />}>
                                Kembali
                            </Button>
                            <h3 className="text-lg font-bold text-gray-800 dark:text-white/90">Preview ({totalQty} Label)</h3>
                        </div>

                        {/* Paper & Label Type Selector */}
                        <div className="flex items-center gap-3">
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
                            <Button onClick={() => window.print()} startIcon={<Icon name="print" />}>
                                Cetak Sekarang
                            </Button>
                        </div>
                    </div>

                    {/* Preview Area (Visible in Print) */}
                    <div className="flex-1 overflow-auto p-4 md:p-8">
                        <div id="print-area" className={`mx-auto bg-white p-4 ${activeConfig.is3Line ? 'w-auto max-w-[106mm]' : activeConfig.isThermalSingle ? 'w-auto max-w-xl' : 'max-w-5xl p-8 shadow-md'}`}>
                            {activeConfig.is3Line ? (
                                /* 3-Line Roll (e.g. 33x15mm 3 Kolom per Baris Roll) */
                                <div className="flex flex-col gap-[2mm] items-center">
                                    {labelRows3Line.map((row, rIdx) => (
                                        <div key={rIdx} className={activeConfig.gridClass}>
                                            {[0, 1, 2].map((cIdx) => {
                                                const label = row[cIdx];
                                                if (!label) {
                                                    return <div key={cIdx} className="w-[33mm] h-[15mm]" />; // Empty spacer
                                                }
                                                return (
                                                    <div key={cIdx} className={activeConfig.labelClass}>
                                                        <div className={activeConfig.nameClass}>
                                                            {label.name}
                                                        </div>
                                                        <Barcode 
                                                            value={label.barcode || label.code} 
                                                            width={activeConfig.barcodeWidth} 
                                                            height={activeConfig.barcodeHeight} 
                                                            fontSize={activeConfig.fontSize}
                                                            margin={0}
                                                            displayValue={false}
                                                            background="transparent"
                                                        />
                                                        <div className="flex w-full items-center justify-between px-0.5">
                                                            <span className={activeConfig.codeClass}>{label.barcode || label.code}</span>
                                                            <span className={activeConfig.priceClass}>{formatRupiah(label.selling_price)}</span>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    ))}
                                </div>
                            ) : activeConfig.isThermalSingle ? (
                                /* Single Thermal Label Continuous Stream */
                                <div className="flex flex-col items-center gap-2">
                                    {labelsToPrint.map((label, idx) => (
                                        <div key={idx} className={activeConfig.labelClass}>
                                            <div className={activeConfig.nameClass}>
                                                {label.name}
                                            </div>
                                            <Barcode 
                                                value={label.barcode || label.code} 
                                                width={activeConfig.barcodeWidth} 
                                                height={activeConfig.barcodeHeight} 
                                                fontSize={activeConfig.fontSize}
                                                margin={0}
                                                displayValue={false}
                                                background="transparent"
                                            />
                                            <div className="mt-0.5 flex w-full items-center justify-between px-1">
                                                <span className={activeConfig.codeClass}>{label.barcode || label.code}</span>
                                                <span className={activeConfig.priceClass}>{formatRupiah(label.selling_price)}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                /* Multi-column Grid Layout (e.g. A4 Sticker Paper) */
                                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                    {labelsToPrint.map((label, idx) => (
                                        <div key={idx} className={activeConfig.labelClass}>
                                            <div className={activeConfig.nameClass}>
                                                {label.name}
                                            </div>
                                            <Barcode 
                                                value={label.barcode || label.code} 
                                                width={activeConfig.barcodeWidth} 
                                                height={activeConfig.barcodeHeight} 
                                                fontSize={activeConfig.fontSize}
                                                margin={0}
                                                displayValue={false}
                                                background="transparent"
                                            />
                                            <div className="mt-1 flex w-full items-center justify-between px-1">
                                                <span className={activeConfig.codeClass}>{label.barcode || label.code}</span>
                                                <span className={activeConfig.priceClass}>{formatRupiah(label.selling_price)}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </Layout>
    );
}
