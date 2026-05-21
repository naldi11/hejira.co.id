<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;

trait ScopesMasterData
{
    protected function getScopeInfo(Request $request)
    {
        $prefix = $request->route()->getPrefix() ?? '';
        if (str_contains($prefix, 'hendhys')) {
            return ['scope' => 'hendhys', 'layout' => 'layouts.hendhys', 'route' => 'hendhys.master.'];
        } elseif (str_contains($prefix, 'jihans')) {
            return ['scope' => 'jihans', 'layout' => 'layouts.jihans', 'route' => 'jihans.master.'];
        }
        return ['scope' => 'gudang', 'layout' => 'layouts.gudang', 'route' => 'master.'];
    }

    protected function getModelClass(string $modelName, string $scope)
    {
        $namespace = match($scope) {
            'hendhys' => 'Hendhys',
            'jihans'  => 'Jihans',
            default   => 'Gudang',
        };
        return "App\\Models\\{$namespace}\\{$modelName}";
    }

    protected function getModel(string $modelName, string $scope)
    {
        $class = $this->getModelClass($modelName, $scope);
        return new $class;
    }
}
