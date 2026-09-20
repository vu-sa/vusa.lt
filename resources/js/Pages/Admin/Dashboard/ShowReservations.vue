<template>
  <OverviewPage
    :eyebrow="$t('shell.workspaces.rezervacijos.title')"
    :title="$t('reservations.overview.title')"
    :head-title="`${$t('shell.workspaces.rezervacijos.title')} · ${$t('shell.sections.apzvalga')}`"
    :lead="$t('reservations.overview.lead')"
  >
    <template #actions>
      <Button variant="ghost" class="pointer-coarse:h-11" @click="showHelpModal = true">
        <Info aria-hidden="true" />
        {{ $t('reservations.overview.rules') }}
      </Button>
      <Button as-child variant="brand" class="pointer-coarse:h-11">
        <Link :href="route('reservations.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja rezervacija') }}
        </Link>
      </Button>
    </template>

    <template v-if="managesResources" #attention>
      <Deferred data="waitingForMe">
        <template #fallback>
          <CollectionSkeleton :rows="2" />
        </template>
        <ReservationsNeedingDecision
          :reservations="waitingForMe ?? []"
          :href="route('reservations.index', { scope: 'administered', state: 'created' })"
          @decide="openDecision"
        />
      </Deferred>
    </template>

    <OverviewNumbers :numbers />

    <Deferred data="myUpcoming">
      <template #fallback>
        <CollectionSkeleton :rows="2" />
      </template>
      <OverviewSection
        :title="$t('reservations.overview.mine')"
        :empty="(myUpcoming ?? []).length === 0"
        :empty-text="$t('reservations.overview.mine_empty')"
        :href="route('reservations.index', { scope: 'mine' })"
        :href-label="$t('reservations.overview.mine_link')"
      >
        <ul class="divide-y divide-border border-y border-border" data-slot="my-reservations">
          <li v-for="reservation in myUpcoming ?? []" :key="reservation.id">
            <Link
              :href="route('reservations.show', reservation.id)"
              prefetch
              class="flex items-center gap-3 px-1 py-3 hover:bg-secondary pointer-coarse:py-4"
            >
              <span class="min-w-0 flex-1">
                <span class="block truncate font-medium">{{ reservation.name }}</span>
                <span class="block truncate text-sm text-muted-foreground">
                  {{ formatDate(new Date(reservation.start_time)) }} – {{ formatDate(new Date(reservation.end_time)) }}
                </span>
              </span>
              <ReservationStateSummary
                :states="getReservationStates(reservation)"
                :unresolved="isReservationUnresolved(reservation)"
              />
            </Link>
          </li>
        </ul>
      </OverviewSection>
    </Deferred>

    <ReservationDecisionDialog
      v-model:open="decisionOpen"
      :decision
      :targets="decisionTargets"
      @done="reload"
    />

    <Dialog v-model:open="showHelpModal">
      <DialogContent class="max-h-[85vh] max-w-3xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ $t('reservations.dashboard.rules') }}</DialogTitle>
        </DialogHeader>
        <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="help" />
      </DialogContent>
    </Dialog>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Deferred, Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Info, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { CollectionSkeleton, OverviewSection } from '@/Components/Patterns';
import ReservationDecisionDialog from '@/Components/Reservations/ReservationDecisionDialog.vue';
import ReservationsNeedingDecision from '@/Components/Reservations/ReservationsNeedingDecision.vue';
import type { ReservationDecision } from '@/Components/Reservations/types';
import ReservationStateSummary from '@/Components/Tag/ReservationStateSummary.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';
import { formatDate } from '@/Utils/dateTime';
import {
  getReservationStates,
  isReservationUnresolved,
  type DashboardReservation,
} from '@/Utils/ReservationStatus';

const props = defineProps<{
  managesResources: boolean;
  counts: { waitingForMe: number; lentOut: number; overdue: number; mine: number; myOverdue: number };
  waitingForMe?: DashboardReservation[];
  myUpcoming?: DashboardReservation[];
}>();

// Every number lands on the filtered list it counts (O17), so the page agrees with the rows.
const numbers = computed<OverviewNumberItem[]>(() => {
  const mine: OverviewNumberItem = {
    key: 'mine',
    label: $t('reservations.overview.numbers.mine'),
    value: props.counts.mine,
    href: route('reservations.index', { scope: 'mine' }),
  };

  if (!props.managesResources) {
    return [
      mine,
      {
        key: 'my_overdue',
        label: $t('reservations.overview.numbers.my_overdue'),
        value: props.counts.myOverdue,
        href: route('reservations.index', { scope: 'mine', overdue: 1 }),
        tone: 'danger',
      },
    ];
  }

  return [
    {
      key: 'waiting',
      label: $t('reservations.overview.numbers.waiting'),
      value: props.counts.waitingForMe,
      href: route('reservations.index', { scope: 'administered', state: 'created' }),
      tone: 'attention',
    },
    {
      key: 'lent',
      label: $t('reservations.overview.numbers.lent'),
      value: props.counts.lentOut,
      href: route('reservations.index', { scope: 'administered', state: 'lent' }),
      tone: 'progress',
    },
    {
      key: 'overdue',
      label: $t('reservations.overview.numbers.overdue'),
      value: props.counts.overdue,
      href: route('reservations.index', { scope: 'administered', overdue: 1 }),
      tone: 'danger',
    },
    mine,
  ];
});

const showHelpModal = ref(false);

const decisionOpen = ref(false);
const decision = ref<ReservationDecision>('approved');
const decisionTargets = ref<DashboardReservation[]>([]);

function openDecision(next: ReservationDecision, target: DashboardReservation): void {
  decision.value = next;
  decisionTargets.value = [target];
  decisionOpen.value = true;
}

function reload(): void {
  router.reload({ only: ['counts', 'waitingForMe', 'myUpcoming'] });
}
</script>
