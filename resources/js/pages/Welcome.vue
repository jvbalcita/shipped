<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import { Button } from '@/components/ui/button';
import { discover, register } from '@/routes';
import { create } from '@/routes/projects';

const page = usePage();
</script>

<template>
    <PublicShell title="A public home for launches">
        <section class="page-enter border-b border-foreground">
            <div
                class="mx-auto grid w-full max-w-[90rem] border-x border-foreground lg:grid-cols-[1.1fr_.9fr]"
            >
                <div class="flex min-h-[34rem] flex-col p-6 sm:p-10 lg:p-14">
                    <p class="technical-label text-primary">
                        A public registry for independent launches
                    </p>
                    <h1
                        class="display-type mt-auto max-w-4xl text-[clamp(4.5rem,10vw,9rem)]"
                    >
                        Make it public.
                    </h1>
                    <div class="mt-8 max-w-xl">
                        <p class="text-base leading-7 sm:text-lg">
                            Shipped gives a finished product a permanent home:
                            the work, its release story, and the person who made
                            it.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <Button as-child size="lg"
                                ><Link
                                    :href="
                                        page.props.auth.user
                                            ? create()
                                            : register()
                                    "
                                    >Ship yours
                                    <ArrowUpRight
                                        class="size-4" /></Link></Button
                            ><Button as-child size="lg" variant="outline"
                                ><Link :href="discover()"
                                    >Browse launches</Link
                                ></Button
                            >
                        </div>
                    </div>
                </div>
                <figure
                    class="grid min-h-[34rem] grid-rows-[1fr_auto] border-t border-foreground bg-secondary lg:border-t-0 lg:border-l"
                >
                    <img
                        src="/images/shipped/hero-worktable.png"
                        alt="A worktable prepared for a product launch"
                        width="1792"
                        height="1024"
                        fetchpriority="high"
                        class="media-reveal size-full min-h-0 object-cover contrast-125 grayscale"
                    />
                    <figcaption
                        class="grid border-t border-foreground bg-background p-5 sm:grid-cols-[10rem_1fr]"
                    >
                        <span class="technical-label text-primary"
                            >The record</span
                        ><span class="mt-4 text-sm leading-6 sm:mt-0"
                            >A launch page that people can revisit after the
                            moment has passed.</span
                        >
                    </figcaption>
                </figure>
            </div>
        </section>
        <section
            class="mx-auto w-full max-w-[90rem] border-x border-b border-foreground"
        >
            <div
                class="grid min-w-0 border-b border-foreground p-6 sm:grid-cols-[12rem_minmax(0,1fr)] sm:p-10"
            >
                <p class="technical-label text-primary">What happens here</p>
                <h2
                    class="display-type mt-10 max-w-4xl text-[clamp(2.5rem,6vw,6rem)] break-words sm:mt-0"
                >
                    A launch deserves a record, not a disappearing link.
                </h2>
            </div>
            <dl
                class="grid divide-y divide-foreground sm:grid-cols-3 sm:divide-x sm:divide-y-0"
            >
                <div class="p-6 sm:p-8">
                    <dt class="technical-label text-primary">Declare</dt>
                    <dd class="mt-10 text-sm leading-6">
                        Set the product’s identity, category, cover, and place
                        to try it.
                    </dd>
                </div>
                <div class="p-6 sm:p-8">
                    <dt class="technical-label text-primary">Release</dt>
                    <dd class="mt-10 text-sm leading-6">
                        Write what changed and decide when the release becomes
                        public.
                    </dd>
                </div>
                <div class="p-6 sm:p-8">
                    <dt class="technical-label text-primary">Discover</dt>
                    <dd class="mt-10 text-sm leading-6">
                        Give the community one durable place to find the work
                        and cheer it on.
                    </dd>
                </div>
            </dl>
        </section>
        <section
            class="mx-auto flex w-full max-w-[90rem] flex-col items-start gap-6 border-x border-b border-foreground p-6 sm:flex-row sm:items-center sm:justify-between sm:p-10"
        >
            <p class="display-type text-4xl sm:text-6xl">Ready to ship?</p>
            <Button as-child size="lg" class="w-full sm:w-auto"
                ><Link :href="page.props.auth.user ? create() : register()"
                    >Open composer <ArrowUpRight class="size-4" /></Link
            ></Button>
        </section>
    </PublicShell>
</template>
