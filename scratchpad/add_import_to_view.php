<?php

$indexFile = __DIR__ . '/../resources/views/master/products/index.blade.php';

if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    
    if (strpos($content, 'id="importModal"') === false) {
        // Find the "Tambah Produk" button to add the other buttons next to it
        $search = '<a href="{{ route(($routePrefix ?? \'master.\') . \'products.create\')"';
        
        $buttons = '
                <div class="flex items-center gap-xs">
                    <button type="button" onclick="document.getElementById(\'importModal\').classList.remove(\'hidden\')"
                        class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-surface-container-high transition-all">
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                        Import Excel
                    </button>
                    <a href="{{ route(($routePrefix ?? \'master.\') . \'products.template\') }}"
                        class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-surface-container-high transition-all">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        Template
                    </a>
                </div>
                <a href="{{ route(($routePrefix ?? \'master.\') . \'products.create\')"';
                
        $content = str_replace($search, $buttons, $content);
        
        // Add Modal at the end of the file before @endsection
        $modal = '
    {{-- Import Modal --}}
    <div id="importModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black bg-opacity-50" aria-hidden="true" onclick="document.getElementById(\'importModal\').classList.add(\'hidden\')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-surface rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-primary-container rounded-full">
                        <span class="material-symbols-outlined text-on-primary-container">upload_file</span>
                    </div>
                    <div class="mt-3 text-center sm:mt-5">
                        <h3 class="text-lg font-medium leading-6 text-on-surface" id="modal-title">Import Data Produk</h3>
                        <div class="mt-2">
                            <p class="text-sm text-on-surface-variant">Upload file Excel (.xlsx, .xls) atau CSV yang sesuai dengan format template. Produk dengan Barcode atau Nama yang sama akan otomatis di-update.</p>
                        </div>
                    </div>
                </div>
                <form action="{{ route(($routePrefix ?? \'master.\') . \'products.import\') }}" method="POST" enctype="multipart/form-data" class="mt-5 sm:mt-6">
                    @csrf
                    <div class="mb-4">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary hover:file:text-on-primary transition-colors">
                    </div>
                    <div class="flex gap-3 justify-end mt-4">
                        <button type="button" onclick="document.getElementById(\'importModal\').classList.add(\'hidden\')" class="px-4 py-2 bg-surface-container text-on-surface rounded-lg font-medium hover:bg-surface-container-high transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-medium hover:bg-on-primary-fixed-variant transition-colors">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
';
        $content = str_replace('@endsection', $modal . "\n@endsection", $content);
        
        file_put_contents($indexFile, $content);
        echo "Added buttons and modal to index.blade.php\n";
    }
}
