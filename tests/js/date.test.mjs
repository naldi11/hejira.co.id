import assert from 'node:assert/strict';
import { test } from 'node:test';
import { todayWib } from '../../resources/js/lib/date.js';
import { formatDate } from '../../resources/js/lib/format.js';

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
