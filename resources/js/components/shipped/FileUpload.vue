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
    }>(),
    { modelValue: null, error: '', existingUrl: null },
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
            class="grid min-h-52 place-items-center border border-dashed border-foreground bg-card p-4 text-center"
            :class="{ 'border-[3px] border-primary': isDragging }"
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
                alt="Selected project cover"
                class="max-h-64 w-full object-cover"
            />
            <div v-else class="grid max-w-sm place-items-center gap-3">
                <ImagePlus class="size-8" aria-hidden="true" />
                <p class="technical-label">Drop a cover plate here</p>
                <p class="text-sm text-muted-foreground">
                    PNG, JPG, or WebP. Maximum 4 MB.
                </p>
            </div>
        </div>
        <Progress
            v-if="modelValue"
            :model-value="100"
            aria-label="Cover selected"
        />
        <div class="flex flex-wrap gap-2">
            <Button type="button" variant="outline" @click="input?.$el.click()"
                ><Upload class="size-4" />{{
                    previewUrl ? 'Replace cover' : 'Choose cover'
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
            ><AlertTitle>Cover rejected</AlertTitle
            ><AlertDescription>{{ error }}</AlertDescription></Alert
        >
    </div>
</template>
