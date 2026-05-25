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
        ['visible_gudang',  'Gudang Utama',   'warehouse',  $vg, 'blue'],
        ['visible_jihans',  "Jihan's Food",    'bakery_dining', $vj, 'orange'],
        ['visible_hendhys', 'Hendhys Brownies','cake',       $vh, 'amber'],
    ];
@endphp
<div class="flex flex-wrap gap-3">
    @foreach($items as [$fieldName, $label, $icon, $checked, $color])
        <label x-data="{ on: {{ $checked ? 'true' : 'false' }} }"
            :class="on ? 'border-indigo-500 bg-indigo-50 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'"
            class="flex items-center gap-3 cursor-pointer px-4 py-2 rounded-xl border-2 transition-all select-none group min-w-[140px]">
            <input type="checkbox" name="{{ $fieldName }}" value="1"
                x-model="on"
                class="hidden">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                 :class="on ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200'">
                <span class="material-symbols-outlined text-[18px]" :class="on ? 'fill' : ''">{{ $icon }}</span>
            </div>
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-tighter" :class="on ? 'text-indigo-600' : 'text-slate-400'">{{ $label }}</span>
                <span class="text-[8px] font-bold uppercase tracking-widest" :class="on ? 'text-indigo-400' : 'text-slate-300'" x-text="on ? 'Enabled' : 'Disabled'"></span>
            </div>
        </label>
    @endforeach
</div>
