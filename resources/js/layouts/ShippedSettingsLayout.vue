<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { KeyRound, UserRound } from '@lucide/vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { edit as profileEdit } from '@/routes/profile';
import { edit as securityEdit } from '@/routes/security';

const page = usePage();
</script>

<template>
    <PublicShell title="Settings">
        <section
            class="mx-auto w-full max-w-[90rem] min-w-0 border-x border-foreground"
        >
            <SectionHeader label="Creator controls">
                <h1
                    class="display-type mt-12 text-[clamp(3rem,6vw,6rem)] sm:mt-0"
                >
                    Settings.
                </h1>
            </SectionHeader>
            <div class="grid gap-px bg-foreground lg:grid-cols-[16rem_1fr]">
                <nav class="bg-background p-4" aria-label="Settings navigation">
                    <Button
                        as-child
                        :variant="
                            page.url.includes('/profile') ? 'default' : 'ghost'
                        "
                        class="mb-2 w-full justify-start"
                        ><Link :href="profileEdit()"
                            ><UserRound class="size-4" />Profile</Link
                        ></Button
                    ><Button
                        as-child
                        :variant="
                            page.url.includes('/security') ? 'default' : 'ghost'
                        "
                        class="w-full justify-start"
                        ><Link :href="securityEdit()"
                            ><KeyRound class="size-4" />Security</Link
                        ></Button
                    >
                </nav>
                <main class="min-w-0 bg-background p-5 sm:p-8"><slot /></main>
            </div>
        </section>
    </PublicShell>
</template>
