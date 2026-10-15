import assert from 'node:assert/strict';
import { test } from 'node:test';
import { execFileSync } from 'node:child_process';
import { addCalendarDays, dateTimeLocalWib, endOfMonth, parseWibDate, todayWib, weekRangeWib } from '../../resources/js/lib/date.js';
import { formatDate, formatDateTime, formatRupiah, formatTime } from '../../resources/js/lib/format.js';

test('tanggal operasional berubah tepat tengah malam WIB', () => {
    assert.equal(todayWib(new Date('2026-10-12T16:59:59Z')), '2026-10-12');
    assert.equal(todayWib(new Date('2026-10-12T17:00:00Z')), '2026-10-13');
    assert.equal(todayWib(new Date('2026-10-13T01:41:07+07:00')), '2026-10-13');
});

test('pergantian bulan dan tahun mengikuti WIB', () => {
    assert.equal(todayWib(new Date('2026-12-31T17:00:00Z')), '2027-01-01');
    assert.equal(todayWib(new Date('2026-02-28T17:00:00Z')), '2026-03-01');
});

test('tampilan tanggal kalender dan timestamp konsisten dalam WIB', () => {
    assert.equal(formatDate('2026-10-13'), '13 Okt 2026');
    assert.equal(formatDate('2026-10-12T17:00:00Z'), '13 Okt 2026');
    assert.equal(formatDate(null), '-');
});

test('timestamp database tanpa offset dibaca WIB dan ISO ber-offset dikonversi', () => {
    assert.equal(parseWibDate('2026-10-13 00:15:00').toISOString(), '2026-10-12T17:15:00.000Z');
    assert.equal(formatDateTime('2026-10-13 00:15:00'), '13 Okt 2026 00.15 WIB');
    assert.equal(formatDateTime('2026-10-13T00:15:00'), '13 Okt 2026 00.15 WIB');
    assert.equal(formatDateTime('2026-10-12T17:15:00.000000Z'), '13 Okt 2026 00.15 WIB');
    assert.equal(formatTime('2026-10-13T03:15:00+10:00'), '00.15 WIB');
    assert.equal(dateTimeLocalWib('2026-12-31T17:00:00Z'), '2027-01-01T00:00');
});

test('rentang laporan memakai kalender, termasuk tahun kabisat dan pergantian tahun', () => {
    assert.equal(addCalendarDays('2026-12-28', 6), '2027-01-03');
    assert.equal(addCalendarDays('2026-03-05', 6), '2026-03-11');
    assert.equal(endOfMonth('2024-02-01'), '2024-02-29');
    assert.equal(endOfMonth('2026-02'), '2026-02-28');
    assert.deepEqual(weekRangeWib('2026-10-11T17:00:00Z'), ['2026-10-12', '2026-10-18']);
    assert.deepEqual(weekRangeWib('2026-10-11'), ['2026-10-05', '2026-10-11']);
});

test('nilai tanggal kosong atau tidak valid ditampilkan aman dan Rupiah memakai format Indonesia', () => {
    for (const value of [null, '', 'bukan-tanggal', '2026-02-31', '2026-10-13 99:00:00']) {
        assert.equal(formatDate(value), '-');
        assert.equal(formatDateTime(value), '-');
    }
    assert.equal(formatRupiah(1234567), 'Rp 1.234.567');
    assert.equal(formatDate('2026-10-15', { day: undefined, month: 'long', year: 'numeric' }), 'Oktober 2026');
});

test('hasil tetap sama saat perangkat memakai UTC, WIB, WITA, atau zona dengan DST', () => {
    const script = `
        import { addCalendarDays, dateTimeLocalWib, endOfMonth, todayWib, weekRangeWib } from './resources/js/lib/date.js';
        import { formatDate, formatDateTime } from './resources/js/lib/format.js';
        process.stdout.write(JSON.stringify([
            todayWib('2026-12-31T17:00:00Z'),
            formatDate('2026-10-13'),
            formatDateTime('2026-10-13 00:15:00'),
            formatDateTime('2026-10-12T17:15:00Z'),
            addCalendarDays('2026-03-05', 6),
            endOfMonth('2026-03-01'),
            weekRangeWib('2026-10-11T17:00:00Z'),
            dateTimeLocalWib('2026-12-31T17:00:00Z'),
        ]));
    `;
    const expected = ['2027-01-01', '13 Okt 2026', '13 Okt 2026 00.15 WIB', '13 Okt 2026 00.15 WIB', '2026-03-11', '2026-03-31', ['2026-10-12', '2026-10-18'], '2027-01-01T00:00'];
    for (const timezone of ['UTC', 'Asia/Jakarta', 'Asia/Makassar', 'America/New_York']) {
        const result = execFileSync(process.execPath, ['--input-type=module', '-e', script], {
            cwd: new URL('../../', import.meta.url), env: { ...process.env, TZ: timezone }, encoding: 'utf8',
        });
        assert.deepEqual(JSON.parse(result), expected, timezone);
    }
});
