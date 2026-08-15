<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { UserCheck, UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    count: number;
    following: boolean;
    action: { url: string; method: 'post' | 'delete' };
}>();

const page = usePage();
const following = ref(props.following);
const count = ref(props.count);

function toggleFollow(): void {
    const authed = page.props.auth.user !== null;
    const previousFollowing = following.value;
    const previousCount = count.value;

    // Authenticated members toggle optimistically; guests get routed to
    // login by the auth-protected follow route.
    if (authed) {
        following.value = !previousFollowing;
        count.value = previousFollowing
            ? Math.max(0, previousCount - 1)
            : previousCount + 1;
    }

    const request =
        props.action.method === 'delete' ? router.delete : router.post;

    request.call(router, props.action.url, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            // Automatic rollback on failure.
            following.value = previousFollowing;
            count.value = previousCount;
        },
    });
}
</script>

<template>
    <Button
        variant="outline"
        :class="following ? 'text-primary' : undefined"
        data-test="follow-button"
        :aria-pressed="following"
        @click="toggleFollow"
    >
        <component
            :is="following ? UserCheck : UserPlus"
            class="size-4"
            aria-hidden="true"
        />
        {{ following ? 'Following' : 'Follow' }} / {{ count }}
    </Button>
</template>
