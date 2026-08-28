<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <CalendarPlus class="size-4" />
          {{ $t('Paskelbti kalendoriuje') }}
        </DialogTitle>
        <DialogDescription class="mt-2 text-sm text-muted-foreground">
          {{ $t('meetings.announce.explainer') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-3 pt-2">
        <button
          type="button"
          class="flex w-full items-start gap-3 rounded-lg border p-3 text-left transition-colors hover:bg-accent/50 disabled:opacity-60"
          :disabled="processing"
          @click="createEvent"
        >
          <Plus class="mt-0.5 size-4 shrink-0 text-primary" />
          <span>
            <span class="block text-sm font-medium">{{ $t('Sukurti naują įrašą') }}</span>
            <span class="block text-xs text-muted-foreground">{{ $t('meetings.announce.create_hint') }}</span>
          </span>
        </button>

        <CollectionSelectDialog
          v-model:open="pickerOpen"
          collection="calendar"
          :base-filter-by="tenantFilter"
          :title="$t('Susieti su esamu įrašu')"
          :description="$t('meetings.announce.link_hint')"
          :confirm-label="$t('Susieti')"
          :search-placeholder="$t('Ieškoti renginio pagal pavadinimą...')"
          :empty-message="$t('Renginių nerasta')"
          @confirm="linkEvent"
        >
          <template #trigger>
            <button
              type="button"
              class="flex w-full items-start gap-3 rounded-lg border p-3 text-left transition-colors hover:bg-accent/50"
            >
              <Link2 class="mt-0.5 size-4 shrink-0 text-primary" />
              <span>
                <span class="block text-sm font-medium">{{ $t('Susieti su esamu įrašu') }}</span>
                <span class="block text-xs text-muted-foreground">{{ $t('meetings.announce.link_hint') }}</span>
              </span>
            </button>
          </template>
        </CollectionSelectDialog>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarPlus, Link2, Plus } from 'lucide-vue-next';
import { DialogDescription } from 'reka-ui';

import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

const props = defineProps<{
  open: boolean;
  meetingId: string;
  /** Tenants of the meeting's institutions; the announcement belongs to one of them. */
  tenantIds?: number[];
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const pickerOpen = ref(false);
const processing = ref(false);

/**
 * Narrow the search to the meeting's own tenants. The server re-checks this — the filter is
 * here so the editor is not offered events they cannot link in the first place.
 */
const tenantFilter = computed(() =>
  props.tenantIds?.length ? `tenant_id:=[${props.tenantIds.join(',')}]` : undefined,
);

const submit = (payload: Record<string, unknown>) => {
  processing.value = true;
  router.post(route('meetings.calendarEvent.store', { meeting: props.meetingId }), payload, {
    preserveScroll: true,
    onFinish: () => (processing.value = false),
    onSuccess: () => emit('update:open', false),
  });
};

const createEvent = () => submit({});

const linkEvent = (hits: NormalizedSearchHit[]) => {
  const picked = hits[0];
  if (!picked) return;
  submit({ calendar_id: Number(picked.recordId) });
};
</script>
