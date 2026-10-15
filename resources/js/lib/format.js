// Shared formatting helpers — keep number/date rendering DRY across pages.
import { APP_LOCALE, APP_TIME_ZONE, parseWibDate } from './date.js';

const numberFormatter = new Intl.NumberFormat(APP_LOCALE);

/** 1234.5 → "1.234" (no decimals, id-ID grouping). */
export function formatQty(value) {
    return numberFormatter.format(Math.round(Number(value) || 0));
}

/** 1234567 → "Rp 1.234.567". */
export function formatRupiah(value) {
    return 'Rp ' + numberFormatter.format(Math.round(Number(value) || 0));
}

/** "2026-06-04" → "04 Jun 2026". */
export function formatDate(value, options = {}) {
    const date = parseWibDate(value);
    if (!date) return '-';
    return date.toLocaleDateString(APP_LOCALE, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...options,
        timeZone: APP_TIME_ZONE,
    });
}

export function formatTime(value, options = {}) {
    const date = parseWibDate(value);
    if (!date) return '-';
    return date.toLocaleTimeString(APP_LOCALE, {
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23', ...options, timeZone: APP_TIME_ZONE,
    }) + ' WIB';
}

export function formatDateTime(value) {
    const date = parseWibDate(value);
    if (!date) return '-';
    return `${formatDate(date)} ${formatTime(date)}`;
}
