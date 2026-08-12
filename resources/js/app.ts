import { createInertiaApp } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import ShippedSettingsLayout from '@/layouts/ShippedSettingsLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Shipped is intentionally paper-light. Remove a persisted starter-kit theme
// class before Vue mounts so old localStorage preferences cannot darken controls.
document.documentElement.classList.remove('dark');

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
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
initializeFlashToast();
