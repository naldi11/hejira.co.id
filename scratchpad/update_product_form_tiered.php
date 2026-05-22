<?php

$formFile = __DIR__ . '/../resources/views/master/products/form.blade.php';

if (file_exists($formFile)) {
    $content = file_get_contents($formFile);

    // Alpine component definition for the tiered pricing section
    $tieredPricingSection = '
        {{-- Tiered Pricing Section --}}
        <div class="mt-lg pt-lg border-t border-outline-variant" x-data="{
            tiers: {{ old(\'tiered_prices\') ? json_encode(old(\'tiered_prices\')) : (isset($product) && $product->tieredPrices->count() > 0 ? $product->tieredPrices->map(fn($t) => [\'min_qty\' => $t->min_qty, \'price\' => $t->price])->toJson() : \'[]\') }},
            addTier() {
                this.tiers.push({ min_qty: \'\', price: \'\' });
            },
            removeTier(index) {
                this.tiers.splice(index, 1);
            }
        }">
            <div class="flex justify-between items-center mb-md">
                <div>
                    <h3 class="font-title-md text-title-md text-on-surface">Harga Grosir / Bertingkat</h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Opsional. Atur harga khusus jika pembelian mencapai jumlah tertentu.</p>
                </div>
                <button type="button" @click="addTier" class="inline-flex items-center gap-xs px-sm py-xs bg-primary-container text-on-primary-container rounded-lg font-label-md text-label-md hover:bg-primary hover:text-on-primary transition-colors">
                    <span class="material-symbols-outlined text-[18px]">add</span> Tambah Tingkat
                </button>
            </div>

            <div class="space-y-sm">
                <template x-for="(tier, index) in tiers" :key="index">
                    <div class="flex gap-sm items-end bg-surface-container-lowest p-sm rounded-lg border border-outline-variant">
                        <div class="flex-1">
                            <label class="block font-label-sm text-label-sm text-on-surface mb-xs">Minimal Qty</label>
                            <input type="number" step="0.001" min="1" x-model="tier.min_qty" :name="`tiered_prices[${index}][min_qty]`" required class="w-full px-md py-sm bg-surface-container-lowest border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface rounded-t-lg transition-colors outline-none" placeholder="10">
                        </div>
                        <div class="flex-1">
                            <label class="block font-label-sm text-label-sm text-on-surface mb-xs">Harga Satuan</label>
                            <input type="number" step="0.01" min="0" x-model="tier.price" :name="`tiered_prices[${index}][price]`" required class="w-full px-md py-sm bg-surface-container-lowest border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface rounded-t-lg transition-colors outline-none" placeholder="14000">
                        </div>
                        <button type="button" @click="removeTier(index)" class="p-sm bg-error-container text-on-error-container rounded-lg hover:bg-error hover:text-on-error transition-colors h-[42px] flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </div>
                </template>
                <div x-show="tiers.length === 0" class="text-center py-md text-on-surface-variant font-body-sm bg-surface-container-lowest rounded-lg border border-dashed border-outline-variant">
                    Belum ada harga grosir yang diatur.
                </div>
            </div>
        </div>
';

    // Insert before <div class="flex justify-end gap-sm mt-xl">
    if (strpos($content, 'Harga Grosir / Bertingkat') === false) {
        $content = str_replace('<div class="flex justify-end gap-sm mt-xl">', $tieredPricingSection . "\n            <div class=\"flex justify-end gap-sm mt-xl\">", $content);
        file_put_contents($formFile, $content);
        echo "Fixed products/form.blade.php\n";
    } else {
        echo "Already added.\n";
    }
}
