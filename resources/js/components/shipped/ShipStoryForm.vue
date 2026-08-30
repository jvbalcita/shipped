<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';
import { focusFirstError } from '@/lib/focusFirstError';
import shipStoryRoutes from '@/routes/projects/ship-story';
import type { ProjectShipStory, StudioProject } from '@/types/creator';

const props = defineProps<{
    project: StudioProject;
    shipStory: ProjectShipStory | null;
}>();

const shipStoryForm = useForm({
    problem: props.shipStory?.problem ?? '',
    audience: props.shipStory?.audience ?? '',
    shipped: props.shipStory?.shipped ?? '',
    build_decisions: props.shipStory?.build_decisions ?? '',
    hardest_problem: props.shipStory?.hardest_problem ?? '',
    lessons_learned: props.shipStory?.lessons_learned ?? '',
    next: props.shipStory?.next ?? '',
    approve: false,
});

useUnsavedChangesGuard(computed(() => shipStoryForm.isDirty));

const ERROR_FIELD_IDS: Record<string, string> = {
    problem: 'story_problem',
    audience: 'story_audience',
    shipped: 'story_shipped',
    build_decisions: 'story_build_decisions',
    hardest_problem: 'story_hardest_problem',
    lessons_learned: 'story_lessons_learned',
    next: 'story_next',
};

function saveShipStory(approve: boolean): void {
    shipStoryForm.approve = approve;
    shipStoryForm.put(shipStoryRoutes.update(props.project).url, {
        preserveScroll: true,
        onSuccess: () => {
            shipStoryForm.approve = false;
        },
        onError: (errors) => focusFirstError(errors, ERROR_FIELD_IDS),
    });
}
</script>

<template>
    <form
        novalidate
        class="grid max-w-3xl gap-5"
        data-test="ship-story-form"
        @submit.prevent="saveShipStory(false)"
    >
        <Field>
            <FieldLabel for="story_problem"
                >What problem made this worth building?</FieldLabel
            >
            <Textarea
                id="story_problem"
                v-model="shipStoryForm.problem"
                rows="4"
                placeholder="Name the real problem behind the project."
            />
            <FieldError v-if="shipStoryForm.errors.problem">{{
                shipStoryForm.errors.problem
            }}</FieldError>
        </Field>
        <Field>
            <FieldLabel for="story_audience">Who is this for?</FieldLabel>
            <Textarea
                id="story_audience"
                v-model="shipStoryForm.audience"
                rows="3"
                placeholder="Describe the people who get value from it."
            />
            <FieldError v-if="shipStoryForm.errors.audience">{{
                shipStoryForm.errors.audience
            }}</FieldError>
        </Field>
        <Field>
            <FieldLabel for="story_shipped">What shipped?</FieldLabel>
            <Textarea
                id="story_shipped"
                v-model="shipStoryForm.shipped"
                rows="4"
                placeholder="Tell people what they can try now."
            />
            <FieldError v-if="shipStoryForm.errors.shipped">{{
                shipStoryForm.errors.shipped
            }}</FieldError>
        </Field>
        <div class="grid gap-5 md:grid-cols-2">
            <Field>
                <FieldLabel for="story_build_decisions"
                    >What build choices mattered?</FieldLabel
                >
                <Textarea
                    id="story_build_decisions"
                    v-model="shipStoryForm.build_decisions"
                    rows="5"
                    placeholder="Explain a Laravel, product, or design decision."
                />
                <FieldError v-if="shipStoryForm.errors.build_decisions">{{
                    shipStoryForm.errors.build_decisions
                }}</FieldError>
            </Field>
            <Field>
                <FieldLabel for="story_hardest_problem"
                    >What was hardest?</FieldLabel
                >
                <Textarea
                    id="story_hardest_problem"
                    v-model="shipStoryForm.hardest_problem"
                    rows="5"
                    placeholder="Share the obstacle that changed the build."
                />
                <FieldError v-if="shipStoryForm.errors.hardest_problem">{{
                    shipStoryForm.errors.hardest_problem
                }}</FieldError>
            </Field>
        </div>
        <Field>
            <FieldLabel for="story_lessons_learned"
                >What did you learn?</FieldLabel
            >
            <Textarea
                id="story_lessons_learned"
                v-model="shipStoryForm.lessons_learned"
                rows="4"
                placeholder="Leave the next builder a useful lesson."
            />
            <FieldError v-if="shipStoryForm.errors.lessons_learned">{{
                shipStoryForm.errors.lessons_learned
            }}</FieldError>
        </Field>
        <Field>
            <FieldLabel for="story_next"
                >What comes next?
                <span class="text-muted-foreground"
                    >(optional)</span
                ></FieldLabel
            >
            <Textarea
                id="story_next"
                v-model="shipStoryForm.next"
                rows="3"
                placeholder="Name the next experiment, release, or question."
            />
            <FieldError v-if="shipStoryForm.errors.next">{{
                shipStoryForm.errors.next
            }}</FieldError>
        </Field>
        <div class="flex flex-wrap gap-3">
            <Button
                type="submit"
                variant="outline"
                :disabled="shipStoryForm.processing"
                data-test="save-ship-story"
            >
                <Spinner v-if="shipStoryForm.processing" />
                Save draft
            </Button>
            <Button
                type="button"
                :disabled="shipStoryForm.processing"
                data-test="approve-ship-story"
                @click="saveShipStory(true)"
            >
                <Spinner v-if="shipStoryForm.processing" />
                <Check v-else class="size-4" />
                Approve Ship Story
            </Button>
        </div>
    </form>
</template>
