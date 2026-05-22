<?php

// Fix Jihans POS View
$jihansView = __DIR__ . '/../resources/views/jihans/pos/index.blade.php';
if (file_exists($jihansView)) {
    $content = file_get_contents($jihansView);

    $helperLogic = "
                getTieredPrice(productId, quantity) {
                    const product = this.products.find(p => p.id === productId);
                    if (!product) return 0;
                    let price = parseFloat(product.selling_price) || 0;
                    if (product.tiered_prices && product.tiered_prices.length > 0) {
                        // tiered_prices sudah di-order DESC (tertinggi ke terendah)
                        for (let i = 0; i < product.tiered_prices.length; i++) {
                            if (quantity >= parseFloat(product.tiered_prices[i].min_qty)) {
                                price = parseFloat(product.tiered_prices[i].price);
                                break; // ketemu yang tertinggi yang memenuhi
                            }
                        }
                    }
                    return price;
                },

                validateQuantity(index) {
                    const item = this.cart[index];
                    // Paksa selalu bilangan bulat, minimal 1
                    item.quantity = Math.max(1, Math.round(parseFloat(item.quantity) || 1));

                    // Tiered Pricing Auto Adjust
                    item.price = this.getTieredPrice(item.product_id, item.quantity);

                    if (item.discount > (item.price * item.quantity)) {
                        item.discount = 0;
                    }

                    item.total = (item.quantity * item.price) - item.discount;
                    this.recalculateTotals();
                },";

    // Replace validateQuantity block and insert the helper
    $content = preg_replace("/validateQuantity\(index\) \{.*?(?=\n\s*removeFromCart\(index\))/s", $helperLogic . "\n", $content);
    
    file_put_contents($jihansView, $content);
    echo "Fixed Jihans POS View\n";
}

// Fix Hendhys POS View
$hendhysView = __DIR__ . '/../resources/views/hendhys/pos/index.blade.php';
if (file_exists($hendhysView)) {
    $content = file_get_contents($hendhysView);

    $helperLogic = "
                getTieredPrice(productId, quantity) {
                    const product = this.products.find(p => p.id === productId);
                    if (!product) return 0;
                    let price = parseFloat(product.selling_price) || 0;
                    if (product.tiered_prices && product.tiered_prices.length > 0) {
                        // tiered_prices sudah di-order DESC (tertinggi ke terendah)
                        for (let i = 0; i < product.tiered_prices.length; i++) {
                            if (quantity >= parseFloat(product.tiered_prices[i].min_qty)) {
                                price = parseFloat(product.tiered_prices[i].price);
                                break; // ketemu yang tertinggi yang memenuhi
                            }
                        }
                    }
                    return price;
                },

                validateQuantity(index) {
                    const item = this.cart[index];
                    // Paksa selalu bilangan bulat, minimal 1
                    item.quantity = Math.max(1, Math.round(parseFloat(item.quantity) || 1));

                    // Tiered Pricing Auto Adjust
                    item.price = this.getTieredPrice(item.product_id, item.quantity);

                    if (item.discount > (item.price * item.quantity)) {
                        item.discount = 0;
                    }

                    item.total = (item.quantity * item.price) - item.discount;
                    this.recalculateTotals();
                },";

    // Replace validateQuantity block and insert the helper
    $content = preg_replace("/validateQuantity\(index\) \{.*?(?=\n\s*removeFromCart\(index\))/s", $helperLogic . "\n", $content);
    
    file_put_contents($hendhysView, $content);
    echo "Fixed Hendhys POS View\n";
}
