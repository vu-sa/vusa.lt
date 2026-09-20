<template>
  <OverviewPage
    :eyebrow="$t('shell.workspaces.sistema.title')"
    :title="$t('sistema.overview.title')"
    :head-title="`${$t('shell.workspaces.sistema.title')} · ${$t('sistema.overview.title')}`"
    :lead="$t('sistema.overview.lead')"
  >
    <template v-if="counts.openRequests !== null" #attention>
      <OverviewSection
        :title="$t('sistema.overview.new_requests')"
        :empty="newRequests.length === 0"
        :empty-text="$t('sistema.overview.new_requests_empty')"
        :href="requestsHref"
        :href-label="$t('shell.sections.pagalbos_uzklausos')"
      >
        <ul class="divide-y divide-border border-y border-border" data-slot="new-requests">
          <li v-for="request in newRequests" :key="request.id">
            <Link
              :href="route('supportRequests.show', request.id)"
              prefetch
              class="flex items-center gap-3 px-1 py-3 hover:bg-secondary pointer-coarse:py-4"
            >
              <span class="min-w-0 flex-1">
                <span class="block truncate font-medium">{{ request.title }}</span>
                <span v-if="request.reporter" class="block truncate text-sm text-muted-foreground">{{ request.reporter }}</span>
              </span>
              <span v-if="request.created_at" class="shrink-0 text-sm text-muted-foreground">{{ formatNearDate(request.created_at) }}</span>
            </Link>
          </li>
        </ul>
      </OverviewSection>
    </template>

    <OverviewNumbers v-if="numbers.length > 0" :numbers />

    <Deferred v-if="counts.roles !== null" data="problems">
      <template #fallback>
        <Skeleton class="h-10 w-full" />
      </template>
      <OverviewSection
        :title="$t('sistema.overview.status')"
        :href="route('systemStatus')"
        :href-label="$t('sistema.overview.status_link')"
      >
        <p v-if="(problems ?? []).length === 0" class="border-y border-border py-3 text-sm text-muted-foreground" data-slot="system-ok">
          {{ $t('sistema.overview.status_ok') }}
        </p>
        <ul v-else class="divide-y divide-border border-y border-border" data-slot="system-problems">
          <li
            v-for="problem in problems"
            :key="problem.check"
            :class="['px-1 py-3 text-sm font-medium', problem.status === 'error' ? 'text-status-danger' : 'text-status-attention']"
          >
            {{ $t('sistema.overview.status_problem', {
              check: $t(`sistema.overview.checks.${problem.check}`),
              status: $t(`sistema.overview.statuses.${problem.status}`),
            }) }}
          </li>
        </ul>
      </OverviewSection>
    </Deferred>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Deferred, Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import { OverviewSection } from '@/Components/Patterns';
import { Skeleton } from '@/Components/ui/skeleton';
import { formatNearDate } from '@/Utils/dateTime';

const props = defineProps<{
  counts: { openRequests: number | null; queuedMail: number | null; roles: number | null; users: number | null };
  newRequests: { id: string; title: string; reporter: string | null; created_at: string | null }[];
  problems?: { check: string; status: string }[];
}>();

// `supportRequests.index` only redirects here; link the destination itself.
const requestsHref = route('mySupportRequests.index', { tab: 'all' });

// A number the user may not open is null, so it is not drawn (hidden, never disabled).
const numbers = computed<OverviewNumberItem[]>(() => {
  const candidates: (OverviewNumberItem | null)[] = [
    props.counts.openRequests === null
      ? null
      : {
          key: 'open_requests',
          label: $t('sistema.overview.numbers.open_requests'),
          value: props.counts.openRequests,
          href: requestsHref,
          tone: 'attention',
        },
    props.counts.queuedMail === null
      ? null
      : {
          key: 'queued_mail',
          label: $t('sistema.overview.numbers.queued_mail'),
          value: props.counts.queuedMail,
          href: route('mailQueue'),
          tone: 'attention',
        },
    props.counts.roles === null
      ? null
      : {
          key: 'roles',
          label: $t('sistema.overview.numbers.roles'),
          value: props.counts.roles,
          href: route('roles.index'),
        },
    props.counts.users === null
      ? null
      : {
          key: 'users',
          label: $t('sistema.overview.numbers.users'),
          value: props.counts.users,
          href: route('users.index'),
        },
  ];

  return candidates.filter((number): number is OverviewNumberItem => number !== null);
});
</script>
