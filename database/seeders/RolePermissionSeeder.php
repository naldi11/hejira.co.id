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

        // --- ADMIN GUDANG ---
        $adminGudang = Role::firstOrCreate(['name' => 'admin_gudang', 'guard_name' => 'web']);
        $adminGudang->syncPermissions([
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
        ]);

        // --- KASIR JIHAN'S ---
        $kasirJihans = Role::firstOrCreate(['name' => 'kasir_jihans', 'guard_name' => 'web']);
        $kasirJihans->syncPermissions([
            'jihans.production.view', 'jihans.production.create', 'jihans.production.edit', 'jihans.production.delete',
            'jihans.pos.view', 'jihans.pos.create',
            'jihans.stock.view',
            'jihans.transfer_request.view', 'jihans.transfer_request.create',
            'master.product.view', 'master.customer.view', 'master.customer.manage', 'master.supplier.view',
            'global.notification.view',
        ]);

        // --- ADMIN JIHAN'S (dormant, same as kasir) ---
        $adminJihans = Role::firstOrCreate(['name' => 'admin_jihans', 'guard_name' => 'web']);
        $adminJihans->syncPermissions($kasirJihans->permissions->pluck('name')->toArray());

        // --- KASIR HENDHYS ---
        $kasirHendhys = Role::firstOrCreate(['name' => 'kasir_hendhys', 'guard_name' => 'web']);
        $kasirHendhys->syncPermissions([
            'hendhys.production.view', 'hendhys.production.create', 'hendhys.production.edit',
            'hendhys.pos.view', 'hendhys.pos.create',
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
