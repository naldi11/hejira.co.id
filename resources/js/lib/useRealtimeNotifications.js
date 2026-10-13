import { useEffect, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';

/**
 * Menyambungkan event broadcast Laravel ke UI.
 *
 * Sebelumnya seluruh rantai realtime terpasang tapi tidak pernah terhubung:
 * 3 event class mengimplementasikan ShouldBroadcast dan routes/channels.php
 * mendefinisikan channel-nya, tetapi TIDAK ADA satu pun komponen React yang
 * subscribe. Akibatnya setiap permintaan transfer hanya menumpuk baris di tabel
 * `jobs` tanpa pernah sampai ke layar siapa pun.
 *
 * Hook ini degradasi dengan aman: bila `window.Echo` tidak ada (VITE_REVERB_APP_KEY
 * belum diisi), notifikasi berbasis props Inertia tetap bekerja. Untuk Owner,
 * polling 60 detik juga memeriksa batas distribusi produksi tanpa broadcast.
 */

// Channel yang boleh didengar tiap role. Harus konsisten dengan otorisasi
// di routes/channels.php — kalau tidak, Echo akan ditolak saat authorize.
function channelsForRoles(roles, branchType) {
    const set = new Set();

    if (roles.includes('super_admin') || roles.includes('owner')) {
        set.add('gudang.notifications');
    }
    if (roles.includes('owner')) {
        set.add('owner.notifications');
    }
    // channels.php hanya mengizinkan kasir_hendhys di cabang pusat (atau owner).
    if ((roles.includes('kasir_hendhys') && branchType === 'pusat') || roles.includes('owner')) {
        set.add('hendhys.pusat.notifications');
    }

    return [...set];
}

function showToast(payload) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: payload?.message ?? 'Ada notifikasi baru',
        showConfirmButton: false,
        timer: 6000,
        timerProgressBar: true,
        didOpen: (el) => {
            if (!payload?.url) return;
            el.style.cursor = 'pointer';
            el.addEventListener('click', () => router.visit(payload.url));
        },
    });
}

export default function useRealtimeNotifications() {
    const { auth, notifications } = usePage().props;
    const userId = auth?.user?.id ?? null;
    const roles = auth?.user?.roles ?? [];
    const branchType = auth?.user?.branch?.type ?? null;

    // Simpan sebagai string supaya efek tidak dijalankan ulang tiap render
    // hanya karena Inertia membuat array roles baru.
    const rolesKey = Array.isArray(roles) ? roles.join(',') : '';

    // router.reload dipanggil dari callback; simpan di ref agar callback stabil.
    const refreshRef = useRef(null);
    refreshRef.current = () => router.reload({ only: roles.includes('owner') ? ['notifications', 'production_monitor', 'jihans_stocks', 'distributions', 'batch_stock'] : ['notifications'], async: true });

    // Check deadline and branch receipts even when no broadcast connection exists.
    useEffect(() => {
        if (!userId || !rolesKey.split(',').some(role => ['owner', 'super_admin', 'admin_hendhys', 'kasir_hendhys', 'admin_jihans', 'kasir_jihans'].includes(role))) return;
        const refresh = () => { if (!document.hidden) refreshRef.current?.(); };
        const timer = window.setInterval(refresh, 60000);
        document.addEventListener('visibilitychange', refresh);
        return () => { window.clearInterval(timer); document.removeEventListener('visibilitychange', refresh); };
    }, [userId, rolesKey]);

    const stockAlert = notifications?.items?.find(item => item.id?.startsWith('production_stock_'));
    useEffect(() => {
        if (!stockAlert || !userId) return;
        const key = `production-alert:${userId}:${stockAlert.id}`;
        try {
            if (sessionStorage.getItem(key)) return;
            sessionStorage.setItem(key, 'shown');
        } catch { /* Badge tetap berfungsi jika penyimpanan browser dibatasi. */ }
        Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: stockAlert.title, text: stockAlert.message, showConfirmButton: false, timer: 10000, timerProgressBar: true, didOpen: el => {
            el.style.cursor = 'pointer';
            el.addEventListener('click', () => router.visit(stockAlert.path));
        } });
    }, [userId, stockAlert?.id]);

    useEffect(() => {
        if (!userId || !window.Echo) return;

        const names = [
            ...channelsForRoles(rolesKey ? rolesKey.split(',') : [], branchType),
            `user.${userId}.notifications`,
        ];

        const onEvent = (payload) => {
            showToast(payload);
            // Segarkan badge/daftar notifikasi tanpa reload halaman penuh.
            refreshRef.current?.();
        };

        const subscribed = names.map((name) => {
            const ch = window.Echo.private(name);
            // listenToAll menangkap ketiga event (TransferRequestCreated,
            // TransferRequestStatusChanged, BranchRequestCreated) tanpa perlu
            // mencantumkan namanya satu per satu di sini.
            ch.listenToAll((_event, payload) => onEvent(payload));
            return name;
        });

        return () => {
            subscribed.forEach((name) => window.Echo.leave(name));
        };
    }, [userId, rolesKey, branchType]);
}
