<script setup lang="ts">
import { ImagePlus, Trash2, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';

const props = withDefaults(
    defineProps<{
        modelValue?: File | null;
        error?: string;
        existingUrl?: string | null;
        /** "cover" renders a wide plate; "logo"/"avatar" render a square slot. */
        kind?: 'cover' | 'logo' | 'avatar';
    }>(),
    { modelValue: null, error: '', existingUrl: null, kind: 'cover' },
);

const emit = defineEmits<{
    'update:modelValue': [value: File | null];
    'remove-existing': [];
}>();
const input = ref<InstanceType<typeof Input> | null>(null);
const isDragging = ref(false);
const showExisting = ref(!!props.existingUrl);
const previewUrl = computed(() =>
    props.modelValue
        ? URL.createObjectURL(props.modelValue)
        : showExisting.value
            ? props.existingUrl
            : null,
);

const isSquare = computed(() => props.kind !== 'cover');
const noun = computed(() =>
    props.kind === 'logo' ? 'logo' : props.kind === 'avatar' ? 'avatar' : 'cover',
);
const hint = computed(() => {
    if (props.kind === 'logo') {
        return 'Square PNG, JPG, or WebP. At least 256×256, up to 6 MB.';
    }

    if (props.kind === 'avatar') {
        return 'Square PNG, JPG, or WebP. Maximum 3 MB.';
    }

    return 'PNG, JPG, or WebP. Maximum 4 MB.';
});

function selectFile(file?: File): void {
    if (file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        emit('update:modelValue', file);
    }
}

function removeFile(): void {
    // Deselecting a freshly picked file restores the saved cover (if any);
    // only when there is no new file do we signal removal of the existing one.
    if (props.modelValue) {
        emit('update:modelValue', null);

        return;
    }

    if (showExisting.value) {
        showExisting.value = false;
        emit('remove-existing');
    }
}
</script>

<template>
    <div class="grid gap-3">
        <Input
            ref="input"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            class="sr-only"
            tabindex="-1"
            @change="selectFile(($event.target as HTMLInputElement).files?.[0])"
        />
        <div
            class="grid place-items-center border border-dashed border-foreground bg-card p-4 text-center"
            :class="[
                isSquare ? 'size-36' : 'min-h-52 w-full',
                { 'border-[3px] border-primary': isDragging },
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="
                isDragging = false;
                selectFile($event.dataTransfer?.files[0]);
            "
        >
            <img
                v-if="previewUrl"
                :src="previewUrl"
                :alt="`Selected ${noun}`"
                class="w-full object-contain"
                :class="isSquare ? 'size-full p-2' : 'max-h-64'"
            />
            <div
                v-else
                class="grid max-w-sm place-items-center gap-3"
                :class="{ 'px-2': isSquare }"
            >
                <ImagePlus class="size-8" aria-hidden="true" />
                <p class="technical-label">
                    Drop
                    {{
                        isSquare
                            ? `a square ${noun}`
                            : 'a cover plate'
                    }}
                    here
                </p>
                <p v-if="!isSquare" class="text-sm text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
        </div>
        <p v-if="isSquare" class="text-xs text-muted-foreground">
            {{ hint }}
        </p>
        <Progress
            v-if="modelValue"
            :model-value="100"
            :aria-label="`${noun} selected`"
        />
        <div class="flex flex-wrap gap-2">
            <Button type="button" variant="outline" @click="input?.$el.click()"
                ><Upload class="size-4" />{{
                    previewUrl
                        ? `Replace ${noun}`
                        : `Choose ${noun}`
                }}</Button
            >
            <Button
                v-if="previewUrl"
                type="button"
                variant="ghost"
                @click="removeFile"
                ><Trash2 class="size-4" /> Remove</Button
            >
        </div>
        <Alert v-if="error" variant="destructive"
            ><AlertTitle>{{ noun }} rejected</AlertTitle
            ><AlertDescription>{{ error }}</AlertDescription></Alert
        >
    </div>
</template>
