import Select from 'react-select';
import CreatableSelect from 'react-select/creatable';

/**
 * Dropdown pencarian berbasis react-select, dibungkus agar seragam di seluruh aplikasi.
 *
 * Kenapa dibungkus, bukan memakai react-select langsung di tiap halaman:
 * 1. Gaya visualnya cukup ditulis SEKALI di sini. Dipakai di puluhan tempat, jadi
 *    kalau tampilannya perlu diubah, cukup satu file.
 * 2. Memakai mode `unstyled` + `classNames` supaya sepenuhnya memakai Tailwind
 *    aplikasi ini — termasuk mode gelap — bukan CSS bawaan react-select yang
 *    tidak ikut berubah saat tema gelap dinyalakan.
 * 3. API-nya dibuat menyerupai <select> biasa: cukup kirim `value` dan
 *    `onChange(nilai)` berupa nilai mentah, bukan objek {label, value}. Ini
 *    memperkecil peluang salah pakai saat mengganti puluhan <select> lama.
 *
 * Pemakaian:
 *   <SelectField
 *      options={produk.map(p => ({ value: p.id, label: p.name }))}
 *      value={data.product_id}
 *      onChange={(v) => setData('product_id', v)}
 *      placeholder="Pilih produk..."
 *   />
 *
 * Untuk field yang boleh diisi nilai baru (mis. Satuan/Kategori produk), pakai
 * `creatable` — kemampuan "ketik baru" dari <datalist> lama tetap terjaga.
 *
 * Sebuah option boleh membawa `sublabel` (mis. kode produk atau nama kasir).
 * Sublabel tampil sebagai baris kedua yang lebih redup DAN ikut dicari, karena
 * kode produk justru yang paling sering diketik. Option tanpa `sublabel` sama
 * sekali tidak terpengaruh.
 */

const control = (state) => [
    'w-full min-h-11 rounded-lg border bg-transparent px-3 py-1 text-sm transition',
    state.isDisabled
        ? 'cursor-not-allowed border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/40'
        : 'border-gray-300 dark:border-gray-700 dark:bg-gray-900/50',
    state.isFocused ? 'border-brand-300 ring-3 ring-brand-500/10 dark:border-brand-800' : '',
].join(' ');

const option = (state) => [
    'cursor-pointer px-3 py-2 text-sm transition',
    state.isSelected
        ? 'bg-brand-500 text-white'
        : state.isFocused
            ? 'bg-brand-50 text-brand-700 dark:bg-white/[0.06] dark:text-white'
            : 'text-gray-700 dark:text-gray-300',
].join(' ');

const classNames = {
    control,
    valueContainer: () => 'gap-1',
    placeholder:    () => 'text-gray-400 dark:text-gray-500',
    singleValue:    () => 'text-gray-800 dark:text-white/90',
    input:          () => 'text-gray-800 dark:text-white/90',
    indicatorSeparator: () => 'bg-gray-200 dark:bg-gray-700 my-2',
    dropdownIndicator:  () => 'px-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300',
    clearIndicator:     () => 'px-2 text-gray-400 hover:text-rose-500',
    menu: () => 'mt-1 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800 z-50',
    menuList:     () => 'max-h-60 overflow-y-auto custom-scrollbar py-1',
    option,
    noOptionsMessage: () => 'px-3 py-2 text-sm text-gray-400 dark:text-gray-500',
    multiValue:      () => 'rounded bg-brand-50 dark:bg-white/[0.08]',
    multiValueLabel: () => 'px-2 py-0.5 text-xs text-brand-700 dark:text-white/90',
    multiValueRemove:() => 'px-1 rounded-r text-brand-500 hover:bg-rose-100 hover:text-rose-600',
};

// Di menu sublabel ditaruh di baris kedua; di kotak terpilih ruangnya sempit,
// jadi disandingkan mendatar.
const formatOptionLabel = (opt, { context }) => {
    if (!opt.sublabel) return opt.label;

    return context === 'value' ? (
        <span>
            {opt.label}
            <span className="ml-2 text-[11px] text-gray-400 dark:text-gray-500">{opt.sublabel}</span>
        </span>
    ) : (
        <span className="flex flex-col">
            <span>{opt.label}</span>
            <span className="text-[11px] leading-4 text-gray-400 dark:text-gray-500">{opt.sublabel}</span>
        </span>
    );
};

// Penyaring bawaan react-select hanya melihat label, sedangkan sublabel berisi
// kode produk yang justru sering dipakai mencari.
const filterOption = (candidate, input) => {
    const query = input.trim().toLowerCase();
    if (!query) return true;

    const label = String(candidate.label ?? '').toLowerCase();
    const sublabel = String(candidate.data?.sublabel ?? '').toLowerCase();

    return label.includes(query) || sublabel.includes(query);
};

export default function SelectField({
    options = [],
    value,
    onChange,
    placeholder = 'Pilih...',
    isDisabled = false,
    isClearable = true,
    creatable = false,
    id,
    name,
    required = false,
    createLabel = (input) => `Tambah "${input}"`,
    ...rest
}) {
    // Terima nilai mentah (id/string) supaya pemanggilnya semirip mungkin dengan
    // <select> biasa. Perbandingan sengaja longgar (==) karena value dari props
    // Inertia kerap berupa string sedangkan option.value berupa number.
    const selected = options.find((o) => o.value == value) ?? null;

    // Nilai yang belum ada di daftar (hasil "ketik baru") tetap ditampilkan.
    const fallback = !selected && value ? { value, label: String(value) } : null;

    const Component = creatable ? CreatableSelect : Select;

    // Disebar hanya bila memang ada sublabel — bukan dikirim sebagai undefined —
    // supaya puluhan pemakaian lama benar-benar memakai bawaan react-select.
    const sublabelProps = options.some((o) => o.sublabel)
        ? { formatOptionLabel, filterOption }
        : {};

    return (
        <Component
            unstyled
            inputId={id}
            name={name}
            options={options}
            value={selected ?? fallback}
            onChange={(opt) => onChange?.(opt ? opt.value : '')}
            {...sublabelProps}
            placeholder={placeholder}
            isDisabled={isDisabled}
            isClearable={isClearable}
            classNames={classNames}
            noOptionsMessage={() => 'Tidak ada pilihan'}
            formatCreateLabel={createLabel}
            menuPlacement="auto"
            // Menu dirender ke <body> agar tidak terpotong oleh kontainer
            // ber-overflow-hidden (kartu form & tabel banyak memakainya).
            menuPortalTarget={typeof document !== 'undefined' ? document.body : null}
            styles={{ menuPortal: (base) => ({ ...base, zIndex: 60 }) }}
            required={required}
            {...rest}
        />
    );
}
