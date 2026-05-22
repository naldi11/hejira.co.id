<?php

$dir = __DIR__ . '/../resources/views/master/products/';

// --- Fix index.blade.php ---
$indexFile = $dir . 'index.blade.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    // Remove Jenis filter
    $content = preg_replace('/<select name="jenis".*?<\/select>/s', '', $content);
    // Remove Jenis th
    $content = preg_replace('/<th[^>]*>Jenis\s*<\/th>/s', '', $content);
    // Remove Jenis td
    $content = preg_replace('/<td[^>]*>\{\{ ucwords\(str_replace\(\'_\', \' \', \$product->jenis\)\) \}\}<\/td>/s', '', $content);
    
    // Also remove from requested checks
    $content = str_replace("'search', 'jenis', 'entity_scope', 'status'", "'search', 'entity_scope', 'status'", $content);
    
    file_put_contents($indexFile, $content);
    echo "Fixed index.blade.php\n";
}

// --- Fix form.blade.php ---
$formFile = $dir . 'form.blade.php';
if (file_exists($formFile)) {
    $content = file_get_contents($formFile);
    
    // Remove Jenis dropdown completely
    // We will search for the label "Jenis" and remove the surrounding container.
    $content = preg_replace('/<div class="space-y-xs">\s*<label for="jenis".*?<\/select>\s*@error\(\'jenis\'\).*?<\/div>\s*<\/div>/s', '', $content);
    $content = preg_replace('/<div class="space-y-xs">\s*<label for="jenis".*?<\/select>\s*<\/div>/s', '', $content);
    
    // We should also add entity_scope dropdown
    $entityScopeDropdown = '
                <div class="space-y-xs">
                    <label for="entity_scope" class="block font-label-md text-label-md text-on-surface">Target Entitas (Scope)<span class="text-error">*</span></label>
                    <select name="entity_scope" id="entity_scope" required
                        class="w-full px-md py-sm bg-surface-container-lowest border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface rounded-t-lg transition-colors outline-none">
                        <option value="all" {{ (old("entity_scope", $product->entity_scope ?? "all") == "all") ? "selected" : "" }}>Semua Entitas</option>
                        <option value="gudang" {{ (old("entity_scope", $product->entity_scope ?? "") == "gudang") ? "selected" : "" }}>Gudang</option>
                        <option value="jihans" {{ (old("entity_scope", $product->entity_scope ?? "") == "jihans") ? "selected" : "" }}>Jihan\'s</option>
                        <option value="hendhys" {{ (old("entity_scope", $product->entity_scope ?? "") == "hendhys") ? "selected" : "" }}>Hendhys</option>
                    </select>
                    @error("entity_scope")
                        <p class="font-body-sm text-body-sm text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>
';
    // Insert it after rack or status.
    $content = str_replace('<label for="status"', $entityScopeDropdown . "\n                <label for=\"status\"", $content);

    file_put_contents($formFile, $content);
    echo "Fixed form.blade.php\n";
}

// --- Fix ProductController.php ---
$ctrlFile = __DIR__ . '/../app/Http/Controllers/Master/ProductController.php';
if (file_exists($ctrlFile)) {
    $content = file_get_contents($ctrlFile);
    // Remove jenis logic
    $content = str_replace("if (\$request->filled('jenis'))\n            \$q->where('jenis', \$request->jenis);", "", $content);
    
    // Add entity_scope validation
    $content = preg_replace('/\'status\' => \'required\|in:active,discontinued\',/', "'status' => 'required|in:active,discontinued',\n            'entity_scope' => 'required|in:all,gudang,jihans,hendhys',", $content);
    
    file_put_contents($ctrlFile, $content);
    echo "Fixed ProductController.php\n";
}

