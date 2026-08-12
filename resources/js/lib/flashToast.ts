import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const toastData = flash?.toast as FlashToast | undefined;

        if (toastData) {
            toast[toastData.type](toastData.message);
        }

        // Surface the "filed" moment so the studio can play the stamp.
        if (flash?.filed) {
            window.dispatchEvent(
                new CustomEvent('shipped:filed', { detail: flash.filed }),
            );
        }
    });
}
