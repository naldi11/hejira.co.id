import test from 'node:test';
import assert from 'node:assert/strict';
import { recalculateJihansCart } from '../../resources/js/lib/jihansPricing.js';
const productById = new Map([
    [1, { id: 1, name: 'Daging', category_name: 'Daging', selling_price: 10000, tiered_prices: [{ min_qty: 50, price: 8000 }, { min_qty: 300, price: 6000 }] }],
    [95, { id: 95, name: 'Tortilla', category_name: 'Tortilla', selling_price: 2000, tiered_prices: [{ min_qty: 50, price: 1500 }, { min_qty: 300, price: 1000 }] }],
]);
test('combined tiers apply to selected products from different catalogue pages', () => {
    const cart = [{ product_id: 1, quantity: 25 }, { product_id: 95, quantity: 25 }];
    assert.deepEqual(recalculateJihansCart(cart, 'Pelanggan Retail', productById).map(i => i.price), [8000, 1500]);
    assert.equal(recalculateJihansCart([{ product_id: 95, quantity: 25 }], 'Pelanggan Retail', productById)[0].price, 2000);
});
test('customer tiers and custom price survive catalogue changes', () => {
    const cart = [{ product_id: 95, quantity: 1 }];
    assert.equal(recalculateJihansCart(cart, 'Reseller', productById)[0].price, 1500);
    assert.equal(recalculateJihansCart(cart, 'Agen', productById)[0].price, 1000);
    assert.equal(recalculateJihansCart([{ ...cart[0], price: 777, is_custom_price: true }], 'Agen', productById)[0].price, 777);
});
