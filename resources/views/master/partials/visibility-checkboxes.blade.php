{{--
  Partial: visibility checkboxes
  Required variables:
    $scope       - currentScope (gudang|jihans|hendhys)
    $model       - existing model or null (for edit/create)
    $isNew       - bool, true when creating
--}}
@php
    $vg = old('visible_gudang',  $isNew ? ($scope === 'gudang')                  : (bool)($model->visible_gudang  ?? false));
    $vj = old('visible_jihans',  $isNew ? in_array($scope, ['gudang','jihans'])  : (bool)($model->visible_jihans  ?? false));
    $vh = old('visible_hendhys', $isNew ? in_array($scope, ['gudang','hendhys']) : (bool)($model->visible_hendhys ?? false));
    $items = [
        ['visible_gudang',  'Gudang Tempua',   'warehouse',  $vg],
        ['visible_jihans',  "Jihan's Food",    'storefront', $vj],
        ['visible_hendhys', 'Hendhys Brownies','cake',       $vh],
    ];
@endphp
<div class="flex flex-wrap gap-sm">
    @foreach($items as [$fieldName, $label, $icon, $checked])
        <label x-data="{ on: {{ $checked ? 'true' : 'false' }} }"
            :class="on ? 'border-primary bg-primary-container' : 'border-outline-variant bg-surface-container-lowest hover:bg-surface-container'"
            class="flex items-center gap-xs cursor-pointer px-sm py-xs rounded-lg border-2 transition-all select-none text-sm">
            <input type="checkbox" name="{{ $fieldName }}" value="1"
                x-model="on"
                class="w-3.5 h-3.5 rounded accent-primary border-outline-variant">
            <span class="material-symbols-outlined text-[15px]" :class="on ? 'text-primary' : 'text-outline'">{{ $icon }}</span>
            <span :class="on ? 'font-semibold text-primary' : 'text-on-surface-variant'">{{ $label }}</span>
        </label>
    @endforeach
</div>
