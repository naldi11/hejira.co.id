export function recalculateJihansCart(currentCart, type, productById) {
        const combinedQty = currentCart.reduce((sum, item) => {
            const product = productById.get(item.product_id);
            if (product) {
                const catName = (product.category_name || '').toLowerCase();
                const prodName = (product.name || '').toLowerCase();
                const isMeatOrTortilla = catName === 'daging' || catName === 'tortilla' ||
                                         prodName.includes('daging') || prodName.includes('tortilla');
                if (isMeatOrTortilla) {
                    return sum + item.quantity;
                }
            }
            return sum;
        }, 0);

        return currentCart.map(item => {
            const product = productById.get(item.product_id);
            if (!product) return item;

            let effectiveQty = item.quantity;
            if (type === 'Reseller') {
                effectiveQty = Math.max(effectiveQty, 50);
            } else if (type === 'Agen') {
                effectiveQty = Math.max(effectiveQty, 300);
            }

            const catName = (product.category_name || '').toLowerCase();
            const prodName = (product.name || '').toLowerCase();
            const isMeatOrTortilla = catName === 'daging' || catName === 'tortilla' ||
                                     prodName.includes('daging') || prodName.includes('tortilla');

            if (isMeatOrTortilla && combinedQty >= 50) {
                effectiveQty = Math.max(effectiveQty, combinedQty);
            }

            let price = product.selling_price || 0;
            const sortedTiers = [...(product.tiered_prices ?? [])].sort((a, b) => b.min_qty - a.min_qty);
            for (const tier of sortedTiers) {
                if (effectiveQty >= tier.min_qty) {
                    price = tier.price;
                    break;
                }
            }

            if (item.is_custom_price) {
                price = item.price;
            }

            return { ...item, price };
        });
    }
