<template>
  <OverviewSection
    :title="$t('Naudingi dokumentai')"
    :icon="BookOpen"
    variant="home"
    content-ruled
    data-slot="reference-documents"
  >
    <RuledGrid as="ul" :columns="{ base: 1, sm: 2, lg: 1, xl: 2 }" top-rule="cells">
      <li v-for="file in files" :key="file.id" class="flex">
        <a
          :href="route('fileableFiles.open', file.id)"
          target="_blank"
          rel="noopener noreferrer"
          :class="[
            'group flex w-full items-start gap-3 p-4 text-left',
            'transition-colors hover:bg-secondary',
            'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
          ]"
          :data-reference-document="file.id"
        >
          <FileText class="size-5 shrink-0 text-brand" aria-hidden="true" />
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-bold break-words text-foreground">{{ file.name }}</span>
            <span class="mt-1 block text-xs text-muted-foreground">
              {{ file.fileable?.title ?? $t(fileTypeLabel(file.file_type)) }}
            </span>
          </span>
          <ExternalLink class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
        </a>
      </li>
    </RuledGrid>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { BookOpen, ExternalLink, FileText } from 'lucide-vue-next';

import type { FileableFileItem } from './types';

import { RuledGrid } from '@/Components/Brand';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { fileTypeLabel } from '@/Constants/fileTypes';

defineProps<{
  files: FileableFileItem[];
}>();
</script>
