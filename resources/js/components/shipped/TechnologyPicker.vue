<script setup lang="ts">
import type { TechnologyGroupOption } from '@/types/technology';

const props = defineProps<{
    groups: TechnologyGroupOption[];
    modelValue: string[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

function toggle(group: TechnologyGroupOption, slug: string): void {
    const isSelected = props.modelValue.includes(slug);

    if (group.multiple) {
        emit(
            'update:modelValue',
            isSelected
                ? props.modelValue.filter((value) => value !== slug)
                : [...props.modelValue, slug],
        );

        return;
    }

    // Version groups hold one choice: clearing the group first means
    // picking a sibling replaces it and re-tapping the pick deselects.
    const groupSlugs = group.technologies.map((technology) => technology.slug);
    const next = props.modelValue.filter(
        (value) => !groupSlugs.includes(value),
    );

    if (!isSelected) {
        next.push(slug);
    }

    emit('update:modelValue', next);
}
</script>

<template>
    <div class="grid gap-5" data-test="technology-picker">
        <fieldset v-for="group in groups" :key="group.group" class="grid gap-2">
            <legend class="technical-label text-muted-foreground">
                {{ group.label }}
                <span v-if="!group.multiple">/ pick one</span>
            </legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="technology in group.technologies"
                    :key="technology.slug"
                    type="button"
                    class="technical-label border border-foreground px-2 py-1 transition-colors hover:bg-secondary"
                    :class="
                        modelValue.includes(technology.slug)
                            ? 'bg-primary text-primary-foreground'
                            : ''
                    "
                    :aria-pressed="modelValue.includes(technology.slug)"
                    :data-test="`technology-option-${technology.slug}`"
                    @click="toggle(group, technology.slug)"
                >
                    {{ technology.name }}
                </button>
            </div>
        </fieldset>
    </div>
</template>
