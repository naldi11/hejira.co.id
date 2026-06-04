// Skeleton loaders with a moving shimmer sweep.
//
// The shimmer is a light gradient that slides left→right across a muted block,
// driven by the `animate-shimmer` keyframe defined in tailwind.config.js.
// Use these placeholders while Inertia transitions or async fetches are pending.

/** Base shimmering block. Size/shape it with `className`. */
export function Skeleton({ className = '', ...props }) {
    return (
        <div className={`relative overflow-hidden rounded-lg bg-slate-200/70 ${className}`} {...props}>
            <div className="absolute inset-0 -translate-x-full animate-shimmer bg-gradient-to-r from-transparent via-white/70 to-transparent" />
        </div>
    );
}

/** A single shimmering text line. */
export function SkeletonText({ className = 'h-4 w-full' }) {
    return <Skeleton className={className} />;
}

/**
 * Placeholder rows for a table while data loads.
 * `columns` controls cells per row, `rows` controls how many rows.
 */
export function SkeletonTableRows({ rows = 8, columns = 5 }) {
    return Array.from({ length: rows }).map((_, r) => (
        <tr key={r} className="border-b border-slate-100">
            {Array.from({ length: columns }).map((__, c) => (
                <td key={c} className="px-6 py-4">
                    <Skeleton className="h-4" style={{ width: `${50 + ((r + c) % 4) * 12}%` }} />
                </td>
            ))}
        </tr>
    ));
}

/** Card-shaped placeholder, e.g. for dashboard stat tiles. */
export function SkeletonCard({ className = 'h-28' }) {
    return (
        <div className={`rounded-3xl border border-slate-200 bg-white p-6 ${className}`}>
            <Skeleton className="mb-3 h-3 w-1/3" />
            <Skeleton className="h-8 w-2/3" />
        </div>
    );
}
