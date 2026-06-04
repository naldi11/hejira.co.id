import Icon from './Icon';

/** Centered empty-state placeholder for tables/lists with no rows. */
export default function EmptyState({ icon = 'inbox', message = 'Tidak ada data.', colSpan }) {
    const content = (
        <div className="flex flex-col items-center py-12">
            <Icon name={icon} className="mb-4 text-[64px] text-slate-200" />
            <p className="font-bold italic text-slate-400">{message}</p>
        </div>
    );

    // When used inside a table, wrap in a full-width row.
    if (colSpan) {
        return (
            <tr>
                <td colSpan={colSpan} className="px-6 py-0 text-center">{content}</td>
            </tr>
        );
    }
    return content;
}
