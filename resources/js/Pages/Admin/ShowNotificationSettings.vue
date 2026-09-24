<template>
  <OverviewPage :title="$t('shell.account.notifications')">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
      <Link :href="route('profile')" class="inline-flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
        <ArrowLeft class="size-4" />
        {{ $t('Profilis') }}
      </Link>

      <PushDeviceManagement />

      <NotificationPreferences
        :notification-preferences
        :notification-categories
        :notification-channels
        :available-digest-emails
      />
    </div>
  </OverviewPage>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Bell } from 'lucide-vue-next';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import NotificationPreferences from '@/Features/Admin/Notifications/NotificationPreferences.vue';
import PushDeviceManagement from '@/Features/Admin/Notifications/PushDeviceManagement.vue';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

defineProps<{
  notificationPreferences: {
    channels: Record<string, Record<string, boolean>>;
    digest_frequency_hours: number;
    digest_emails: string[];
    muted_until: string | null;
    muted_threads: Record<string, string[]>;
    reminder_settings: {
      task_reminder_days: number[];
      meeting_reminder_hours: number[];
    };
  };
  notificationCategories: Record<string, { value: string; modelEnumKey: string; color: string }>;
  notificationChannels: Record<string, { value: string; enabledByDefault: boolean }>;
  availableDigestEmails: { email: string; label: string; type: 'user' | 'duty' }[];
}>();

usePageBreadcrumbs([
  { label: $t('Profilis'), href: route('profile') },
  { label: $t('shell.account.notifications'), icon: Bell },
]);
</script>
