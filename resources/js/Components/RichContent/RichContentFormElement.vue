<template>
  <div class="rich-content-form-element mb-6">
    <Suspense>
      <RichContentEditor v-model:contents="contentParts" :tenant-id @save="$emit('save')" />
      <template #fallback>
        <div class="space-y-4 border border-border bg-background p-4">
          <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">
            <Loader2 class="size-4 animate-spin text-brand" />
            {{ $t('Užkraunamas turinio redaktorius...') }}
          </div>
          <div class="space-y-3">
            <Skeleton class="h-24 w-full" />
            <div class="flex gap-2">
              <Skeleton class="h-9 w-28" />
              <Skeleton class="h-9 w-28" />
            </div>
          </div>
        </div>
      </template>
    </Suspense>
  </div>
</template>

<script setup lang="ts">
/**
 * There used to be a top-level "Redagavimas / Peržiūra" tab pair here, duplicating
 * RichContentEditor's own "Peržiūrėti viską" toggle one level up — and worse, its
 * preview pane rendered through `RichContentParser` without a `:resolved` prop, so
 * any `event-list`/`link-list` block previewed here always crashed
 * (`EventListDisplay` dereferencing `resolved!.items` on `undefined`). Removed
 * entirely in favor of the one preview surface inside `RichContentEditor`, which
 * does pass `:resolved` (see `useContentPartPreview`).
 */
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2 } from 'lucide-vue-next';

import RichContentEditor from './RichContentEditor.vue';
import type { ContentPart } from './Types';

import { Skeleton } from '@/Components/ui/skeleton';

const contentParts = defineModel<ContentPart[]>();

defineEmits<(e: 'save') => void>();

defineProps<{
  /** Tenant the page/news article being edited belongs to — for server-resolved (link-list, event-list, …) previews. */
  tenantId?: number | null;
}>();
</script>
