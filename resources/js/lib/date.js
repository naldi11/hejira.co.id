export const APP_TIME_ZONE = 'Asia/Jakarta';
export const APP_LOCALE = 'id-ID';

/** SQL timestamps without an offset are local WIB; ISO offsets describe an instant. */
export function parseWibDate(value) {
    if (value === null || value === undefined || value === '') return null;
    let input = value;
    if (typeof value === 'string') {
        input = value.trim();
        const local = /^(\d{4}-\d{2}-\d{2})(?:[T ](\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?))?$/.exec(input);
        if (local) {
            // Reject impossible calendar dates instead of silently rolling into the next month.
            const calendar = new Date(`${local[1]}T00:00:00Z`);
            if (Number.isNaN(calendar.getTime()) || calendar.toISOString().slice(0, 10) !== local[1]) return null;
            input = `${local[1]}T${local[2] || '00:00:00'}+07:00`;
        } else if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}.*(?:Z|[+-]\d{2}:?\d{2})$/i.test(input)) {
            // Do not let browser-dependent free-form parsing guess the timezone.
            return null;
        }
    }
    const date = new Date(input);
    return Number.isNaN(date.getTime()) ? null : date;
}

// Operational calendar dates follow WIB even if the browser uses another timezone.
export function todayWib(now = new Date()) {
    const date = parseWibDate(now);
    if (!date) return '';
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: APP_TIME_ZONE, year: 'numeric', month: '2-digit', day: '2-digit',
    }).formatToParts(date);
    const part = (type) => parts.find((value) => value.type === type).value;
    return `${part('year')}-${part('month')}-${part('day')}`;
}

/** Calendar arithmetic uses UTC fields, so browser timezone and DST cannot change the day. */
export function addCalendarDays(value, days) {
    const calendar = todayWib(value);
    if (!calendar) return '';
    const date = new Date(`${calendar}T00:00:00Z`);
    date.setUTCDate(date.getUTCDate() + days);
    return date.toISOString().slice(0, 10);
}

export function endOfMonth(value) {
    const calendar = todayWib(typeof value === 'string' && value.length === 7 ? `${value}-01` : value);
    if (!calendar) return '';
    const date = new Date(`${calendar}T00:00:00Z`);
    date.setUTCMonth(date.getUTCMonth() + 1, 0);
    return date.toISOString().slice(0, 10);
}

export function weekRangeWib(value = new Date()) {
    const calendar = todayWib(value);
    if (!calendar) return ['', ''];
    const day = new Date(`${calendar}T00:00:00Z`).getUTCDay();
    const start = addCalendarDays(calendar, -(day === 0 ? 6 : day - 1));
    return [start, addCalendarDays(start, 6)];
}

/** Value for a datetime-local input whose label explicitly says WIB. */
export function dateTimeLocalWib(value = new Date()) {
    const date = parseWibDate(value);
    if (!date) return '';
    const time = new Intl.DateTimeFormat('en-GB', {
        timeZone: APP_TIME_ZONE, hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).format(date);
    return `${todayWib(date)}T${time}`;
}
