<template>
  <CollectionPage
    :source
    collection="mailQueue"
    :eyebrow="$t('Sistemos būsena')"
    :title="$t('Laiškų eilė')"
    :lead="$t('mail_queue.explanation')"
    default-view="rows"
    :available-views="['rows']"
    :item-key="recipient => recipient.user_id"
  >
    <template #actions>
      <div class="flex items-center gap-2">
        <Button variant="outline" voice="sentence" @click="source.refresh()">
          <RefreshCwIcon class="mr-2 h-4 w-4" />
          {{ $t('Atnaujinti') }}
        </Button>

        <Button v-if="canManage && totals.items > 0" variant="outline" voice="sentence" class="text-destructive" @click="requestClearAll">
          <Trash2Icon class="size-4" />
          {{ $t('Išvalyti eilę') }}
        </Button>
      </div>
    </template>
    <template #row="{ item: recipient }">
      <div class="border-b border-border px-3 py-3 last:border-b-0 sm:px-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex min-w-0 items-center gap-3">
            <MailIcon class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-foreground">
                {{ recipient.user?.name ?? $t('Ištrintas naudotojas') }}
              </p>
              <p class="truncate text-xs text-muted-foreground">
                <span v-if="recipient.user?.email">{{ recipient.user.email }} · </span>
                {{ $t('Seniausia') }}: {{ formatDate(recipient.oldest_at) }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Badge variant="secondary">
              {{ $tChoice('mail_queue.line_count', recipient.items_count) }}
            </Badge>
            <Button
              v-if="canManage"
              variant="ghost"
              size="sm"
              voice="sentence"
              class="text-destructive hover:text-destructive"
              :disabled="busyKey !== null"
              @click="requestClearRecipient(recipient)"
            >
              <Trash2Icon class="size-4" />
              {{ $t('Nesiųsti') }}
            </Button>
          </div>
        </div>

        <details class="mt-2 border-t border-border/70 pt-1">
          <summary class="flex min-h-11 cursor-pointer items-center text-xs font-medium text-foreground hover:text-brand">
            {{ $t('mail_queue.show_lines') }}
          </summary>
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
                class="size-11 shrink-0 text-destructive hover:text-destructive"
                :disabled="busyKey !== null"
                :aria-label="$t('Pašalinti eilutę')"
                @click="requestDeleteItem(item)"
              >
                <Trash2Icon class="h-4 w-4" />
              </Button>
            </li>
          </ul>
        </details>
      </div>
    </template>
    <template #empty>
      <EmptyState :title="$t('Laiškų eilė tuščia')" :description="$t('mail_queue.empty_description')" />
    </template>
  </CollectionPage>
  <ConfirmDialog
    v-model:open="confirmationOpen"
    :title="confirmationTitle"
    :description="confirmationDescription"
    :confirm-label="$t('Nesiųsti')"
    destructive
    @confirm="confirmDiscard"
  />
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';
import { format, parseISO } from 'date-fns';
import { Mail as MailIcon, RefreshCw as RefreshCwIcon, Trash2 as Trash2Icon } from 'lucide-vue-next';

import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
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

const props = defineProps<{
  recipients: {
    data: Recipient[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
  };
  canManage: boolean;
  totals: { items: number; recipients: number };
}>();

const dateLocale = useDateLocale();
const source = useDatabaseCollectionSource<Recipient>({
  endpoint: route('api.v1.admin.mailQueue.index'),
  initial: {
    items: props.recipients.data,
    total: props.recipients.total,
    perPage: props.recipients.per_page,
    currentPage: props.recipients.current_page,
    lastPage: props.recipients.last_page,
  },
  defaultSort: 'items_count:desc',
  sortOptions: () => [
    { value: 'items_count:desc', label: $t('Daugiausia eilučių') },
    { value: 'oldest_at:asc', label: $t('Seniausi pirmiau') },
  ],
});
watch(() => props.recipients, () => source.refresh());

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

const pendingDiscard = ref<{ url: string; key: string | number; kind: 'item' | 'recipient' | 'all'; count: number } | null>(null);
const confirmationOpen = computed({
  get: () => pendingDiscard.value !== null,
  set: (open: boolean) => { if (!open) pendingDiscard.value = null; },
});
const confirmationTitle = computed(() => pendingDiscard.value?.kind === 'all'
  ? $t('Išvalyti visą laiškų eilę?')
  : $t('mail_queue.discard_title'));
const confirmationDescription = computed(() => pendingDiscard.value?.kind === 'all'
  ? $t('mail_queue.clear_all_warning', { count: props.totals.items, recipients: props.totals.recipients })
  : $t('mail_queue.discard_warning', { count: pendingDiscard.value?.count ?? 0 }));

const requestDeleteItem = (item: QueuedItem) => {
  pendingDiscard.value = { url: route('mailQueue.destroy', item.id), key: item.id, kind: 'item', count: 1 };
};
const requestClearRecipient = (recipient: Recipient) => {
  pendingDiscard.value = { url: route('mailQueue.destroyForUser', recipient.user_id), key: recipient.user_id, kind: 'recipient', count: recipient.items_count };
};
const requestClearAll = () => {
  pendingDiscard.value = { url: route('mailQueue.destroyAll'), key: 'all', kind: 'all', count: props.totals.items };
};
const confirmDiscard = () => {
  if (!pendingDiscard.value) return;
  submit(pendingDiscard.value.url, pendingDiscard.value.key);
};

</script>
