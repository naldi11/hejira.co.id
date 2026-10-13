import { formatQty } from '@/lib/format';

export default function ProductionLotSelect({ lots = [], item, onChange, error }) {
    const choices = lots.filter(lot => String(lot.product_id) === String(item.product_id) && String(lot.unit_id) === String(item.unit_id));
    const chosen = choices.find(lot => String(lot.id) === String(item.production_lot_id));
    let needed = Number(item.quantity) || 0;
    const allocation = choices.flatMap(lot => {
        const take = Math.min(needed, lot.remaining);
        needed -= take;
        return take > 0 ? [`${lot.production_number || 'Stok tanpa nomor produksi'}: ${formatQty(take)}`] : [];
    });
    return <div className="mt-3 min-w-56 text-xs">
        <label className="block font-semibold">Sumber stok / nomor produksi
            <select value={item.production_lot_id || ''} onChange={event => onChange(event.target.value)} className="mt-1 w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                <option value="">Otomatis — tanpa nomor, lalu produksi tertua</option>
                {choices.map(lot => <option key={lot.id} value={lot.id}>{lot.production_number || 'Stok tanpa nomor produksi'}{lot.production_date && ` · ${lot.production_date}`} · sisa {formatQty(lot.remaining)}</option>)}
            </select>
        </label>
        <p className="mt-1 text-gray-500 dark:text-gray-400">{chosen ? `Ambil barang dari ${chosen.production_number || 'stok tanpa nomor produksi'}; tersedia ${formatQty(chosen.remaining)}.` : 'Stok tanpa nomor didahulukan, lalu produksi tertua. Ikuti sumber stok di rincian pengiriman saat mengambil barang.'}</p>
        {!chosen && choices.length > 0 && <p className="mt-1 text-gray-500">Perkiraan sumber: {allocation.join('; ')}{needed > 0 && '; saldo lainnya diperiksa saat disimpan'}.</p>}
        {choices.length === 0 && <p className="mt-1 text-amber-700 dark:text-amber-400">Stok lama belum memiliki nomor produksi; tidak akan dikaitkan secara otomatis ke produksi lama.</p>}
        {chosen && Number(item.quantity) > chosen.remaining && <p className="mt-1 text-red-600">Jumlah melebihi sisa sumber ini. Kurangi jumlah atau gunakan pilihan otomatis untuk beberapa produksi.</p>}
        {error && <p className="mt-1 text-red-600">{error}</p>}
    </div>;
}
