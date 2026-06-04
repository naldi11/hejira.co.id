import { Link } from '@inertiajs/react';

/**
 * Renders a Laravel paginator's `links` array as Inertia links.
 * Pass `paginator.links` (the `{ url, label, active }[]` shape from
 * `->paginate()` serialized through an API Resource / `toArray()`).
 * Preserves scroll and only reloads the `data`-bearing props.
 */
export default function Pagination({ links = [] }) {
    // Hide the control entirely when there is only the prev/next pair (single page).
    if (links.length <= 3) return null;

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1.5" aria-label="Pagination">
            {links.map((link, i) => {
                const label = link.label
                    .replace('&laquo;', '‹')
                    .replace('&raquo;', '›')
                    .replace('Previous', '‹')
                    .replace('Next', '›');

                if (!link.url) {
                    return (
                        <span
                            key={i}
                            className="flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-bold text-slate-300"
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                }

                return (
                    <Link
                        key={i}
                        href={link.url}
                        preserveScroll
                        preserveState
                        className={`flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-bold transition-all ${
                            link.active
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20'
                                : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 hover:text-indigo-600'
                        }`}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </nav>
    );
}
