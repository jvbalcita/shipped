<script setup lang="ts">
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';

export interface GitHubRepository {
    name: string;
    url: string;
}

const props = defineProps<{ repos: GitHubRepository[] }>();
const url = defineModel<string>({ required: true });

// A pasted URL can point anywhere (private repo, another host), so the
// raw input stays available; it starts active whenever the current URL
// isn't one of the pickable public repositories.
const isKnownRepo = computed(() =>
    props.repos.some((repo) => repo.url === url.value),
);
const pasteMode = ref(url.value !== '' && !isKnownRepo.value);
const selectedRepo = computed(
    () => props.repos.find((repo) => repo.url === url.value)?.name,
);
</script>

<template>
    <div data-test="github-repo-picker">
        <div v-if="!pasteMode" class="grid gap-2">
            <Combobox v-model="url" open-on-click>
                <ComboboxAnchor as-child>
                    <ComboboxTrigger
                        type="button"
                        class="flex h-10 w-full items-center justify-between gap-2 rounded-none border border-foreground bg-transparent px-3 text-sm"
                    >
                        <span
                            class="truncate"
                            :class="{ 'text-muted-foreground': !selectedRepo }"
                        >
                            {{ selectedRepo ?? (url || 'Pick a repository') }}
                        </span>
                        <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
                    </ComboboxTrigger>
                </ComboboxAnchor>
                <ComboboxList
                    class="w-[var(--reka-combobox-trigger-width)] rounded-none border-foreground"
                    align="start"
                >
                    <ComboboxInput placeholder="Search repositories…" />
                    <ComboboxViewport>
                        <ComboboxEmpty class="py-4 text-xs">
                            No matching repository.
                        </ComboboxEmpty>
                        <ComboboxItem
                            v-for="repo in repos"
                            :key="repo.url"
                            :value="repo.url"
                            class="py-2"
                        >
                            {{ repo.name }}
                            <ComboboxItemIndicator>
                                <Check class="size-4" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxViewport>
                </ComboboxList>
            </Combobox>
            <button
                type="button"
                class="w-fit text-left text-xs underline underline-offset-2 hover:bg-secondary"
                data-test="github-url-paste-toggle"
                @click="pasteMode = true"
            >
                Paste a URL instead
            </button>
        </div>
        <div v-else class="grid gap-2">
            <Input
                id="github_url"
                v-model="url"
                type="url"
                placeholder="https://github.com/you/project"
                data-test="github-url-input"
            />
            <button
                v-if="repos.length"
                type="button"
                class="w-fit text-left text-xs underline underline-offset-2 hover:bg-secondary"
                @click="pasteMode = false"
            >
                Pick from your repositories
            </button>
        </div>
    </div>
</template>
