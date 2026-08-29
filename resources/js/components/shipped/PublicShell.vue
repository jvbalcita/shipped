<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Flame,
    LogOut,
    Menu,
    Settings,
    ShieldCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import CommandPalette from '@/components/shipped/CommandPalette.vue';
import NotificationBell from '@/components/shipped/NotificationBell.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';
import { dashboard, discover, feed, login, logout, register } from '@/routes';
import { index as collectionsIndex } from '@/routes/collections';
import { edit as profileEdit } from '@/routes/profile';
import { create } from '@/routes/projects';
import { index as reportsIndex } from '@/routes/reports';
import { edit as securityEdit } from '@/routes/security';

const props = defineProps<{ title?: string }>();
const page = usePage();
const { getInitials } = useInitials();

const documentTitle = computed(() => {
    const seoTitle = (page.props as { seo?: { title?: string } }).seo?.title;

    return seoTitle ?? props.title;
});

const handleLogout = (): void => {
    router.flushAll();
};
</script>

<template>
    <Head :title="documentTitle" />
    <a
        href="#main"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-primary-foreground"
        >Skip to content</a
    >
    <div class="flex min-h-dvh flex-col bg-background text-foreground">
        <Toaster />
        <CommandPalette />
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
                    <Button as-child variant="ghost" class="h-auto border-y-0"
                        ><Link :href="collectionsIndex()"
                            >Collections</Link
                        ></Button
                    >
                    <Button
                        v-if="page.props.auth.user"
                        as-child
                        variant="ghost"
                        class="h-auto border-y-0"
                        ><Link :href="feed()">Following</Link></Button
                    >
                    <Button
                        v-if="page.props.auth.user"
                        as-child
                        variant="ghost"
                        class="h-auto border-y-0"
                        ><Link :href="dashboard()">Studio</Link></Button
                    >
                    <Button
                        v-if="page.props.can?.curate"
                        as-child
                        variant="ghost"
                        class="h-auto border-y-0"
                        data-test="nav-curate"
                        ><Link :href="reportsIndex()">Curate</Link></Button
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
                    <NotificationBell
                        v-if="page.props.auth.user"
                        class="my-2"
                    />
                    <DropdownMenu v-if="page.props.auth.user" :modal="false">
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                class="my-2 size-9 p-0"
                                aria-label="Open account menu"
                            >
                                <Avatar
                                    class="size-9 rounded-none border border-foreground"
                                >
                                    <AvatarImage
                                        v-if="page.props.auth.user.avatar"
                                        :src="page.props.auth.user.avatar"
                                        :alt="page.props.auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-none bg-primary font-mono text-xs font-semibold text-primary-foreground"
                                    >
                                        {{
                                            getInitials(
                                                page.props.auth.user.name,
                                            )
                                        }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-64 border-foreground bg-background"
                        >
                            <UserMenuContent :user="page.props.auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </nav>
                <Sheet>
                    <SheetTrigger as-child class="md:hidden"
                        ><Button
                            variant="ghost"
                            size="icon"
                            class="self-center"
                            aria-label="Open navigation"
                            ><Menu class="size-5" /></Button
                    ></SheetTrigger>
                    <SheetContent
                        side="right"
                        class="w-[min(20rem,calc(100vw-0.75rem))] border-l-foreground bg-background p-6"
                    >
                        <div
                            v-if="page.props.auth.user"
                            class="mt-12 border border-foreground p-4"
                        >
                            <p class="technical-label text-primary">
                                Signed in as
                            </p>
                            <div class="mt-4 flex items-center gap-3">
                                <Avatar
                                    class="size-10 rounded-none border border-foreground"
                                >
                                    <AvatarImage
                                        v-if="page.props.auth.user.avatar"
                                        :src="page.props.auth.user.avatar"
                                        :alt="page.props.auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-none bg-primary font-mono text-sm font-semibold text-primary-foreground"
                                    >
                                        {{
                                            getInitials(
                                                page.props.auth.user.name,
                                            )
                                        }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">
                                        {{ page.props.auth.user.name }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ page.props.auth.user.email }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <nav
                            :class="[
                                'grid gap-2',
                                page.props.auth.user ? 'mt-6' : 'mt-12',
                            ]"
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
                                as-child
                                variant="ghost"
                                class="justify-start"
                                ><Link :href="collectionsIndex()"
                                    >Collections</Link
                                ></Button
                            >
                            <Button
                                v-if="page.props.auth.user"
                                as-child
                                variant="ghost"
                                class="justify-start"
                                ><Link :href="feed()">Following</Link></Button
                            >
                            <div
                                v-if="page.props.auth.user"
                                class="flex items-center justify-between"
                            >
                                <span class="text-sm text-muted-foreground"
                                    >Notifications</span
                                >
                                <NotificationBell />
                            </div>
                            <Button
                                v-if="page.props.auth.user"
                                as-child
                                variant="ghost"
                                class="justify-start"
                                ><Link :href="dashboard()">Studio</Link></Button
                            >
                            <Button
                                v-if="page.props.can?.curate"
                                as-child
                                variant="ghost"
                                class="justify-start"
                                data-test="nav-curate"
                                ><Link :href="reportsIndex()"
                                    >Curate</Link
                                ></Button
                            >
                            <template v-if="page.props.auth.user">
                                <Button
                                    as-child
                                    variant="ghost"
                                    class="justify-start"
                                    ><Link :href="profileEdit()"
                                        ><Settings class="size-4" />Profile &
                                        settings</Link
                                    ></Button
                                ><Button
                                    as-child
                                    variant="ghost"
                                    class="justify-start"
                                    ><Link :href="securityEdit()"
                                        ><ShieldCheck
                                            class="size-4"
                                        />Security</Link
                                    ></Button
                                ><Button
                                    as-child
                                    variant="outline"
                                    class="mt-4 justify-start"
                                    ><Link
                                        :href="logout.url()"
                                        method="post"
                                        as="button"
                                        @click="handleLogout"
                                        ><LogOut class="size-4" />Log out</Link
                                    ></Button
                                >
                            </template>
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
                class="relative mx-auto grid w-full max-w-[90rem] gap-px bg-foreground sm:grid-cols-3"
            >
                <span
                    aria-hidden="true"
                    class="technical-label pointer-events-none absolute -top-2 left-1 hidden bg-background px-1 text-muted-foreground sm:block"
                    >+</span
                >
                <span
                    aria-hidden="true"
                    class="technical-label pointer-events-none absolute -top-2 right-1 hidden bg-background px-1 text-muted-foreground sm:block"
                    >+</span
                >
                <p class="technical-label bg-background px-4 py-5">
                    Shipped / Community registry
                </p>
                <p class="bg-background px-4 py-5 text-sm">
                    A public home for launches worth sharing.
                </p>
                <p
                    class="inline-flex items-center gap-2 bg-background px-4 py-5 text-sm sm:justify-end"
                >
                    <Flame class="size-4 text-[#ff2d20]" aria-hidden="true" />
                    For Laravel Builders
                </p>
            </div>
        </footer>
    </div>
</template>
