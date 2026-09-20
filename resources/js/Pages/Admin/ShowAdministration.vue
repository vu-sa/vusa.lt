<template>
  <OverviewPage
    :eyebrow="$t('shell.chrome.product')"
    :title="$t('shell.chrome.all_sections')"
    :lead="$t('shell.chrome.all_sections_lead')"
  >
    <template #actions>
      <div class="relative w-full sm:w-64">
        <SearchIcon aria-hidden="true" class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="searchQuery"
          type="search"
          class="pl-8 pointer-coarse:h-11"
          :aria-label="$t('shell.chrome.find_section')"
          :placeholder="$t('shell.chrome.find_section')"
        />
      </div>
    </template>

    <!-- The Organizacija shortcuts that are tools rather than sections (duty_update, duty_periods). -->
    <OverviewSection v-if="filteredTools.length" :title="$t('shell.chrome.tools')">
      <ul class="grid gap-x-8 md:grid-cols-2 lg:grid-cols-3" data-slot="all-sections-tools">
        <li v-for="tool in filteredTools" :key="tool.key">
          <SectionLink :href="tool.href" :label="$t(tool.label)" :icon="tool.icon" />
        </li>
      </ul>
    </OverviewSection>

    <OverviewSection v-for="workspace in filteredWorkspaces" :key="workspace.key" :title="$t(workspace.label)">
      <p class="-mt-1 text-sm text-muted-foreground">
        {{ $t(workspace.description) }}
      </p>
      <ul class="grid gap-x-8 md:grid-cols-2 lg:grid-cols-3" :data-workspace="workspace.key">
        <li v-for="section in workspace.sections" :key="section.key">
          <SectionLink :href="section.href" :label="$t(section.label)" :icon="section.icon" />
        </li>
      </ul>
    </OverviewSection>

    <EmptyState
      v-if="!hasVisibleItems"
      mode="no-results"
      :title="$t('shell.chrome.no_sections')"
      :description="$t('Bandykite pakeisti paieškos kriterijus arba filtrus.')"
    />
  </OverviewPage>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, defineComponent, h, ref, type Component, type PropType } from 'vue';
import { ChartLine, ChevronRight, LayoutDashboard, Mail, MessageSquare, SearchIcon } from 'lucide-vue-next';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { EmptyState, OverviewSection } from '@/Components/Patterns';
import { CategoryIcon, NotificationIcon, PageIcon, SettingIcon, SharepointFileIcon } from '@/Components/icons';
import { Input } from '@/Components/ui/input';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';

const adminNavigation = computed(() => usePage().props.adminNavigation);

const searchQuery = ref('');

const matchesQuery = (label: string): boolean => {
  const query = searchQuery.value.trim().toLowerCase();

  return query === '' || $t(label).toLowerCase().includes(query);
};

/** One hairline row: icon, name, chevron. Touch targets reach 44px on coarse pointers. */
const SectionLink = defineComponent({
  props: {
    href: { type: String, required: true },
    label: { type: String, required: true },
    icon: { type: Object as PropType<Component>, required: true },
  },
  setup: props => () => h(Link, {
    href: props.href,
    prefetch: true,
    class: 'group flex min-h-11 items-center gap-3 border-b border-border px-1 py-2.5 hover:bg-secondary focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
  }, () => [
    h(props.icon, { 'class': 'size-4 shrink-0 text-muted-foreground', 'aria-hidden': 'true' }),
    h('span', { class: 'min-w-0 flex-1 truncate text-sm font-medium' }, props.label),
    h(ChevronRight, { 'class': 'size-4 shrink-0 text-muted-foreground group-hover:text-foreground', 'aria-hidden': 'true' }),
  ]),
});

/** A section with no backing `ModelEnum` entity — icon keyed by the section's own key. */
const SECTION_FALLBACK_ICONS: Record<string, Component> = {
  apzvalga: LayoutDashboard,
  kategorijos: CategoryIcon,
  failai: SharepointFileIcon,
  nustatymai: SettingIcon,
  sistemos_busena: NotificationIcon,
  laisku_eile: Mail,
  rep_metrics: ChartLine,
  pagalbos_uzklausos: MessageSquare,
};

function iconFor(section: { key: string; entityType: string | null }): Component {
  if (section.entityType) {
    return getEntityTypeDefinition(section.entityType)?.icon ?? PageIcon;
  }

  return SECTION_FALLBACK_ICONS[section.key] ?? PageIcon;
}

interface ToolItem {
  key: string;
  label: string;
  icon: Component;
  href: string;
}

const filteredTools = computed<ToolItem[]>(() => {
  const organizacija = adminNavigation.value?.workspaces.find(workspace => workspace.key === 'organizacija');

  return (organizacija?.createActions ?? [])
    .filter(action => action.target.kind === 'route')
    .map(action => ({
      key: action.key,
      label: action.label,
      icon: action.entityType ? (getEntityTypeDefinition(action.entityType)?.icon ?? PageIcon) : PageIcon,
      href: route((action.target as { kind: 'route'; routeName: string }).routeName),
    }))
    .filter(tool => matchesQuery(tool.label));
});

const filteredWorkspaces = computed(() =>
  (adminNavigation.value?.workspaces ?? [])
    .map(workspace => ({
      key: workspace.key,
      label: workspace.label,
      description: workspace.description,
      sections: workspace.sections
        .filter(section => matchesQuery(section.label))
        .map(section => ({
          key: section.key,
          label: section.label,
          icon: iconFor(section),
          href: route(section.routeName, section.routeParams),
        })),
    }))
    .filter(workspace => workspace.sections.length > 0),
);

const hasVisibleItems = computed(() => filteredTools.value.length > 0 || filteredWorkspaces.value.length > 0);
</script>
