<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import { usePage } from '@inertiajs/vue3';
import { create } from '@/routes/projects';
import { dashboard, discover, register } from '@/routes';

const page = usePage();
const open = ref(false);

function onKeyDown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = !open.value;
    }
}

onMounted(() => window.addEventListener('keydown', onKeyDown));
onUnmounted(() => window.removeEventListener('keydown', onKeyDown));

const authed = computed(() => !!page.props.auth.user);

function go(url: string): void {
    open.value = false;
    router.visit(url);
}
</script>

<template>
    <CommandDialog
        v-model:open="open"
        title="Shipped command"
        description="Jump to a destination or action."
    >
        <CommandInput placeholder="Type a command or search…" />
        <CommandList>
            <CommandEmpty>No results.</CommandEmpty>
            <CommandGroup heading="Registry">
                <CommandItem
                    :value="['Browse launches', 'discover']"
                    @select="go(discover().url)"
                >
                    Browse launches
                </CommandItem>
                <CommandItem
                    :value="['Homepage', 'home']"
                    @select="go('/')"
                >
                    Homepage
                </CommandItem>
            </CommandGroup>
            <CommandSeparator />
            <CommandGroup heading="Studio">
                <CommandItem
                    v-if="authed"
                    :value="['Open studio', 'dashboard']"
                    @select="go(dashboard().url)"
                >
                    Open studio
                </CommandItem>
                <CommandItem
                    :value="[authed ? 'New launch' : 'Create account', 'ship yours']"
                    @select="go(authed ? create().url : register().url)"
                >
                    {{ authed ? 'New launch' : 'Create account' }}
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>
