import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = 'HEJIRA';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#4f46e5',
        showSpinner: false,
    },
});

// Force browser date picker instead of manual typing for all type="date" inputs
if (typeof document !== 'undefined') {
    document.addEventListener('keydown', function(e) {
        if (e.target && e.target.tagName === 'INPUT' && e.target.type === 'date') {
            // Block all keys except Tab, Escape, Enter, Arrow keys
            const allowedKeys = ['Tab', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter', 'Escape'];
            if (!allowedKeys.includes(e.key)) {
                e.preventDefault();
            }
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.tagName === 'INPUT' && e.target.type === 'date') {
            if (typeof e.target.showPicker === 'function') {
                try {
                    e.target.showPicker();
                } catch (err) {
                    // Ignore errors
                }
            }
        }
    });
}
