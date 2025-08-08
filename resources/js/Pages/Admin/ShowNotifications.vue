<template>
  <AdminContentPage :title="$t('Pranešimai')">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <NSwitch v-model:value="showReadNotifications" size="small" />
        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $t("Rodyti perskaitytus") }}</span>
      </div>

      <NButton :disabled="shownNotifications.length === 0" size="tiny" :loading="loading" text @click="handleAllRead">
        {{
          $t("Pažymėti visus")
        }}<template #icon>
          <IFluentCheckmarkCircle24Regular />
        </template>
      </NButton>
    </div>

    <div class="border text-center p-4 dark:border-zinc-700 rounded-sm">
      <template v-if="shownNotifications.length === 0">
        <span class="dark:text-zinc-400 text-zinc-700">{{ $t("Pranešimų nėra") }}.</span>
      </template>
      <NotificationItem v-for="notification in shownNotifications" :key="notification.id"
        :class="cn('w-full border-b last:border-0 border-zinc-300 dark:border-zinc-800', notification.read_at ? 'text-zinc-700' : '')"
        :notification>
        <div>{{ notification }}</div>
      </NotificationItem>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { cn } from "@/Utils/Shadcn/utils";
import { useToasts } from '@/Composables/useToasts';
import { useFetch } from "@vueuse/core";
import { router, usePage } from "@inertiajs/vue3";

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import type { NotificationData } from "@/Features/Admin/Notifications/NotificationItem.vue";
import NotificationItem from "@/Features/Admin/Notifications/NotificationItem.vue";

import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import { trans as $t } from "laravel-vue-i18n";
import Icons from "@/Types/Icons/regular";
import IFluentCheckmarkCircle24Regular from '~icons/fluent/checkmark-circle-24-regular';

const { notifications } = defineProps<{
  notifications: App.Entities.Notification<NotificationData>[];
}>();

const showReadNotifications = ref(false);

const shownNotifications = computed(() => {
  return showReadNotifications.value
    ? notifications
    : notifications.filter((notification) => !notification.read_at);
});

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs([
  { label: $t('Pranešimai'), icon: Icons.NOTIFICATION }
]);

const loading = ref(false);
const toasts = useToasts();

const handleAllRead = async () => {
  loading.value = true;

  const { isFinished } = await useFetch(
    route("notifications.mark-as-read.all"),
    { headers: { "X-CSRF-TOKEN": usePage().props.csrf_token } },
  ).post().json();

  if (isFinished.value) {
    loading.value = false;
    toasts.success("Visi pranešimai pažymėti kaip perskaityti.");
    router.reload();
  }
};
</script>
