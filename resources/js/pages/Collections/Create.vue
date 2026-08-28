<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CollectionMembershipPicker from '@/components/shipped/CollectionMembershipPicker.vue';
import type { CollectionMemberRow } from '@/components/shipped/CollectionMembershipPicker.vue';
import FileUpload from '@/components/shipped/FileUpload.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { index as collectionsIndex, store } from '@/routes/collections';

defineProps<{
    candidates: { id: number; name: string; creator_username: string | null }[];
}>();

const form = useForm({
    title: '',
    description: '',
    cover_image: null as File | null,
    project_ids: [] as number[],
});

// The picker owns the ordered rows; the form mirrors their ids so the
// server's membership errors land on a typed field.
const members = ref<CollectionMemberRow[]>([]);

watch(
    members,
    (rows) => {
        form.project_ids = rows.map((row) => row.id);
        form.clearErrors('project_ids');
    },
    { deep: true },
);

function submit(): void {
    form.post(store().url, { forceFormData: true });
}
</script>

<template>
    <PublicShell title="Studio: new collection">
        <section
            class="page-enter mx-auto w-full min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader label="Curator / New collection">
                <h1
                    class="display-type mt-12 max-w-4xl text-[clamp(3rem,6.5vw,6.75rem)] sm:mt-0"
                >
                    New collection.
                </h1>
                <p
                    class="mt-6 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    Write the narrative, pick the members, set the order. A
                    collection is live the moment you save it.
                </p>
            </SectionHeader>
            <form
                novalidate
                class="grid gap-px bg-foreground"
                @submit.prevent="submit"
            >
                <div class="grid gap-6 bg-background p-5 sm:p-8">
                    <Field>
                        <FieldLabel for="title">Title</FieldLabel>
                        <Input
                            id="title"
                            v-model="form.title"
                            type="text"
                            required
                            data-test="collection-title"
                        />
                        <FieldError v-if="form.errors.title">{{
                            form.errors.title
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="description"
                            >The curator's narrative — why these
                            projects</FieldLabel
                        >
                        <Textarea
                            id="description"
                            v-model="form.description"
                            :rows="8"
                            required
                            data-test="collection-description"
                        />
                        <FieldError v-if="form.errors.description">{{
                            form.errors.description
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="cover"
                            >Cover image (optional)</FieldLabel
                        >
                        <FileUpload v-model="form.cover_image" kind="cover" />
                        <FieldError v-if="form.errors.cover_image">{{
                            form.errors.cover_image
                        }}</FieldError>
                    </Field>
                </div>
                <div class="bg-background p-5 sm:p-8">
                    <CollectionMembershipPicker
                        v-model="members"
                        :candidates="candidates"
                    />
                    <FieldError v-if="form.errors.project_ids">{{
                        form.errors.project_ids
                    }}</FieldError>
                </div>
                <div
                    class="flex items-center justify-between gap-3 bg-background px-5 py-4 sm:px-8"
                >
                    <Button as-child variant="ghost" size="sm"
                        ><Link :href="collectionsIndex()">Cancel</Link></Button
                    >
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="collection-save"
                        >{{
                            form.processing ? 'Saving…' : 'Create collection'
                        }}</Button
                    >
                </div>
            </form>
        </section>
    </PublicShell>
</template>
