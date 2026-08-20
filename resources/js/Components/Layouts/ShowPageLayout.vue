<template>
  <AdminContentPage>
    <!-- Rendered here rather than via AdminContentPage's `title` prop, because that
         prop runs the title through $t() — wrong for an entity name. -->
    <Head v-if="title">
      <title>{{ title }}</title>
    </Head>

    <ShowPageHero
      flat
      :title
      :subtitle
      :icon
      :badge
    >
      <template v-if="$slots.icon" #icon>
        <slot name="icon" />
      </template>
      <template v-if="$slots.title" #title>
        <slot name="title" />
      </template>
      <template v-if="$slots.subtitle" #subtitle>
        <slot name="subtitle" />
      </template>
      <template v-if="$slots.badge" #badge>
        <slot name="badge" />
      </template>
      <template v-if="$slots.info" #info>
        <slot name="info" />
      </template>
      <template #actions>
        <ActivityLogSheet
          v-if="auditSubjectType && model"
          :subject-type="auditSubjectType"
          :subject-id="String(model.id)"
        />
        <slot name="actions" />
      </template>
    </ShowPageHero>

    <!-- Page-level alerts (vacancy warnings, etc.) sit between hero and tabs. -->
    <slot name="alert" />

    <Tabs v-if="hasTabs" v-model="currentTab" class="mt-6">
      <TabsList class="mb-4">
        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value" class="gap-1.5">
          <component :is="tab.icon" v-if="tab.icon" class="h-4 w-4 shrink-0" />
          <span>{{ tab.label }}</span>
          <span v-if="tab.count" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
            {{ tab.count }}
          </span>
        </TabsTrigger>
      </TabsList>

      <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
        <slot :name="tab.value" />
      </TabsContent>
    </Tabs>

    <!--
      Without tabs the default slot is simply the page body. With tabs it is
      where page-level overlays (dialogs, sheets) go — outside the panels, so
      they survive tab switches instead of unmounting with the active tab.
    -->
    <div :class="{ 'mt-6': !hasTabs }">
      <slot />
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';

import AdminContentPage from './AdminContentPage.vue';

import ShowPageHero from '@/Components/Hero/ShowPageHero.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

export interface ShowPageTab {
  /** Doubles as the name of the slot that fills this tab. */
  value: string;
  label: string;
  /** Rendered as a muted suffix; falsy values are hidden rather than showing "0". */
  count?: number | null;
  /** Optional leading icon. Admin surfaces use Lucide. */
  icon?: Component;
}

interface BadgeConfig {
  label: string;
  variant?: 'default' | 'secondary' | 'outline' | 'destructive';
  icon?: Component;
}

const props = withDefaults(defineProps<{
  title?: string;
  subtitle?: string;
  icon?: Component;
  badge?: BadgeConfig;
  /** The record being shown. Only `id` is used — for the activity log subject. */
  model?: { id: string | number };
  /**
   * `App\Support\Auditables` alias (e.g. "duty"). Omit for models that aren't
   * logged; the activity trigger then simply doesn't render.
   */
  auditSubjectType?: string;
  /**
   * Tabs to render. Each `value` becomes a slot name, so avoid the names this
   * component already uses: icon, title, subtitle, badge, info, actions, alert.
   * Omit entirely to render the default slot instead.
   */
  tabs?: ShowPageTab[];
  /**
   * localStorage key for remembering the open tab, e.g. "show-user-tab".
   * Ignored in controlled mode — a page binding `v-model:tab` owns persistence.
   */
  tabStorageKey?: string;
}>(), {
  title: undefined,
  subtitle: undefined,
  icon: undefined,
  badge: undefined,
  model: undefined,
  auditSubjectType: undefined,
  tabs: undefined,
  tabStorageKey: undefined,
});

/**
 * Pages that own their tab state — URL `?tab=` sync, per-entity resets,
 * cross-component navigation — bind `v-model:tab` instead of letting the layout
 * remember the choice. Such a page must supply a defined initial value, since
 * that is what marks the layout as controlled.
 */
const tabModel = defineModel<string>('tab');

const hasTabs = computed(() => (props.tabs?.length ?? 0) > 0);

const defaultTab = computed(() => props.tabs?.[0]?.value ?? '');

const storedTab = useStorage(props.tabStorageKey ?? 'show-page-tab', defaultTab.value, undefined, {
  // A controlled page owns persistence, so the layout must not also write a key
  // — least of all the shared `show-page-tab` default.
  writeDefaults: tabModel.value === undefined,
});

/** Decided once: a page either drives the tab for the whole mount, or it doesn't. */
const isControlled = tabModel.value !== undefined;

const currentTab = computed({
  get: () => (isControlled ? tabModel.value! : storedTab.value),
  set: (value: string) => {
    if (isControlled) {
      tabModel.value = value;
    } else {
      storedTab.value = value;
    }
  },
});

/**
 * A stored tab can outlive the tab that produced it — a rename or removal would
 * otherwise leave the page stuck on a tab that renders nothing. Fall back to the
 * first tab whenever the remembered value is no longer offered.
 */
watch(
  [() => props.tabs, currentTab],
  () => {
    if (!hasTabs.value) {
      return;
    }
    if (!props.tabs?.some(tab => tab.value === currentTab.value)) {
      currentTab.value = defaultTab.value;
    }
  },
  { immediate: true },
);
</script>
