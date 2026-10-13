export default function ReturnNotice({ errors = {}, received = false }) {
    return <div className="my-4 space-y-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-900/20 dark:text-amber-200">
        <p>{received ? 'Barang rusak / kedaluwarsa diterima ke Stok Rusak / Retur yang terpisah. Barang ini tidak dapat dijual atau dikirim kembali.' : 'Pilih kondisi sebenarnya dan jelaskan alasan retur. Stok asal berkurang saat dikirim. Barang rusak / kedaluwarsa masuk stok terpisah setelah diterima tujuan, tanpa menambah stok siap jual.'}</p>
        {Object.keys(errors).length > 0 && <div role="alert" className="font-semibold text-red-600">{[...new Set(Object.values(errors))].map((e, i) => <p key={i}>{e}</p>)}</div>}
    </div>;
}
