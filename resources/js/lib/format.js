// Shared formatting helpers — keep number/date rendering DRY across pages.

const numberFormatter = new Intl.NumberFormat('id-ID');

/** 1234.5 → "1.234" (no decimals, id-ID grouping). */
export function formatQty(value) {
    return numberFormatter.format(Math.round(Number(value) || 0));
}

/** 1234567 → "Rp 1.234.567". */
export function formatRupiah(value) {
    return 'Rp ' + numberFormatter.format(Math.round(Number(value) || 0));
}

/** "2026-06-04" → "04 Jun 2026". */
export function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
