<script setup lang="ts">
import { Copy } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';

const props = defineProps<{ markdown: string }>();

const input = ref<HTMLInputElement | null>(null);

async function copyMarkdown(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.markdown);
        toast.success('Badge markdown copied.');
    } catch {
        // Clipboard API unavailable (permissions, non-secure context):
        // fall back to selecting the snippet for manual copy.
        input.value?.focus();
        input.value?.select();
        toast('Select and copy the badge markdown.');
    }
}
</script>

<template>
    <section class="border-t border-foreground">
        <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
            <p class="technical-label text-primary">README badge</p>
            <div class="mt-8 flex flex-col gap-3 sm:mt-0">
                <p class="text-sm leading-6 text-muted-foreground">
                    Drop the live-on-Cloud badge into your README. It links back
                    to your Shipped launch page and updates as your verification
                    status changes. The probe means the Cloud URL was up, not
                    Cloud account ownership.
                </p>
                <div class="flex gap-2">
                    <input
                        ref="input"
                        :value="markdown"
                        type="text"
                        readonly
                        class="h-9 min-w-0 flex-1 rounded-none border border-foreground bg-background px-3 font-mono text-xs text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                        data-test="badge-markdown"
                        @click="input?.select()"
                    />
                    <Button
                        variant="outline"
                        class="shrink-0"
                        data-test="copy-badge"
                        @click="copyMarkdown"
                    >
                        <Copy class="size-4" aria-hidden="true" />
                        Copy badge
                    </Button>
                </div>
            </div>
        </div>
    </section>
</template>
