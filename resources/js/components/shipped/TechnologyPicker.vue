<script setup lang="ts">
import { X } from '@lucide/vue';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import type {
    TechnologyGroupOption,
    TechnologyOption,
} from '@/types/technology';

const SUGGESTION_LIMIT = 6;
const MATCH_LIMIT = 8;

const props = defineProps<{
    groups: TechnologyGroupOption[];
    modelValue: string[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const searchQuery = ref('');

function isSelected(slug: string): boolean {
    return props.modelValue.includes(slug);
}

function select(slug: string): void {
    if (!isSelected(slug)) {
        emit('update:modelValue', [...props.modelValue, slug]);
    }
}

function deselect(slug: string): void {
    emit(
        'update:modelValue',
        props.modelValue.filter((value) => value !== slug),
    );
}

function toggle(group: TechnologyGroupOption, slug: string): void {
    if (group.multiple) {
        if (isSelected(slug)) {
            deselect(slug);
        } else {
            select(slug);
        }

        return;
    }

    // Version groups hold one choice: clearing the group first means
    // picking a sibling replaces it and re-tapping the pick deselects.
    const groupSlugs = group.technologies.map((technology) => technology.slug);
    const next = props.modelValue.filter(
        (value) => !groupSlugs.includes(value),
    );

    if (!isSelected(slug)) {
        next.push(slug);
    }

    emit('update:modelValue', next);
}

function selectedIn(group: TechnologyGroupOption): TechnologyOption[] {
    return group.technologies.filter((technology) =>
        isSelected(technology.slug),
    );
}

function matchesFor(group: TechnologyGroupOption): TechnologyOption[] {
    const query = searchQuery.value.trim().toLowerCase();

    if (query === '') {
        return [];
    }

    return group.technologies
        .filter((technology) => technology.name.toLowerCase().includes(query))
        .slice(0, MATCH_LIMIT);
}

function suggestionsFor(group: TechnologyGroupOption): TechnologyOption[] {
    return group.suggested
        .map((slug) => group.technologies.find((t) => t.slug === slug))
        .filter((technology): technology is TechnologyOption =>
            Boolean(technology),
        )
        .filter((technology) => !isSelected(technology.slug))
        .slice(0, SUGGESTION_LIMIT);
}

function selectFirstMatch(group: TechnologyGroupOption): void {
    const [first] = matchesFor(group);

    if (first) {
        select(first.slug);
        searchQuery.value = '';
    }
}
</script>

<template>
    <div class="grid gap-5" data-test="technology-picker">
        <fieldset v-for="group in groups" :key="group.group" class="grid gap-2">
            <legend class="technical-label text-muted-foreground">
                {{ group.label }}
                <span v-if="!group.multiple">/ pick one</span>
            </legend>
            <template v-if="group.searchable">
                <Input
                    v-model="searchQuery"
                    type="search"
                    :placeholder="`Search ${group.label.toLowerCase()}…`"
                    autocomplete="off"
                    data-test="technology-search"
                    @keydown.enter.prevent="selectFirstMatch(group)"
                />
                <ul
                    v-if="selectedIn(group).length"
                    class="flex flex-wrap gap-2"
                    data-test="technology-search-selected"
                >
                    <li
                        v-for="technology in selectedIn(group)"
                        :key="technology.slug"
                    >
                        <button
                            type="button"
                            class="technical-label inline-flex items-center gap-1.5 border border-primary bg-primary px-2 py-1 text-primary-foreground"
                            :aria-label="`Remove ${technology.name}`"
                            :data-test="`technology-selected-${technology.slug}`"
                            @click="deselect(technology.slug)"
                        >
                            {{ technology.name }}
                            <X class="size-3" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
                <div v-if="searchQuery.trim()">
                    <ul class="flex flex-wrap gap-2">
                        <li
                            v-for="technology in matchesFor(group)"
                            :key="technology.slug"
                        >
                            <button
                                type="button"
                                class="technical-label border border-foreground px-2 py-1 transition-colors hover:bg-secondary"
                                :class="
                                    isSelected(technology.slug)
                                        ? 'bg-primary text-primary-foreground'
                                        : ''
                                "
                                :data-test="`technology-match-${technology.slug}`"
                                @click="
                                    isSelected(technology.slug)
                                        ? deselect(technology.slug)
                                        : select(technology.slug)
                                "
                            >
                                {{ technology.name }}
                            </button>
                        </li>
                    </ul>
                    <p
                        v-if="!matchesFor(group).length"
                        class="text-xs text-muted-foreground"
                    >
                        No {{ group.label.toLowerCase() }} match "{{
                            searchQuery.trim()
                        }}".
                    </p>
                </div>
                <div v-else class="grid gap-2">
                    <p class="technical-label text-muted-foreground">
                        Commonly used
                    </p>
                    <ul class="flex flex-wrap gap-2">
                        <li
                            v-for="technology in suggestionsFor(group)"
                            :key="technology.slug"
                        >
                            <button
                                type="button"
                                class="technical-label border border-foreground px-2 py-1 transition-colors hover:bg-secondary"
                                :data-test="`technology-suggested-${technology.slug}`"
                                @click="select(technology.slug)"
                            >
                                {{ technology.name }}
                            </button>
                        </li>
                    </ul>
                </div>
            </template>
            <div v-else class="flex flex-wrap gap-2">
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
