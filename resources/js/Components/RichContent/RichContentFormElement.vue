<template>
  <div class="rich-content-form-element mb-6">
    <Suspense>
      <RichContentEditor v-model:contents="contentParts" :tenant-id="tenantId" />
      <template #fallback>
        <div class="space-y-6">
          <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <div
              class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent dark:border-zinc-600" />
            Loading rich content editor...
          </div>
          <div class="space-y-4">
            <Skeleton class="h-32 w-full rounded-lg" />
            <div class="flex gap-2">
              <Skeleton class="h-10 w-32 rounded" />
              <Skeleton class="h-10 w-32 rounded" />
              <Skeleton class="h-10 w-32 rounded" />
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
import RichContentEditor from './RichContentEditor.vue';
import type { ContentPart } from './Types';

import { Skeleton } from '@/Components/ui/skeleton';

const contentParts = defineModel<ContentPart[]>();

defineProps<{
  /** Tenant the page/news article being edited belongs to — for server-resolved (link-list, event-list, …) previews. */
  tenantId?: number | null;
}>();
</script>
