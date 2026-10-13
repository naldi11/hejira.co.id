export default function CataloguePages({ catalogue }) {
    const { pagination, loading, error, setPage } = catalogue;
    return <div className="flex flex-wrap items-center justify-between gap-3 p-3 text-xs text-gray-500 dark:text-gray-400" aria-live="polite">
        <span>{loading ? 'Mencari produk…' : error || `Halaman ${pagination?.page || 1} · Pencarian mencakup seluruh produk`}</span>
        <div className="flex gap-2">
            <button type="button" disabled={loading || (pagination?.page || 1) <= 1} onClick={() => setPage(pagination.page - 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">Sebelumnya</button>
            <button type="button" disabled={loading || !pagination?.has_more} onClick={() => setPage(pagination.page + 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">Berikutnya</button>
        </div>
    </div>;
}
