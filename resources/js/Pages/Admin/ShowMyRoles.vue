<template>
  <OverviewPage
    :eyebrow="$t('access.eyebrow')"
    :title="$t('access.title')"
    :lead="$t('access.lead')"
  >
    <OverviewSection
      :title="$t('access.current.title')"
      :empty="access.current.length === 0"
      :empty-text="$t('access.current.empty')"
    >
      <ul class="divide-y divide-border border-y border-border" data-testid="current-duties">
        <MyDutyTermRow v-for="term in access.current" :key="term.id" :term />
      </ul>
    </OverviewSection>

    <OverviewSection
      :title="$t('access.direct.title')"
      :empty="access.directRoles.length === 0"
      :empty-text="$t('access.direct.empty')"
    >
      <ul class="flex flex-col gap-1" data-testid="direct-roles">
        <li v-for="role in access.directRoles" :key="role" class="text-sm font-medium">
          {{ $t(role) }}
        </li>
      </ul>
    </OverviewSection>

    <OverviewSection
      :title="$t('access.capabilities.title')"
      :empty="!access.isSuperAdmin && workspaces.length === 0"
      :empty-text="$t('access.capabilities.empty')"
    >
      <p v-if="access.isSuperAdmin" class="text-sm text-foreground" data-testid="super-admin">
        {{ $t('access.capabilities.super_admin') }}
      </p>
      <ul v-else class="divide-y divide-border border-y border-border" data-testid="capabilities">
        <li v-for="workspace in workspaces" :key="workspace.key" class="flex flex-col gap-1 py-3 sm:flex-row sm:gap-6">
          <span class="w-40 shrink-0 text-sm font-semibold">{{ $t(workspace.label) }}</span>
          <ul class="flex flex-wrap gap-x-4 gap-y-1">
            <li v-for="section in workspace.sections" :key="section.key">
              <Link
                :href="sectionHref(section)"
                class="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline pointer-coarse:inline-block pointer-coarse:py-2"
              >
                {{ $t(section.label) }}
              </Link>
            </li>
          </ul>
        </li>
      </ul>
    </OverviewSection>

    <OverviewSection
      :title="$t('access.upcoming.title')"
      :empty="access.upcoming.length === 0"
    >
      <ul class="divide-y divide-border border-y border-border" data-testid="upcoming-duties">
        <MyDutyTermRow v-for="term in access.upcoming" :key="term.id" :term />
      </ul>
    </OverviewSection>

    <OverviewSection
      :title="$t('access.ended.title')"
      :empty="access.ended.length === 0"
    >
      <ul class="divide-y divide-border border-y border-border" data-testid="ended-duties">
        <MyDutyTermRow v-for="term in access.ended" :key="term.id" :term />
      </ul>
    </OverviewSection>

    <OverviewSection
      id="history"
      :title="$t('access.history.title')"
      :empty="access.history.length === 0"
      :empty-text="$t('access.history.empty')"
    >
      <ul class="divide-y divide-border border-y border-border" data-testid="access-history">
        <li
          v-for="change in access.history"
          :key="`${change.kind}-${change.dutyName}-${change.date}`"
          class="flex flex-col gap-1 px-1 py-3 sm:flex-row sm:items-baseline sm:gap-6"
          data-slot="access-change"
        >
          <span class="w-28 shrink-0 text-sm tabular-nums text-muted-foreground">{{ change.date }}</span>
          <span class="min-w-0 flex-1 text-sm">
            <span class="font-medium">{{ $t(`access.history.${change.kind}`, { duty: change.dutyName }) }}</span>
            <span v-if="change.institutionName" class="text-muted-foreground"> · {{ change.institutionName }}</span>
          </span>
        </li>
      </ul>
    </OverviewSection>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { MyDutyTermRow } from '@/Components/Duties';
import type { HomeAccessChange } from '@/Components/Home/types';
import type { MyDutyTerm } from '@/Components/Duties/MyDutyTermRow.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { OverviewSection } from '@/Components/Patterns';
import { sectionHref } from '@/Composables/useAdminNavigation';

defineProps<{
  access: {
    isSuperAdmin: boolean;
    directRoles: string[];
    current: MyDutyTerm[];
    upcoming: MyDutyTerm[];
    ended: MyDutyTerm[];
    /** Dated starts and ends of terms, newest first (U14). */
    history: HomeAccessChange[];
  };
}>();

// What the user can open is the catalog's answer for them — the same list every menu reads.
const workspaces = computed(() => usePage().props.adminNavigation?.workspaces ?? []);
</script>
