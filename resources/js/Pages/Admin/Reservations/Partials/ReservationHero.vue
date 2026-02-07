<template>
  <Card class="overflow-hidden">
    <div class="relative">
      <!-- Subtle gradient background -->
      <div class="absolute inset-0 bg-gradient-to-br from-zinc-100/50 to-zinc-50 dark:from-zinc-800/50 dark:to-zinc-900" />

      <CardContent class="relative py-4 sm:py-6">
        <div class="flex flex-col gap-4 sm:gap-6">
          <!-- Header row: Icon + Title/Status -->
          <div class="flex items-start gap-3 sm:gap-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-zinc-50 sm:size-14 sm:rounded-xl dark:bg-zinc-50 dark:text-zinc-900">
              <component :is="Icons.RESERVATION" class="size-5 sm:size-7" />
            </div>
            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
              <!-- Title row with badge -->
              <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <h1 class="text-lg font-semibold leading-snug tracking-tight sm:text-xl lg:text-2xl" :title="reservation.name">
                  {{ reservation.name }}
                </h1>
                <div class="inline-flex shrink-0 items-center gap-1">
                  <Badge :variant="statusBadgeVariant">
                    {{ statusLabel }}
                  </Badge>
                  <Button variant="ghost" size="icon-sm" class="size-6" @click="$emit('show-help')">
                    <IFluentInfo24Regular class="size-4 text-muted-foreground" />
                    <span class="sr-only">{{ $t('Būsenų informacija') }}</span>
                  </Button>
                </div>
              </div>
              <!-- Date row -->
              <p class="inline-flex items-center gap-1.5 text-xs text-muted-foreground sm:text-sm">
                <IFluentCalendarLtr24Regular class="size-3.5 shrink-0 sm:size-4" />
                <span>{{ formattedDateRange }}</span>
              </p>
              <!-- Managers row -->
              <div v-if="reservation.users && reservation.users.length > 0" class="-ml-1 flex items-center gap-1">
                <UsersAvatarGroup :users="reservation.users" :max="3" :size="20" />
                <span class="text-xs leading-5 text-muted-foreground">
                  {{ reservation.users.length }} {{ $t('valdytojai') }}
                </span>
              </div>
            </div>
          </div>

          <!-- Stats and Actions row -->
          <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Quick Stats -->
            <div class="flex items-stretch gap-2 sm:gap-3">
              <div class="flex min-w-16 flex-col items-center justify-center rounded-lg border bg-background px-3 py-1.5 sm:min-w-20 sm:px-4 sm:py-2">
                <span class="text-xl font-semibold leading-none sm:text-2xl">{{ resourcesCount }}</span>
                <span class="mt-0.5 text-[10px] leading-tight text-muted-foreground sm:text-xs">
                  {{ $tChoice('entities.resource.model', resourcesCount) }}
                </span>
              </div>
              <div
                v-if="pendingCount > 0"
                class="flex min-w-16 flex-col items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 sm:min-w-20 sm:px-4 sm:py-2 dark:border-amber-900 dark:bg-amber-950"
              >
                <span class="text-xl font-semibold leading-none text-amber-600 sm:text-2xl dark:text-amber-400">
                  {{ pendingCount }}
                </span>
                <span class="mt-0.5 text-[10px] leading-tight text-amber-600 sm:text-xs dark:text-amber-400">
                  {{ $t('laukia') }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
              <Button variant="outline" size="sm" class="h-9 gap-1.5" @click="$emit('add-user')">
                <IFluentPersonAdd24Regular class="size-4 shrink-0" />
                <span class="hidden sm:inline">{{ $t('Pridėti valdytoją') }}</span>
              </Button>
              <Button size="sm" class="h-9 gap-1.5" @click="$emit('add-resource')">
                <IFluentAdd24Filled class="size-4 shrink-0" />
                <span class="hidden xs:inline">{{ $t('Pridėti išteklių') }}</span>
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { formatStaticTime } from '@/Utils/IntlTime';
import Icons from '@/Types/Icons/filled';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';

const props = defineProps<{
  reservation: App.Entities.Reservation;
}>();

defineEmits<{
  (e: 'add-resource'): void;
  (e: 'add-user'): void;
  (e: 'show-help'): void;
}>();

const locale = computed(() => usePage().props.app.locale);

// Date formatting
const dateFormatter = new Intl.DateTimeFormat(locale.value, {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

const formattedDateRange = computed(() => {
  const start = new Date(props.reservation.start_time);
  const end = new Date(props.reservation.end_time);
  return `${dateFormatter.format(start)} – ${dateFormatter.format(end)}`;
});

// Resource statistics
const resourcesCount = computed(() => props.reservation.resources?.length ?? 0);

const pendingCount = computed(() => {
  return props.reservation.resources?.filter(
    r => r.pivot?.state === 'created' || r.pivot?.state === 'reserved',
  ).length ?? 0;
});

// Overall reservation status based on resources
type ReservationStatus = 'empty' | 'pending' | 'approved' | 'mixed' | 'completed';

const overallStatus = computed<ReservationStatus>(() => {
  const resources = props.reservation.resources ?? [];

  if (resources.length === 0) return 'empty';

  const states = resources.map(r => r.pivot?.state);
  const allReturned = states.every(s => s === 'returned');
  const allLent = states.every(s => s === 'lent' || s === 'returned');
  const allPending = states.every(s => s === 'created' || s === 'reserved');
  const hasCancelledOrRejected = states.some(s => s === 'cancelled' || s === 'rejected');

  if (allReturned) return 'completed';
  if (allLent && !hasCancelledOrRejected) return 'approved';
  if (allPending) return 'pending';
  return 'mixed';
});

const statusLabel = computed(() => {
  const labels: Record<ReservationStatus, string> = {
    empty: $t('Tuščia'),
    pending: $t('Laukia patvirtinimo'),
    approved: $t('Patvirtinta'),
    mixed: $t('Mišri būsena'),
    completed: $t('Užbaigta'),
  };
  return labels[overallStatus.value];
});

const statusBadgeVariant = computed<'default' | 'secondary' | 'destructive' | 'outline'>(() => {
  switch (overallStatus.value) {
    case 'approved':
    case 'completed':
      return 'default';
    case 'pending':
      return 'outline';
    case 'mixed':
    case 'empty':
    default:
      return 'secondary';
  }
});
</script>
