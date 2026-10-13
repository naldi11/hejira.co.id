import { Link } from '@inertiajs/react';
import Modal from '@/Components/Modal';

// Compatibility for old callers: direct stock adjustment is permanently disabled.
export default function AdjustModal({ product, onClose }) {
    return <Modal show={!!product} onClose={onClose} title="Stock opname melalui sesi Owner" icon="lock" maxWidth="max-w-md">
        <p className="text-gray-700 dark:text-gray-300">Penyesuaian langsung sudah ditutup. Hasil hitung hanya dapat disimpan dalam sesi aktif yang dibuka Owner untuk lokasi Anda.</p>
        <Link href={window.route('stock-opname.index')} className="mt-4 inline-block text-brand-500 underline">Lihat sesi stock opname</Link>
    </Modal>;
}
