<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;

trait ScopesMasterData
{
    protected function getScopeInfo(Request $request)
    {
        // `layout` is the React layout COMPONENT name consumed by the shared master
        // pages (resources/js/Pages/Master/*), keyed in their `Layouts` map — not a
        // Blade view name. `route` is the named-route prefix for that scope.
        $prefix = $request->route()->getPrefix() ?? '';
        if (str_contains($prefix, 'hendhys')) {
            return ['scope' => 'hendhys', 'layout' => 'HendhysLayout', 'route' => 'hendhys.master.'];
        } elseif (str_contains($prefix, 'jihans')) {
            return ['scope' => 'jihans', 'layout' => 'JihansLayout', 'route' => 'jihans.master.'];
        }
        if ($request->route()->getName() === 'products.qr') {
            $user = auth()->user();
            if ($user->hasRole(['kasir_hendhys', 'admin_hendhys', 'super_admin_hendhys'])) {
                return ['scope' => 'hendhys', 'layout' => 'HendhysLayout', 'route' => ''];
            } elseif ($user->hasRole(['kasir_jihans', 'admin_jihans', 'super_admin_jihans'])) {
                return ['scope' => 'jihans', 'layout' => 'JihansLayout', 'route' => ''];
            } elseif ($user->hasRole('owner')) {
                return ['scope' => 'owner', 'layout' => 'OwnerLayout', 'route' => ''];
            }
            return ['scope' => 'gudang', 'layout' => 'GudangLayout', 'route' => ''];
        }

        return ['scope' => 'gudang', 'layout' => 'GudangLayout', 'route' => 'master.'];
    }

    protected function getModelClass(string $modelName, string $scope)
    {
        $namespace = match($scope) {
            'hendhys' => 'Hendhys',
            'jihans'  => 'Jihans',
            default   => 'Gudang',
        };
        return "App\\Models\\{$modelName}";
    }

    protected function getModel(string $modelName, string $scope)
    {
        $class = $this->getModelClass($modelName, $scope);
        return new $class;
    }
}
