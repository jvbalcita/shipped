<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Menu } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { dashboard, discover, login, register } from '@/routes';
import { create } from '@/routes/projects';

defineProps<{ title?: string }>();
const page = usePage();
</script>

<template>
    <Head :title="title" />
    <a
        href="#main"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-primary-foreground"
        >Skip to content</a
    >
    <div class="flex min-h-dvh flex-col bg-background text-foreground">
        <Toaster />
        <header
            class="sticky top-0 z-20 border-b border-foreground bg-background"
        >
            <div
                class="mx-auto flex min-h-16 w-full max-w-[90rem] items-stretch justify-between px-4 sm:px-6"
            >
                <Link
                    :href="discover()"
                    class="flex min-w-0 items-center gap-3 border-x border-foreground px-4 focus-visible:outline-3 focus-visible:outline-offset-[-3px] focus-visible:outline-ring"
                >
                    <span
                        class="grid size-7 shrink-0 place-items-center bg-primary font-display text-sm text-primary-foreground"
                        >S</span
                    >
                    <span
                        class="font-display text-xl tracking-[-.07em] uppercase"
                        >Shipped</span
                    >
                </Link>
                <nav
                    class="hidden items-center gap-1 md:flex"
                    aria-label="Primary navigation"
                >
                    <Button as-child variant="ghost" class="h-auto border-y-0"
                        ><Link :href="discover()">Discover</Link></Button
                    >
                    <Button
                        v-if="page.props.auth.user"
                        as-child
                        variant="ghost"
                        class="h-auto border-y-0"
                        ><Link :href="dashboard()">Studio</Link></Button
                    >
                    <Button
                        v-if="!page.props.auth.user"
                        as-child
                        variant="ghost"
                        class="h-auto border-y-0"
                        ><Link :href="login()">Log in</Link></Button
                    >
                    <Button as-child class="my-2"
                        ><Link
                            :href="page.props.auth.user ? create() : register()"
                            >Ship yours <ArrowUpRight class="size-3" /></Link
                    ></Button>
                </nav>
                <Sheet>
                    <SheetTrigger as-child class="md:hidden"
                        ><Button
                            variant="ghost"
                            size="icon"
                            aria-label="Open navigation"
                            ><Menu class="size-5" /></Button
                    ></SheetTrigger>
                    <SheetContent
                        side="right"
                        class="w-[min(20rem,calc(100vw-0.75rem))] border-l-foreground bg-background p-6"
                    >
                        <nav
                            class="mt-12 grid gap-2"
                            aria-label="Mobile navigation"
                        >
                            <Button
                                as-child
                                variant="ghost"
                                class="justify-start"
                                ><Link :href="discover()"
                                    >Discover</Link
                                ></Button
                            >
                            <Button
                                v-if="page.props.auth.user"
                                as-child
                                variant="ghost"
                                class="justify-start"
                                ><Link :href="dashboard()">Studio</Link></Button
                            >
                            <Button
                                v-if="!page.props.auth.user"
                                as-child
                                variant="ghost"
                                class="justify-start"
                                ><Link :href="login()">Log in</Link></Button
                            >
                            <Button as-child class="mt-3"
                                ><Link
                                    :href="
                                        page.props.auth.user
                                            ? create()
                                            : register()
                                    "
                                    >Ship yours</Link
                                ></Button
                            >
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>
        </header>
        <main
            id="main"
            class="flex min-w-0 flex-1 flex-col overflow-x-clip pb-10 sm:pb-16"
        >
            <slot />
        </main>
        <footer class="border-t border-foreground">
            <div
                class="mx-auto grid w-full max-w-[90rem] gap-px bg-foreground sm:grid-cols-3"
            >
                <p class="technical-label bg-background px-4 py-5">
                    Shipped / Community registry
                </p>
                <p class="bg-background px-4 py-5 text-sm">
                    A public home for launches worth sharing.
                </p>
                <p class="bg-background px-4 py-5 text-sm sm:text-right">
                    For Laravel builders.
                </p>
            </div>
        </footer>
    </div>
</template>
