<?php

$dirs = ['customers', 'suppliers'];
foreach ($dirs as $dir) {
    $formFile = __DIR__ . '/../resources/views/master/' . $dir . '/form.blade.php';
    if (file_exists($formFile)) {
        // Read file
        $content = file_get_contents($formFile);
        
        // Remove the improperly added dropdown if it exists
        $content = preg_replace('/<div class="space-y-xs">\s*<label for="entity_scope".*?<\/div>\s*<\/div>/s', '', $content);
        
        $modelVar = rtrim($dir, 's'); // customer or supplier
        $entityScopeDropdown = '
                <div class="space-y-xs">
                    <label for="entity_scope" class="block font-label-md text-label-md text-on-surface">Target Entitas (Scope)<span class="text-error">*</span></label>
                    <select name="entity_scope" id="entity_scope" required
                        class="w-full px-md py-sm bg-surface-container-lowest border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface rounded-t-lg transition-colors outline-none">
                        <option value="all" {{ (old("entity_scope", $'.$modelVar.'->entity_scope ?? "all") == "all") ? "selected" : "" }}>Semua Entitas</option>
                        <option value="gudang" {{ (old("entity_scope", $'.$modelVar.'->entity_scope ?? "") == "gudang") ? "selected" : "" }}>Gudang</option>
                        <option value="jihans" {{ (old("entity_scope", $'.$modelVar.'->entity_scope ?? "") == "jihans") ? "selected" : "" }}>Jihan\'s</option>
                        <option value="hendhys" {{ (old("entity_scope", $'.$modelVar.'->entity_scope ?? "") == "hendhys") ? "selected" : "" }}>Hendhys</option>
                    </select>
                    @error("entity_scope")
                        <p class="font-body-sm text-body-sm text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>
';
        // Insert it correctly before `<div class="flex justify-end gap-sm mt-xl">`
        $content = str_replace('<div class="flex justify-end gap-sm mt-xl">', $entityScopeDropdown . "\n            <div class=\"flex justify-end gap-sm mt-xl\">", $content);
        
        file_put_contents($formFile, $content);
        echo "Added entity_scope to $dir form correctly.\n";
    }
}
