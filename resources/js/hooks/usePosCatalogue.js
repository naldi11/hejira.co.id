import { useEffect, useRef, useState } from 'react';

export default function usePosCatalogue(url, initialProducts, initialPage) {
    const [query, setQueryValue] = useState('');
    const [page, setPage] = useState(1);
    const [products, setProducts] = useState(initialProducts);
    const [pagination, setPagination] = useState(initialPage || { page: 1, has_more: false });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const first = useRef(true);
    useEffect(() => {
        if (first.current) { first.current = false; return; }
        const abort = new AbortController();
        setLoading(true); setError('');
        const timer = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ lookup: 'products', q: query, catalog_page: String(page) });
                const response = await fetch(`${url}?${params}`, { signal: abort.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Daftar produk belum berhasil dimuat. Coba cari kembali.');
                const data = await response.json();
                if (!abort.signal.aborted) { setProducts(data.products); setPagination(data.catalogue); }
            } catch (err) { if (!abort.signal.aborted) setError(err.message); }
            finally { if (!abort.signal.aborted) setLoading(false); }
        }, 250);
        return () => { clearTimeout(timer); abort.abort(); };
    }, [url, query, page]);
    return { products: loading || error ? [] : products, pagination, loading, error, query,
        setQuery: value => { if (value !== query) setLoading(true); setQueryValue(value); setPage(1); },
        setPage: value => { setLoading(true); setPage(value); } };
}
