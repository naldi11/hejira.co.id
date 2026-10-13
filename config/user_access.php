<?php

return [
    'units' => [
        ['value' => 'hendhys', 'label' => 'Hendhys Brownies', 'sublabel' => 'Penjualan dan operasional Hendhys'],
        ['value' => 'jihans', 'label' => "Jihan's Food", 'sublabel' => 'Penjualan dan operasional Jihan’s'],
        ['value' => 'gudang', 'label' => 'Gudang Utama', 'sublabel' => 'Administrasi gudang dan data master'],
        ['value' => 'all', 'label' => 'Semua unit usaha', 'sublabel' => 'Administrasi Gudang, Hendhys, dan Jihan’s'],
    ],
    'entity_roles' => [
        'all' => ['super_admin'],
        'gudang' => ['super_admin'],
        'hendhys' => ['kasir_hendhys', 'admin_hendhys'],
        'jihans' => ['kasir_jihans', 'admin_jihans'],
    ],
    'roles' => [
        'kasir_hendhys' => [
            'label' => 'Kasir Hendhys',
            'description' => 'Untuk petugas yang melayani penjualan di kasir Hendhys.',
            'can' => ['Mencatat penjualan dan menyimpan transaksi tertunda saat shift aktif.', 'Membuka dan menutup shift sendiri, mencetak struk dan invoice.', 'Melihat stok, riwayat transaksi cabang aktif, dan mengunduh laporan.', 'Mengajukan permintaan barang, menerima kiriman, dan mencatat retur sesuai lokasi aktif.', 'Menghitung dan menyesuaikan stok cabang aktif hanya selama sesi stock opname yang dibuka Owner.', 'Melakukan mutasi antar cabang dan konfirmasi penerimaan sesuai akses cabang yang dikelola.'],
            'cannot' => ['Mengelola akun pengguna atau data master produk, pelanggan, dan pemasok.', 'Mencatat produksi dan distribusi dari pusat.', 'Membatalkan penjualan melalui fitur pembatalan admin.', 'Mengakses operasional Jihan’s atau Gudang Utama.'],
        ],
        'admin_hendhys' => [
            'label' => 'Admin Hendhys',
            'description' => 'Untuk pengelola stok, data, laporan, dan operasional Hendhys.',
            'can' => ['Mengelola produk, pelanggan, dan pemasok Hendhys.', 'Melihat stok, riwayat transaksi, laporan penjualan, dan detail shift.', 'Mengelola permintaan barang, penerimaan, dan retur sesuai lokasi aktif.', 'Membatalkan penjualan selama shift transaksi masih terbuka.', 'Menghitung dan menyesuaikan stok cabang aktif hanya selama sesi stock opname yang dibuka Owner.', 'Melakukan mutasi antar cabang dan konfirmasi penerimaan sesuai akses cabang yang dikelola.'],
            'centre_only' => ['Mencatat produksi dan prediksi bahan, serta mengirim produk dari pusat ke cabang.'],
            'cannot' => ['Menggunakan layar kasir untuk mencatat penjualan POS.', 'Membuat akun pengguna atau mengakses operasional unit lain.'],
        ],
        'kasir_jihans' => [
            'label' => 'Kasir Jihan’s',
            'description' => 'Untuk petugas yang melayani penjualan di kasir Jihan’s.',
            'can' => ['Mencatat penjualan dan menyimpan transaksi tertunda saat shift aktif.', 'Mengubah transaksi selama shift asalnya masih terbuka.', 'Membuka dan menutup shift sendiri, mencetak struk, dan melihat riwayat.', 'Melihat stok, mengunduh laporan, serta menangani permintaan, penerimaan, dan retur barang.', 'Menghitung dan menyesuaikan stok cabang aktif hanya selama sesi stock opname yang dibuka Owner.', 'Mengirim stok penjualan bersama Jihan’s ke cabang Hendhys dari cabang aktif, dengan riwayat lengkap.'],
            'cannot' => ['Mengelola data master, karyawan, atau akun pengguna.', 'Mencatat produksi, prediksi bahan, dan rekap produksi.', 'Membatalkan penjualan melalui fitur pembatalan admin.', 'Mengakses operasional Hendhys atau Gudang Utama.'],
        ],
        'admin_jihans' => [
            'label' => 'Admin Jihan’s',
            'description' => 'Untuk pengelola stok, produksi, data, dan laporan Jihan’s.',
            'can' => ['Mengelola produk, pelanggan, pemasok, dan karyawan Jihan’s.', 'Mencatat produksi, prediksi bahan, rekap produksi, dan faktur.', 'Melihat stok, riwayat transaksi, laporan penjualan, dan detail shift.', 'Menangani permintaan, penerimaan, dan retur; membatalkan penjualan selama shift transaksi masih terbuka.', 'Menghitung dan menyesuaikan stok cabang aktif hanya selama sesi stock opname yang dibuka Owner.', 'Mengirim stok penjualan bersama Jihan’s ke cabang Hendhys dari cabang aktif, dengan riwayat lengkap.'],
            'cannot' => ['Menggunakan layar kasir untuk mencatat penjualan POS.', 'Membuat akun pengguna atau mengakses operasional unit lain.'],
        ],
        'super_admin' => [
            'label' => 'Super Admin',
            'description' => 'Untuk pengelola sistem. Pilih hanya jika orang ini perlu mengatur pengguna dan operasional.',
            'can' => ['Mengelola akun pengguna selain Owner, termasuk peran, penempatan, dan status aktif.', 'Mengelola data master, pembelian, penerimaan, stok, permintaan, dan distribusi Gudang Utama.', 'Melakukan stock opname sesuai unit akses hanya dalam sesi yang dibuka Owner.', 'Melakukan mutasi antar cabang dan konfirmasi penerimaan sesuai akses cabang yang dikelola.'],
            'all_unit_can' => ['Mengakses administrasi Hendhys dan Jihan’s: data master, stok, produksi, laporan, dan pengelolaan transaksi.'],
            'cannot' => ['Menggunakan layar kasir POS; penjualan POS memerlukan peran Kasir.', 'Membuat, mengubah, atau menghapus akun Owner.', 'Membuka akses stock opname sendiri atau menyesuaikan stok tanpa sesi Owner.'],
            'gudang_only_cannot' => ['Mengakses operasional Hendhys atau Jihan’s. Pilih “Semua unit usaha” jika perlu administrasi kedua unit tersebut.'],
        ],
        'owner' => [
            'label' => 'Pemilik (Owner)',
            'description' => 'Akun khusus pemilik yang sudah disiapkan sistem; tidak dapat dibuat atau diberikan melalui form ini.',
            'can' => ['Melihat dashboard pemilik, rincian kinerja unit dan cabang, detail shift, serta laporan dan ekspor.', 'Melihat rincian PO, prediksi dan produksi aktual, serta peringatan stok pusat Hendhys setelah 17.00 WIB.', 'Menghapus sisa stok produksi Jihans dan Hendhys satu per satu atau sekaligus, serta membatalkan penghapusan dengan alasan wajib dan riwayat lengkap.', 'Membuka dan menutup sesi stock opname untuk lokasi tertentu, menentukan batas waktu, dan memantau hasil petugas.'],
            'cannot' => ['Menggunakan layar kasir POS atau menu operasional admin melalui peran Owner.', 'Membuat akun Owner baru melalui form pengguna.'],
        ],
    ],
];
