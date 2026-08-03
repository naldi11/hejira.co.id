<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Gudang
            'gudang.po.view', 'gudang.po.create', 'gudang.po.edit', 'gudang.po.delete',
            'gudang.receiving.view', 'gudang.receiving.create',
            'gudang.stock.view', 'gudang.stock.adjust',
            'gudang.transfer_request.view', 'gudang.transfer_request.approve', 'gudang.transfer_request.reject',
            'gudang.transfer_out.view', 'gudang.transfer_out.create',
            'gudang.user.manage',

            // Jihan's
            'jihans.production.view', 'jihans.production.create', 'jihans.production.edit', 'jihans.production.delete',
            'jihans.pos.view', 'jihans.pos.create',
            'jihans.stock.view',
            'jihans.transfer_request.view', 'jihans.transfer_request.create',

            // Hendhys
            'hendhys.production.view', 'hendhys.production.create', 'hendhys.production.edit',
            'hendhys.pos.view', 'hendhys.pos.create',
            'hendhys.stock.view',
            'hendhys.transfer_request.view', 'hendhys.transfer_request.create',
            'hendhys.branch_request.view', 'hendhys.branch_request.create', 'hendhys.branch_request.approve',
            'hendhys.transfer_to_branch.view', 'hendhys.transfer_to_branch.create',
            'hendhys.return.view', 'hendhys.return.create',

            // Master data
            'master.supplier.view', 'master.supplier.manage',
            'master.customer.view', 'master.customer.manage',
            'master.product.view', 'master.product.manage',
            'master.branch.view', 'master.branch.manage',
            'master.unit.manage', 'master.brand.manage', 'master.category.manage',

            // Owner & Global
            'owner.dashboard', 'owner.reports',
            'global.activity_log.view', 'global.notification.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // --- OWNER ---
        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $owner->syncPermissions([
            'owner.dashboard', 'owner.reports',
            'global.activity_log.view', 'global.notification.view',
            'gudang.po.view', 'gudang.receiving.view', 'gudang.stock.view',
            'gudang.transfer_request.view', 'gudang.transfer_out.view',
            'jihans.production.view', 'jihans.pos.view', 'jihans.stock.view', 'jihans.transfer_request.view',
            'hendhys.production.view', 'hendhys.pos.view', 'hendhys.stock.view',
            'hendhys.transfer_request.view', 'hendhys.branch_request.view',
            'hendhys.transfer_to_branch.view', 'hendhys.return.view',
            'master.supplier.view', 'master.customer.view', 'master.product.view', 'master.branch.view',
        ]);

        // --- SUPER ADMIN ---
        // Peleburan dari admin_gudang + super_admin_hendhys + super_admin_jihans.
        // Mengelola Gudang Utama sekaligus mengawasi Hendhys, Jihans, dan (nanti) Izzy.
        // Catatan: permission di bawah BELUM ditegakkan di kode — otorisasi masih
        // sepenuhnya berbasis nama role lewat middleware `role:`. Daftar ini dijaga
        // akurat sebagai dokumentasi niat dan jalan naik bila nanti dibutuhkan
        // kontrol yang lebih halus.
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions([
            'gudang.po.view', 'gudang.po.create', 'gudang.po.edit', 'gudang.po.delete',
            'gudang.receiving.view', 'gudang.receiving.create',
            'gudang.stock.view', 'gudang.stock.adjust',
            'gudang.transfer_request.view', 'gudang.transfer_request.approve', 'gudang.transfer_request.reject',
            'gudang.transfer_out.view', 'gudang.transfer_out.create',
            'gudang.user.manage',
            'master.supplier.view', 'master.supplier.manage',
            'master.customer.view', 'master.customer.manage',
            'master.product.view', 'master.product.manage',
            'master.branch.view', 'master.branch.manage',
            'master.unit.manage', 'master.brand.manage', 'master.category.manage',
            'global.activity_log.view', 'global.notification.view',
            // Pengawasan lintas unit — sengaja TANPA *.pos.* karena kasir yang
            // mengoperasikan POS (diputuskan setelah data membuktikan super admin
            // tidak pernah sekali pun membuat transaksi atau membuka shift).
            'jihans.production.view', 'jihans.stock.view', 'jihans.transfer_request.view',
            'hendhys.production.view', 'hendhys.stock.view',
            'hendhys.transfer_request.view', 'hendhys.branch_request.view',
            'hendhys.transfer_to_branch.view', 'hendhys.return.view',
        ]);

        // --- KASIR JIHAN'S ---
        $kasirJihans = Role::firstOrCreate(['name' => 'kasir_jihans', 'guard_name' => 'web']);
        $kasirJihans->syncPermissions([
            'jihans.pos.view', 'jihans.pos.create',
            'jihans.stock.view',
            'master.product.view', 'master.customer.view', 'master.customer.manage',
            'global.notification.view',
        ]);

        // --- ADMIN JIHAN'S ---
        $adminJihans = Role::firstOrCreate(['name' => 'admin_jihans', 'guard_name' => 'web']);
        $adminJihans->syncPermissions([
            'jihans.production.view', 'jihans.production.create', 'jihans.production.edit', 'jihans.production.delete',
            'jihans.stock.view',
            'jihans.transfer_request.view', 'jihans.transfer_request.create',
            'master.product.view', 'master.customer.view', 'master.customer.manage', 'master.supplier.view',
            'global.notification.view',
        ]);

        // --- KASIR HENDHYS ---
        $kasirHendhys = Role::firstOrCreate(['name' => 'kasir_hendhys', 'guard_name' => 'web']);
        $kasirHendhys->syncPermissions([
            'hendhys.pos.view', 'hendhys.pos.create',
            'hendhys.stock.view',
            'hendhys.transfer_to_branch.view', // to view & receive incoming transfers
            'master.customer.view', 'master.customer.manage',
            'global.notification.view',
        ]);

        // --- ADMIN HENDHYS (New) ---
        $adminHendhys = Role::firstOrCreate(['name' => 'admin_hendhys', 'guard_name' => 'web']);
        $adminHendhys->syncPermissions([
            'hendhys.production.view', 'hendhys.production.create', 'hendhys.production.edit',
            'hendhys.stock.view',
            'hendhys.transfer_request.view', 'hendhys.transfer_request.create',
            'hendhys.branch_request.view', 'hendhys.branch_request.create', 'hendhys.branch_request.approve',
            'hendhys.transfer_to_branch.view', 'hendhys.transfer_to_branch.create',
            'hendhys.return.view', 'hendhys.return.create',
            'master.product.view', 'master.customer.view', 'master.customer.manage', 'master.supplier.view',
            'global.notification.view',
        ]);

    }
}

