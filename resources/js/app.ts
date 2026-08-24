import { createInertiaApp } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import ShippedSettingsLayout from '@/layouts/ShippedSettingsLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Shipped is intentionally paper-light. Remove a persisted starter-kit theme
// class before Vue mounts so old localStorage preferences cannot darken controls.
if (!import.meta.env.SSR) {
    document.documentElement.classList.remove('dark');
}

createInertiaApp({
    title: (title) => {
        if (!title) {
            return appName;
        }

        if (
            title === appName ||
            title.startsWith(`${appName} — `) ||
            title.endsWith(` - ${appName}`) ||
            title.endsWith(` — ${appName}`)
        ) {
            return title;
        }

        return `${title} - ${appName}`;
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return ShippedSettingsLayout;
            case name.startsWith('Projects/'):
            case name.startsWith('Discover/'):
            case name.startsWith('Creators/'):
            case name === 'Welcome':
                return null;
            default:
                return null;
        }
    },
    progress: {
        color: '#e61919',
    },
});

// This will listen for flash toast data from the server...
if (!import.meta.env.SSR) {
    initializeFlashToast();
}
