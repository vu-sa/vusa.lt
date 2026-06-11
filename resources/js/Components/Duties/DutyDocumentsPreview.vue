<template>
  <SectionCard
    :title="$t('Dokumentai')"
    :icon="FileText"
    :count="files?.length || undefined"
    :empty="!isFetching && (!files || files.length === 0)"
  >
    <!-- Loading skeleton -->
    <div v-if="isFetching" class="space-y-2">
      <div v-for="i in 3" :key="i" class="h-8 animate-pulse rounded-md bg-muted" />
    </div>

    <div v-else class="space-y-0.5">
      <a
        v-for="file in files"
        :key="file.id"
        :href="file.webUrl ?? undefined"
        target="_blank"
        rel="noopener noreferrer"
        :class="[
          'flex items-center gap-2.5 rounded-md px-2 py-1.5',
          'transition-colors hover:bg-accent/50',
        ]"
      >
        <FileText class="h-4 w-4 shrink-0 text-muted-foreground" />
        <span class="min-w-0 flex-1 truncate text-sm text-foreground">{{ file.name }}</span>
        <ExternalLink class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
      </a>
    </div>

    <template #empty>
      <div class="py-6 text-center">
        <FileText class="mx-auto mb-2 h-7 w-7 text-muted-foreground" />
        <p class="text-sm text-muted-foreground">
          {{ $t('Dokumentų nėra') }}
        </p>
      </div>
    </template>
  </SectionCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useFetch } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { FileText, ExternalLink } from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';

interface DriveItem {
  id: string;
  name: string;
  webUrl?: string | null;
  file?: unknown;
  folder?: unknown;
}

const props = defineProps<{
  fileable: { id: string | number; type: string };
}>();

const { data, isFetching } = useFetch(
  route('sharepoint.getTypesDriveItems', { type: props.fileable.type, id: props.fileable.id }),
).json<DriveItem[]>();

/** Only surface actual files (skip folders) in the compact sidebar list. */
const files = computed(() => (data.value ?? []).filter((item) => item.file));
</script>
