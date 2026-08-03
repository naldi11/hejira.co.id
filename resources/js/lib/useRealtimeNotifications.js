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
 * belum diisi), hook tidak melakukan apa-apa dan aplikasi tetap berjalan seperti
 * biasa dengan notifikasi berbasis props Inertia.
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
    const { auth } = usePage().props;
    const userId = auth?.user?.id ?? null;
    const roles = auth?.user?.roles ?? [];
    const branchType = auth?.user?.branch?.type ?? null;

    // Simpan sebagai string supaya efek tidak dijalankan ulang tiap render
    // hanya karena Inertia membuat array roles baru.
    const rolesKey = Array.isArray(roles) ? roles.join(',') : '';

    // router.reload dipanggil dari callback; simpan di ref agar callback stabil.
    const refreshRef = useRef(null);
    refreshRef.current = () => router.reload({ only: ['notifications'] });

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
