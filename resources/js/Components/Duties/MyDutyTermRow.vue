<template>
  <li class="flex flex-col gap-1 py-3" data-slot="my-duty-term">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
      <Link v-if="term.dutyHref" :href="term.dutyHref" class="font-medium hover:text-brand">
        {{ term.dutyName }}
      </Link>
      <span v-else class="font-medium">{{ term.dutyName }}</span>

      <span class="text-sm text-muted-foreground">
        <Link v-if="term.institutionHref" :href="term.institutionHref" class="underline-offset-4 hover:underline">
          {{ term.institutionName }}
        </Link>
        <template v-else>{{ term.institutionName }}</template>
        <template v-if="term.tenant"> · {{ term.tenant }}</template>
      </span>

      <span v-if="term.isExOfficio" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('access.term.ex_officio') }}
      </span>
      <span v-if="term.representsTenant" class="text-sm text-muted-foreground">
        {{ $t('access.term.represents', { tenant: term.representsTenant }) }}
      </span>
    </div>

    <p class="text-sm text-muted-foreground tabular-nums">
      {{ term.startDate }} – {{ term.endDate ?? $t('access.term.until_now') }}
    </p>

    <p v-if="term.roles.length > 0" class="text-sm text-foreground">
      {{ $t('access.term.roles', { roles: term.roles.map(role => $t(role)).join(', ') }) }}
    </p>
  </li>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

export interface MyDutyTerm {
  id: string | number;
  dutyName: string;
  dutyHref: string | null;
  institutionName: string | null;
  institutionHref: string | null;
  tenant: string | null;
  representsTenant: string | null;
  startDate: string;
  endDate: string | null;
  isExOfficio: boolean;
  roles: string[];
}

defineProps<{
  term: MyDutyTerm;
}>();
</script>
