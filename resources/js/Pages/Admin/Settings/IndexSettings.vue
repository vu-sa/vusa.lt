<template>
  <OverviewPage
    :eyebrow="`${$t('shell.workspaces.sistema.title')} · ${$t('shell.sections.nustatymai')}`"
    :title="$t('settings.title')"
    :lead="$t('settings.description')"
  >
    <OverviewSection :title="$t('settings.categories.general')">
      <NavigationTiles :items="generalItems" :columns="3" data-category="general" />
    </OverviewSection>

    <OverviewSection v-if="isSuperAdmin" :title="$t('settings.categories.authorization')">
      <NavigationTiles :items="authorizationItems" :columns="3" data-category="authorization" />
    </OverviewSection>
  </OverviewPage>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { Building2, CalendarRange, FileText, Globe, KeyRound, ListChecks, Users } from 'lucide-vue-next';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { NavigationTiles, OverviewSection, type NavigationTileItem } from '@/Components/Patterns';

defineProps<{
  isSuperAdmin: boolean;
}>();

const generalItems = computed<NavigationTileItem[]>(() => [
  {
    key: 'forms',
    label: $t('settings.pages.forms.title'),
    description: $t('settings.pages.forms.description'),
    icon: ListChecks,
    href: route('settings.forms.edit'),
  },
  {
    key: 'meetings',
    label: $t('settings.pages.meetings.title'),
    description: $t('settings.pages.meetings.description'),
    icon: Users,
    href: route('settings.meetings.edit'),
  },
  {
    key: 'atstovavimas',
    label: $t('settings.pages.atstovavimas.title'),
    description: $t('settings.pages.atstovavimas.description'),
    icon: Building2,
    href: route('settings.atstovavimas.edit'),
  },
  {
    key: 'documents',
    label: $t('settings.pages.documents.title'),
    description: $t('settings.pages.documents.description'),
    icon: FileText,
    href: route('settings.documents.edit'),
  },
  {
    key: 'cadences',
    label: $t('settings.pages.cadences.title'),
    description: $t('settings.pages.cadences.description'),
    icon: CalendarRange,
    href: route('settings.cadences.index'),
  },
  {
    key: 'site',
    label: $t('settings.pages.site.title'),
    description: $t('settings.pages.site.description'),
    icon: Globe,
    href: route('settings.site.edit'),
  },
]);

const authorizationItems = computed<NavigationTileItem[]>(() => [
  {
    key: 'authorization',
    label: $t('settings.pages.authorization.title'),
    description: $t('settings.pages.authorization.description'),
    icon: KeyRound,
    href: route('settings.authorization.edit'),
  },
]);
</script>
