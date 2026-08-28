<script setup lang="ts">
import { ArrowDown, ArrowUp, Plus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export interface CollectionMemberRow {
    id: number;
    name: string;
    creator_username: string | null;
    is_discoverable?: boolean;
}

const props = defineProps<{
    candidates: { id: number; name: string; creator_username: string | null }[];
    modelValue: CollectionMemberRow[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: CollectionMemberRow[]];
}>();

const search = ref('');

const memberIds = computed(() => new Set(props.modelValue.map((m) => m.id)));

const filteredCandidates = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.candidates.filter(
        (candidate) =>
            !memberIds.value.has(candidate.id) &&
            (query === '' ||
                candidate.name.toLowerCase().includes(query) ||
                (candidate.creator_username ?? '')
                    .toLowerCase()
                    .includes(query)),
    );
});

function add(candidate: {
    id: number;
    name: string;
    creator_username: string | null;
}): void {
    emit('update:modelValue', [...props.modelValue, { ...candidate }]);
}

function remove(id: number): void {
    emit(
        'update:modelValue',
        props.modelValue.filter((member) => member.id !== id),
    );
}

function move(index: number, offset: -1 | 1): void {
    const rows = [...props.modelValue];
    const target = index + offset;

    if (target < 0 || target >= rows.length) {
        return;
    }

    [rows[index], rows[target]] = [rows[target], rows[index]];
    emit('update:modelValue', rows);
}
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-3">
            <p class="technical-label text-primary">Registry index</p>
            <Input
                v-model="search"
                type="search"
                placeholder="Search discoverable projects by name or creator…"
                data-test="collection-member-search"
            />
            <ul
                class="max-h-64 divide-y divide-border overflow-y-auto border border-border"
                data-test="collection-candidates"
            >
                <li
                    v-for="candidate in filteredCandidates"
                    :key="candidate.id"
                    class="flex items-center justify-between gap-3 px-3 py-2 text-sm"
                >
                    <span class="min-w-0 truncate">
                        {{ candidate.name }}
                        <span
                            v-if="candidate.creator_username"
                            class="text-muted-foreground"
                            >/@{{ candidate.creator_username }}</span
                        >
                    </span>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="add(candidate)"
                        :data-test="`collection-add-${candidate.id}`"
                        ><Plus class="size-3"
                    /></Button>
                </li>
                <li
                    v-if="filteredCandidates.length === 0"
                    class="px-3 py-4 text-center text-sm text-muted-foreground"
                >
                    No matching discoverable projects.
                </li>
            </ul>
        </div>
        <div class="grid gap-3">
            <p class="technical-label text-primary">
                Members, in curated order
            </p>
            <ul
                class="divide-y divide-border border border-border"
                data-test="collection-members"
            >
                <li
                    v-for="(member, index) in modelValue"
                    :key="member.id"
                    class="flex items-center gap-3 px-3 py-2 text-sm"
                    :data-test="`collection-member-${member.id}`"
                >
                    <span
                        class="technical-label text-muted-foreground tabular-nums"
                        >{{ index + 1 }}</span
                    >
                    <span class="min-w-0 flex-1 truncate">
                        {{ member.name }}
                        <span
                            v-if="member.is_discoverable === false"
                            class="text-muted-foreground"
                            >(hidden from the public page until
                            rediscoverable)</span
                        >
                    </span>
                    <span class="flex shrink-0 items-center gap-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="index === 0"
                            @click="move(index, -1)"
                            :data-test="`collection-up-${member.id}`"
                            ><ArrowUp class="size-3"
                        /></Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="index === modelValue.length - 1"
                            @click="move(index, 1)"
                            :data-test="`collection-down-${member.id}`"
                            ><ArrowDown class="size-3"
                        /></Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="remove(member.id)"
                            :data-test="`collection-remove-${member.id}`"
                            ><X class="size-3"
                        /></Button>
                    </span>
                </li>
                <li
                    v-if="modelValue.length === 0"
                    class="px-3 py-4 text-center text-sm text-muted-foreground"
                >
                    No members yet. A collection without members stays off the
                    public index.
                </li>
            </ul>
        </div>
    </div>
</template>
