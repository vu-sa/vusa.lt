<script setup lang="ts">
import { Link as InertiaLink, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Route, Files, BookOpen, NotebookPen, Users, Library } from '@lucide/vue';
import { NativeSelect } from '@/Components/ui/native-select';

const props = defineProps<{ canSeeReps: boolean; canManage?: boolean }>();

const page = usePage();
const tabs = [
  { icon: Route, key: 'path', route: 'learningPath.show', segment: '/learning' },
  { icon: Files, key: 'documents', route: 'learningPath.documents', segment: '/learning/documents' },
  { icon: BookOpen, key: 'glossary', route: 'glossaryTerms.index', segment: '/learning/glossary' },
  { icon: NotebookPen, key: 'handover', route: 'learningPath.handover', segment: '/learning/handover' },
  { icon: Users, key: 'reps', route: 'learningPath.reps', segment: '/learning/reps' },
  { icon: Library, key: 'library', route: 'learningSteps.index', segment: '/learning/library' },
] as const;
const active = computed(() => {
  const path = page.url.split('?')[0] ?? '';
  if (path.includes('/learning/steps')) return props.canManage ? 'library' : 'path';
  return [...tabs].reverse().find(tab => path.endsWith(tab.segment) || path.includes(tab.segment + '/'))?.key ?? 'path';
});
const visibleTabs = computed(() => tabs.filter(tab => (tab.key !== 'reps' || props.canSeeReps) && (tab.key !== 'library' || props.canManage)));
function navigate(value: string | number | null | undefined) {
  const tab = visibleTabs.value.find(tab => tab.key === value);
  if (tab) router.visit(route(tab.route));
}
</script>

<template>
  <nav :aria-label="$t('learning.pages.path')" class="hidden overflow-x-auto border-b border-border md:flex">
    <template v-for="tab in visibleTabs" :key="tab.key">
      <InertiaLink
        v-if="(tab.key !== 'reps' || canSeeReps) && (tab.key !== 'library' || canManage)"
        :href="route(tab.route)"
        :aria-current="active === tab.key ? 'page' : undefined"
        :class="[
          'inline-flex h-10 shrink-0 items-center gap-2 border-b-2 px-3 text-xs font-bold uppercase tracking-wide pointer-coarse:h-11 focus-visible:outline-2 focus-visible:outline-ring',
          active === tab.key ? 'border-brand text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground',
        ]"
      >
        <component :is="tab.icon" class="size-4 shrink-0" aria-hidden="true" />
        {{ $t(`learning.tabs.${tab.key}`) }}
      </InertiaLink>
    </template>
  </nav>
  <label class="block space-y-1.5 border-b border-border py-3 md:hidden">
    <span class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('Rodyti') }}</span>
    <NativeSelect :model-value="active" :options="visibleTabs.map(tab => ({ value: tab.key, label: $t(`learning.tabs.${tab.key}`) }))" @update:model-value="navigate" />
  </label>
</template>
