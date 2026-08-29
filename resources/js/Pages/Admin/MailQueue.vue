<template>
  <AdminContentPage :title="$t('Laiškų eilė')">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
      <div class="flex gap-3">
        <StatTile :label="$t('Laukiančios eilutės')" :value="totals.items" />
        <StatTile :label="$t('Gavėjai')" :value="totals.recipients" />
      </div>

      <div class="flex items-center gap-2">
        <Button variant="outline" size="sm" @click="router.reload()">
          <RefreshCwIcon class="mr-2 h-4 w-4" />
          {{ $t('Atnaujinti') }}
        </Button>

        <AlertDialog v-if="canManage && totals.items > 0">
          <AlertDialogTrigger as-child>
            <Button variant="destructive" size="sm">
              <Trash2Icon class="mr-2 h-4 w-4" />
              {{ $t('Išvalyti eilę') }}
            </Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>{{ $t('Išvalyti visą laiškų eilę?') }}</AlertDialogTitle>
              <AlertDialogDescription>
                {{ $t('mail_queue.clear_all_warning', { count: totals.items, recipients: totals.recipients }) }}
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>{{ $t('Atšaukti') }}</AlertDialogCancel>
              <AlertDialogAction
                class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                @click="clearAll"
              >
                {{ $t('Išvalyti') }}
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>
    </div>

    <p class="mb-6 max-w-2xl text-sm text-muted-foreground">
      {{ $t('mail_queue.explanation') }}
    </p>

    <EmptyState
      v-if="recipients.length === 0"
      :title="$t('Laiškų eilė tuščia')"
      :description="$t('mail_queue.empty_description')"
    />

    <div v-else class="space-y-3">
      <SectionCard
        v-for="recipient in recipients"
        :key="recipient.user_id"
        :title="recipient.user?.name ?? $t('Ištrintas naudotojas')"
        :icon="MailIcon"
      >
        <template #action>
          <div class="flex items-center gap-2">
            <Badge variant="secondary">
              {{ $tChoice('mail_queue.line_count', recipient.items_count) }}
            </Badge>
            <Button
              v-if="canManage"
              variant="ghost"
              size="sm"
              class="text-destructive hover:text-destructive"
              :disabled="busyKey === recipient.user_id"
              @click="clearRecipient(recipient)"
            >
              <Trash2Icon class="mr-2 h-4 w-4" />
              {{ $t('Nesiųsti') }}
            </Button>
          </div>
        </template>

        <p class="mb-3 text-xs text-muted-foreground">
          <span v-if="recipient.user?.email">{{ recipient.user.email }} · </span>
          {{ $t('Seniausia') }}: {{ formatDate(recipient.oldest_at) }}
        </p>

        <ul class="divide-y divide-border">
          <li
            v-for="item in recipient.items"
            :key="item.id"
            class="flex items-start justify-between gap-3 py-2"
          >
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <Badge variant="outline" class="shrink-0 text-xs">
                  {{ item.category }}
                </Badge>
                <span class="truncate text-sm font-medium">{{ item.title ?? item.notification_class }}</span>
              </div>
              <p v-if="item.body" class="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                {{ item.body }}
              </p>
              <p class="mt-0.5 text-xs text-muted-foreground">
                {{ formatDate(item.created_at) }}
              </p>
            </div>

            <Button
              v-if="canManage"
              variant="ghost"
              size="icon"
              class="h-8 w-8 shrink-0 text-destructive hover:text-destructive"
              :disabled="busyKey === item.id"
              :aria-label="$t('Pašalinti eilutę')"
              @click="deleteItem(item)"
            >
              <Trash2Icon class="h-4 w-4" />
            </Button>
          </li>
        </ul>
      </SectionCard>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { format, parseISO } from 'date-fns';
import { Mail as MailIcon, RefreshCw as RefreshCwIcon, Trash2 as Trash2Icon } from 'lucide-vue-next';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { EmptyState, SectionCard, StatTile } from '@/Components/Patterns';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useDateLocale } from '@/Composables/useDateLocale';

interface QueuedItem {
  id: number;
  category: string;
  notification_class: string;
  title: string | null;
  body: string | null;
  url: string | null;
  created_at: string | null;
}

interface Recipient {
  user_id: string;
  user: { id: string; name: string; email: string; profile_photo_path: string | null } | null;
  items_count: number;
  oldest_at: string | null;
  newest_at: string | null;
  items: QueuedItem[];
}

defineProps<{
  recipients: Recipient[];
  canManage: boolean;
  totals: { items: number; recipients: number };
}>();

const dateLocale = useDateLocale();

// One request at a time, keyed by whatever row triggered it.
const busyKey = ref<string | number | null>(null);

const formatDate = (value: string | null) => {
  if (!value) return '—';
  return format(parseISO(value), 'yyyy-MM-dd HH:mm', { locale: dateLocale.value });
};

const submit = (url: string, key: string | number) => {
  if (busyKey.value !== null) return;
  busyKey.value = key;

  router.delete(url, {
    preserveScroll: true,
    onFinish: () => {
      busyKey.value = null;
    },
  });
};

const deleteItem = (item: QueuedItem) => submit(route('mailQueue.destroy', item.id), item.id);

const clearRecipient = (recipient: Recipient) =>
  submit(route('mailQueue.destroyForUser', recipient.user_id), recipient.user_id);

const clearAll = () => submit(route('mailQueue.destroyAll'), 'all');

usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm($t('Sistemos būsena'), 'systemStatus', $t('Laiškų eilė')),
);
</script>
