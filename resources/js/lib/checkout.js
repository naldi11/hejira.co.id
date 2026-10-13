// Retain the key across retries and page reloads in this tab. Different carts,
// cashiers, or branches get separate keys; clear only after confirmed success.
export function checkoutKey(scope, payload) {
    const storageKey = `pos_checkout:${scope}`;
    const fingerprint = JSON.stringify(payload);
    const previous = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
    if (previous?.fingerprint === fingerprint) return previous.key;

    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
    const key = `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    sessionStorage.setItem(storageKey, JSON.stringify({ fingerprint, key }));
    return key;
}

export function clearCheckoutKey(scope) {
    sessionStorage.removeItem(`pos_checkout:${scope}`);
}
