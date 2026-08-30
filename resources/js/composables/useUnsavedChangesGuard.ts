import type { GlobalEvent } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import type { Ref } from 'vue';

// Warn before leaving a page with unsaved form changes. Inertia form
// submissions are POST/PATCH visits and never prompt; only GET navigations
// (and browser unload) are guarded, so prefetches and saves stay silent.
export function useUnsavedChangesGuard(isDirty: Ref<boolean>): void {
    const MESSAGE = 'You have unsaved changes on this page. Leave anyway?';

    function beforeVisit(event: GlobalEvent<'before'>): void {
        if (!isDirty.value) {
            return;
        }

        const visit = event.detail?.visit;

        if (visit.method !== 'get' || visit.prefetch) {
            return;
        }

        if (!window.confirm(MESSAGE)) {
            event.preventDefault();
        }
    }

    function beforeUnload(event: BeforeUnloadEvent): void {
        if (!isDirty.value) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    }

    let removeRouterListener: (() => void) | undefined;

    onMounted(() => {
        removeRouterListener = router.on('before', beforeVisit);
        window.addEventListener('beforeunload', beforeUnload);
    });

    onUnmounted(() => {
        removeRouterListener?.();
        window.removeEventListener('beforeunload', beforeUnload);
    });
}
