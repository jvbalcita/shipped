<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed } from 'vue';
import { index as notificationsIndex } from '@/routes/notifications';

const page = usePage();

const unreadCount = computed(
    () => Number(page.props.unreadNotificationsCount ?? 0),
);
</script>

<template>
    <Link
        :href="notificationsIndex()"
        class="relative inline-flex size-9 items-center justify-center border border-foreground transition-colors hover:bg-secondary focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring"
        data-test="notification-bell"
        aria-label="Notifications"
    >
        <Bell class="size-4" aria-hidden="true" />
        <span
            v-if="unreadCount > 0"
            class="technical-label absolute -top-2 -right-2 min-w-5 border border-foreground bg-primary px-1 text-center tabular-nums text-primary-foreground"
            data-test="notification-unread-count"
            aria-hidden="true"
        >
            {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
    </Link>
</template>
