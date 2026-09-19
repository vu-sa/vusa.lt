<template>
  <AdminContentPage :title="$t('Administravimas')">
    <!-- Search bar -->
    <div class="mb-6">
      <div class="relative w-full max-w-md">
        <Input
          v-model="searchQuery"
          :placeholder="$t('Ieškoti įrankių...')"
          class="w-full"
        >
          <template #prefix>
            <SearchIcon class="h-4 w-4 text-muted-foreground" />
          </template>
          <template v-if="searchQuery" #suffix>
            <Button variant="ghost" size="icon" @click="searchQuery = ''">
              <XIcon class="h-4 w-4" />
            </Button>
          </template>
        </Input>
      </div>
    </div>

    <!-- Tools first — the two Organizacija shortcuts that used to live in this hand-written
         category (duty_update, duty_periods). Other workspaces' create actions already have a
         home in the sidebar quick actions / ActionWindow, so they are not repeated here. -->
    <section v-if="filteredTools.length" class="my-8">
      <h2 class="mb-4 text-xl font-semibold">
        {{ $t('Įrankiai') }}
      </h2>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <Link v-for="tool in filteredTools" :key="tool.key" :href="tool.href" class="group relative block h-full w-full rounded-lg transition-all duration-200 hover:scale-[1.01]">
          <div class="relative flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-linear-to-br from-white to-white p-4 text-left text-sm leading-4 text-zinc-700 transition-all duration-300 group-hover:ring-1 group-hover:ring-primary/20 dark:border-0 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300">
            <span :class="cn('inline-flex self-start shrink-0 items-center justify-center rounded-md bg-gradient-to-br p-1.5', tool.gradient ?? 'from-muted to-muted')">
              <component :is="tool.icon" width="20" height="20" />
            </span>
            {{ $t(tool.label) }}
          </div>
        </Link>
      </div>
    </section>

    <!-- One category per workspace the user holds sections in (Pradžia's Apžvalga/Užduotys/
         Pranešimai already sit one tap away in the sidebar, so it is left out here — PR 7.5
         turns this page into the full Visi skyriai map, including it). -->
    <template v-for="workspace in filteredWorkspaces" :key="workspace.key">
      <section class="my-8">
        <h2 class="mb-4 text-xl font-semibold">
          {{ $t(workspace.label) }}
        </h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <Link v-for="section in workspace.sections" :key="section.key" :href="section.href" class="group relative block h-full w-full rounded-lg transition-all duration-200 hover:scale-[1.01]">
            <div class="relative flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-linear-to-br from-white to-white p-4 text-left text-sm leading-4 text-zinc-700 transition-all duration-300 group-hover:ring-1 group-hover:ring-primary/20 dark:border-0 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300">
              <component :is="section.icon" width="28" height="28" />
              {{ $t(section.label) }}
            </div>
          </Link>
        </div>
      </section>
    </template>

    <!-- Empty state when no items match filter -->
    <Alert v-if="!hasVisibleItems" variant="default" class="mt-8">
      <AlertCircleIcon class="h-4 w-4" />
      <AlertTitle>{{ $t("Nerasta rezultatų") }}</AlertTitle>
      <AlertDescription>
        {{ $t("Bandykite pakeisti paieškos kriterijus arba filtrus.") }}
      </AlertDescription>
    </Alert>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, type Component } from 'vue';

import {
  SearchIcon,
  XIcon,
  AlertCircleIcon,
  LayoutDashboard,
  Mail,
  MessageSquare,
} from 'lucide-vue-next';

import { cn } from '@/Utils/Shadcn/utils';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Alert, AlertTitle, AlertDescription } from '@/Components/ui/alert';
import { CategoryIcon, PageIcon, SharepointFileIcon, SettingIcon, NotificationIcon, TypeIcon } from '@/Components/icons';
import { quickActionGradient } from '@/Composables/useQuickActions';
import {
  usePageBreadcrumbs,
  BreadcrumbHelpers,
} from '@/Composables/useBreadcrumbsUnified';

usePageBreadcrumbs([{ label: $t('Administravimas'), icon: TypeIcon }]);

const adminNavigation = computed(() => usePage().props.adminNavigation);

const searchQuery = ref('');

const matchesQuery = (label: string): boolean => {
  if (!searchQuery.value) return true;
  return $t(label).toLowerCase().includes(searchQuery.value.toLowerCase());
};

interface ToolItem {
  key: string;
  label: string;
  icon: Component;
  gradient?: string;
  href: string;
}

/** A section with no backing `ModelEnum` entity — icon keyed by the section's own key. */
const SECTION_FALLBACK_ICONS: Record<string, Component> = {
  apzvalga: LayoutDashboard,
  kategorijos: CategoryIcon,
  failai: SharepointFileIcon,
  nustatymai: SettingIcon,
  sistemos_busena: NotificationIcon,
  laisku_eile: Mail,
  pagalbos_uzklausos: MessageSquare,
};

function iconFor(section: { key: string; entityType: string | null }): Component {
  if (section.entityType) {
    return getEntityTypeDefinition(section.entityType)?.icon ?? PageIcon;
  }

  return SECTION_FALLBACK_ICONS[section.key] ?? PageIcon;
}

const filteredTools = computed<ToolItem[]>(() => {
  const organizacija = adminNavigation.value?.workspaces.find(w => w.key === 'organizacija');

  return (organizacija?.createActions ?? [])
    .filter(action => action.target.kind === 'route')
    .map(action => ({
      key: action.key,
      label: action.label,
      icon: action.entityType ? (getEntityTypeDefinition(action.entityType)?.icon ?? PageIcon) : PageIcon,
      gradient: quickActionGradient(action.key),
      href: route((action.target as { kind: 'route'; routeName: string }).routeName),
    }))
    .filter(tool => matchesQuery(tool.label));
});

interface SectionItem {
  key: string;
  label: string;
  icon: Component;
  href: string;
}

interface WorkspaceGroup {
  key: string;
  label: string;
  sections: SectionItem[];
}

const filteredWorkspaces = computed<WorkspaceGroup[]>(() => {
  return (adminNavigation.value?.workspaces ?? [])
    .filter(workspace => workspace.key !== 'pradzia')
    .map(workspace => ({
      key: workspace.key,
      label: workspace.label,
      sections: workspace.sections
        .filter(section => matchesQuery(section.label))
        .map(section => ({
          key: section.key,
          label: section.label,
          icon: iconFor(section),
          href: route(section.routeName, section.routeParams),
        })),
    }))
    .filter(workspace => workspace.sections.length > 0);
});

const hasVisibleItems = computed(() => filteredTools.value.length > 0 || filteredWorkspaces.value.length > 0);
</script>
