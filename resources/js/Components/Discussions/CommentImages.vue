<template>
  <div
    v-if="images.length"
    :class="['mt-2 grid max-w-md gap-1', images.length === 1 ? 'grid-cols-1' : 'grid-cols-2']"
    data-slot="comment-images"
  >
    <button
      v-for="(image, index) in images"
      :key="image.id ?? image.url"
      type="button"
      :class="[
        'group/image relative block overflow-hidden border border-border bg-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
        images.length === 1 ? 'max-h-80' : 'aspect-square',
      ]"
      :aria-label="$t('discussions.images.open', { n: String(index + 1) })"
      @click="openIndex = index"
    >
      <img
        :src="images.length === 1 ? image.url : image.thumb"
        :srcset="images.length === 1 ? image.srcset ?? undefined : undefined"
        :alt="image.alt ?? ''"
        loading="lazy"
        :class="images.length === 1 ? 'max-h-80 w-auto' : 'size-full object-cover'"
      >
    </button>

    <Dialog :open="openIndex !== null" @update:open="value => !value && (openIndex = null)">
      <DialogContent class="max-w-[min(64rem,calc(100vw-2rem))] p-2 sm:p-3">
        <DialogTitle class="sr-only">
          {{ $t('discussions.images.open', { n: String((openIndex ?? 0) + 1) }) }}
        </DialogTitle>
        <img v-if="current" :src="current.url" :alt="current.alt ?? ''" class="mx-auto max-h-[80dvh] w-auto">
        <div v-if="images.length > 1" class="flex items-center justify-between gap-2 pt-2">
          <Button type="button" variant="ghost" size="sm" :disabled="openIndex === 0" @click="openIndex = (openIndex ?? 1) - 1">
            <ChevronLeft />
            {{ $t('discussions.images.previous') }}
          </Button>
          <span class="text-xs tabular-nums text-muted-foreground">{{ (openIndex ?? 0) + 1 }} / {{ images.length }}</span>
          <Button type="button" variant="ghost" size="sm" :disabled="openIndex === images.length - 1" @click="openIndex = (openIndex ?? 0) + 1">
            {{ $t('discussions.images.next') }}
            <ChevronRight />
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import type { ImageData } from '@/Types/media';

const props = defineProps<{ images: ImageData[] }>();

const openIndex = ref<number | null>(null);
const current = computed(() => (openIndex.value === null ? null : props.images[openIndex.value] ?? null));
</script>
