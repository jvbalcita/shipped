<script setup lang="ts">
import { ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { computed, onMounted, onUnmounted } from 'vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';

interface Screenshot {
    id: number;
    url: string;
    caption: string | null;
}

const props = defineProps<{ screenshots: Screenshot[] }>();
const activeIndex = defineModel<number | null>({ required: true });

const isOpen = computed(() => activeIndex.value !== null);
const current = computed(() =>
    activeIndex.value === null ? null : props.screenshots[activeIndex.value],
);
const hasPrev = computed(() => (activeIndex.value ?? 0) > 0);
const hasNext = computed(
    () => (activeIndex.value ?? 0) < props.screenshots.length - 1,
);

function step(direction: 1 | -1): void {
    if (activeIndex.value === null) {
        return;
    }

    const next = activeIndex.value + direction;

    if (next >= 0 && next < props.screenshots.length) {
        activeIndex.value = next;
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (activeIndex.value === null) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        step(-1);
    }

    if (event.key === 'ArrowRight') {
        step(1);
    }

    // Close on Escape ourselves: relying on the dialog's own escape
    // handling has proven unreliable in this composition.
    if (event.key === 'Escape') {
        activeIndex.value = null;
    }
}

// The content element spans the whole viewport, so clicks on the dark
// area land inside it — close on those instead of the image/controls.
function onBackdropClick(event: MouseEvent): void {
    const target = event.target as HTMLElement;

    if (target.closest('button, a, img')) {
        return;
    }

    activeIndex.value = null;
}

// Capture phase: the dialog content may not contain focus, and other
// handlers may stop bubbling keydowns, so catch them on the way down.
onMounted(() => window.addEventListener('keydown', onKeydown, true));
onUnmounted(() => window.removeEventListener('keydown', onKeydown, true));

function figureNumber(index: number): string {
    return `Fig. ${String(index + 1).padStart(2, '0')}`;
}
</script>

<template>
    <DialogRoot
        :open="isOpen"
        @update:open="(value: boolean) => {
            if (!value) {
                activeIndex = null;
            }
        }"
    >
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-foreground/90" />
            <DialogContent
                class="fixed inset-0 z-50 flex flex-col items-center justify-center p-6 outline-none"
                data-test="screenshot-lightbox"
                @click="onBackdropClick"
            >
                <DialogTitle class="sr-only">Screenshot preview</DialogTitle>

                <img
                    v-if="current"
                    :src="current.url"
                    :alt="current.caption ?? ''"
                    class="max-h-[78vh] max-w-[88vw] border border-background object-contain"
                />

                <DialogClose
                    class="absolute top-4 right-4 grid size-11 place-items-center border border-foreground bg-background hover:bg-secondary"
                    aria-label="Close preview"
                >
                    <X class="size-5" />
                </DialogClose>

                <button
                    v-if="hasPrev"
                    type="button"
                    class="absolute top-1/2 left-4 grid size-11 -translate-y-1/2 place-items-center border border-foreground bg-background hover:bg-secondary"
                    aria-label="Previous screenshot"
                    data-test="screenshot-prev"
                    @click="step(-1)"
                >
                    <ChevronLeft class="size-6" />
                </button>
                <button
                    v-if="hasNext"
                    type="button"
                    class="absolute top-1/2 right-4 grid size-11 -translate-y-1/2 place-items-center border border-foreground bg-background hover:bg-secondary"
                    aria-label="Next screenshot"
                    data-test="screenshot-next"
                    @click="step(1)"
                >
                    <ChevronRight class="size-6" />
                </button>

                <DialogDescription
                    v-if="current"
                    class="absolute inset-x-0 bottom-6 grid justify-items-center gap-1 px-6 text-center"
                >
                    <span class="technical-label text-background">
                        {{ figureNumber(activeIndex ?? 0) }} ·
                        {{ (activeIndex ?? 0) + 1 }} / {{ screenshots.length }}
                    </span>
                    <span
                        v-if="current.caption"
                        class="max-w-2xl text-sm text-background/80"
                    >
                        {{ current.caption }}
                    </span>
                </DialogDescription>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
