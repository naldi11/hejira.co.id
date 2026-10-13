import { Link } from '@inertiajs/react';
import Icon from '@/Components/Icon';

/** Compact navigation for Laravel paginators, with readable Indonesian labels. */
export default function Pagination({ links = [] }) {
    const numbered = links.filter(link => /^\d+$/.test(String(link.label)));
    if (numbered.length <= 1) return null;

    const current = Number(numbered.find(link => link.active)?.label ?? 1);
    const last = Math.max(...numbered.map(link => Number(link.label)));
    const wanted = new Set(current <= 2 ? [1, 2, 3, last]
        : current >= last - 1 ? [1, last - 2, last - 1, last]
        : [1, current - 1, current, current + 1, last]);
    const pages = numbered.filter(link => wanted.has(Number(link.label)));
    const previous = links[0];
    const next = links[links.length - 1];
    const base = 'inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500';
    const idle = 'text-slate-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-800';
    const edge = (link, label, icon, reverse = false) => {
        const content = <><Icon name={icon} className={`text-lg ${reverse ? 'order-last' : ''}`} /><span>{label}</span></>;
        return link?.url ? <Link href={link.url} preserveScroll preserveState className={`${base} ${idle}`} aria-label={label}>{content}</Link>
            : <button type="button" disabled className={`${base} cursor-not-allowed text-slate-300 dark:text-gray-600`}>{content}</button>;
    };

    return <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <p className="text-xs text-slate-500 dark:text-gray-400">Halaman <strong className="text-slate-700 dark:text-gray-200">{current}</strong> dari {last.toLocaleString('id-ID')}</p>
        <nav className="flex w-full items-center justify-between gap-1 sm:w-auto sm:justify-end" aria-label="Navigasi halaman">
            {edge(previous, 'Sebelumnya', 'chevron_left')}
            <div className="hidden items-center gap-1 sm:flex">
                {pages.map((link, i) => <div key={link.label} className="flex items-center gap-1">
                    {i > 0 && Number(link.label) - Number(pages[i - 1].label) > 1 && <span aria-hidden="true" className="px-1 text-slate-400">…</span>}
                    {link.active ? <span aria-current="page" aria-label={`Halaman ${link.label}`} className={`${base} min-w-10 bg-brand-600 text-white`}>{link.label}</span>
                        : <Link href={link.url} preserveScroll preserveState aria-label={`Halaman ${link.label}`} className={`${base} ${idle} min-w-10`}>{link.label}</Link>}
                </div>)}
            </div>
            {edge(next, 'Berikutnya', 'chevron_right', true)}
        </nav>
    </div>;
}
