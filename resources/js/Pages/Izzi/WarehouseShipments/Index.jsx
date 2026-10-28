import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import IzziLayout from '@/Layouts/IzziLayout';
import Pagination from '@/Components/Pagination';
import { Navigation } from '../Common';
import { formatDate } from '@/lib/format';

const route = window.route;
const input = 'rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700';

export default function Index({ transfers, branch, branches, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    return <IzziLayout pageTitle="Pengiriman Gudang ke Izzi">
        <Head title="Terima Pengiriman Gudang" />
        <div className="space-y-6 text-gray-800 dark:text-gray-200">
            <div>
                <h1 className="text-2xl font-bold">Terima Pengiriman Gudang — {branch.name}</h1>
                <p className="mt-2 text-sm text-gray-500">Periksa surat jalan dan jumlah fisik sebelum mengonfirmasi. Stok Izzi bertambah setelah barang baik diterima.</p>
            </div>
            <Navigation branch={branch} branches={branches} />
            <form className="flex flex-wrap gap-3" onSubmit={(e) => {
                e.preventDefault();
                router.get(route('izzi.warehouse-shipments.index'), { branch_id: branch.id, search, status });
            }}>
                <input aria-label="Cari surat jalan" className={`${input} min-w-60 flex-1`} value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor surat jalan…" />
                <select aria-label="Status pengiriman" className={input} value={status} onChange={(e) => setStatus(e.target.value)}>
                    <option value="">Semua status</option><option value="sent">Menunggu penerimaan</option><option value="received">Sudah dikonfirmasi</option>
                </select>
                <button className="rounded-lg bg-brand-500 px-5 py-2 font-semibold text-white">Cari</button>
            </form>
            <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <table className="w-full text-left text-sm">
                    <thead className="bg-gray-50 text-gray-500 dark:bg-gray-800"><tr>
                        {['Surat jalan', 'Tanggal kirim', 'Pengirim', 'Status', 'Tindakan'].map((label) => <th key={label} className="px-5 py-4">{label}</th>)}
                    </tr></thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                        {transfers.data.map((document) => <tr key={document.id}>
                            <td className="px-5 py-4 font-mono font-semibold">{document.transfer_number}</td>
                            <td className="px-5 py-4">{formatDate(document.date)}</td>
                            <td className="px-5 py-4">{document.creator}</td>
                            <td className="px-5 py-4">{document.status === 'received' ? 'Sudah dikonfirmasi' : 'Menunggu penerimaan'}</td>
                            <td className="px-5 py-4"><Link className="font-semibold text-brand-500" href={route('izzi.warehouse-shipments.show', document.id)}>Periksa pengiriman →</Link></td>
                        </tr>)}
                        {!transfers.data.length && <tr><td colSpan={5} className="p-8 text-center text-gray-500">Belum ada pengiriman Gudang yang sesuai filter.</td></tr>}
                    </tbody>
                </table>
            </div>
            <Pagination links={transfers.meta?.links || []} />
        </div>
    </IzziLayout>;
}
