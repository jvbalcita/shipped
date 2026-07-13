<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-svh bg-background p-4 sm:p-8">
        <header
            class="mx-auto flex w-full max-w-[90rem] items-center justify-between border border-foreground px-4 py-3"
        >
            <Link
                :href="home()"
                class="font-display text-2xl tracking-[-.07em] uppercase"
                >Shipped</Link
            ><Button as-child variant="ghost"
                ><Link :href="home()"
                    >Registry <ArrowUpRight class="size-3" /></Link
            ></Button>
        </header>
        <div
            class="mx-auto mt-6 grid w-full max-w-[90rem] border border-foreground sm:mt-16 lg:grid-cols-[.9fr_1.1fr]"
        >
            <aside
                class="border-b border-foreground bg-secondary p-6 sm:p-10 lg:border-r lg:border-b-0"
            >
                <p class="technical-label text-primary">
                    Creator access / Secure entry
                </p>
                <p
                    class="display-type mt-12 max-w-sm text-[clamp(3rem,6vw,6rem)]"
                >
                    Make work public.
                </p>
            </aside>
            <div class="grid p-6 sm:p-10 lg:place-items-center">
                <div class="w-full max-w-[30rem]">
                    <h1 class="display-type text-4xl">{{ title }}</h1>
                    <p class="mt-5 text-sm leading-7 text-muted-foreground">
                        {{ description }}
                    </p>
                    <div class="mt-10"><slot /></div>
                </div>
            </div>
        </div>
    </div>
</template>
