<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ canSeeReps: boolean; canManage?: boolean }>();

const page = usePage();
const tabs = [
  { key: 'path', route: 'learningPath.show', segment: '/learning' },
  { key: 'documents', route: 'learningPath.documents', segment: '/learning/documents' },
  { key: 'glossary', route: 'glossaryTerms.index', segment: '/learning/glossary' },
  { key: 'handover', route: 'learningPath.handover', segment: '/learning/handover' },
  { key: 'reps', route: 'learningPath.reps', segment: '/learning/reps' },
  { key: 'library', route: 'learningSteps.index', segment: '/learning/library' },
] as const;
const active = computed(() => {
  const path = page.url.split('?')[0] ?? '';
  if (path.includes('/learning/steps')) return props.canManage ? 'library' : 'path';
  return [...tabs].reverse().find(tab => path.endsWith(tab.segment) || path.includes(tab.segment + '/'))?.key ?? 'path';
});
</script>

<template>
  <nav :aria-label="$t('learning.pages.path')" class="flex overflow-x-auto border-b border-border">
    <template v-for="tab in tabs" :key="tab.key">
      <Link
        v-if="(tab.key !== 'reps' || canSeeReps) && (tab.key !== 'library' || canManage)"
        :href="route(tab.route)"
        :aria-current="active === tab.key ? 'page' : undefined"
        :class="[
          'inline-flex min-h-11 shrink-0 items-center border-b-2 px-4 text-xs font-bold uppercase tracking-wider',
          active === tab.key ? 'border-brand text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground',
        ]"
      >
        {{ $t(`learning.tabs.${tab.key}`) }}
      </Link>
    </template>
  </nav>
</template>
