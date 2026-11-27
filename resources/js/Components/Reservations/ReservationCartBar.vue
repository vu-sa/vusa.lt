<template>
  <Teleport defer :to="`#${SHELL_BOTTOM_ACTIONS_ID}`" :disabled="!shellFocus">
    <div
      v-if="hasDraft"
      class="border-t border-border bg-popover text-popover-foreground md:mb-[var(--start-fm-clearance,0px)]"
      role="region"
      :aria-label="$t('reservations.cart.title')"
      data-slot="reservation-cart-bar"
    >
      <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-wide">
            {{ $t('reservations.cart.title') }} ·
            <span class="tabular-nums text-brand">{{ $tChoice('reservations.cart.items', items.length, { count: String(items.length) }) }}</span>
          </p>
          <p class="mt-0.5 truncate text-sm text-muted-foreground">
            {{ periodLabel }}
            <span v-if="(cart?.problemCount ?? 0) > 0" class="ml-2 inline-flex items-center gap-1 text-status-danger">
              <AlertTriangle class="size-3.5" aria-hidden="true" />
              {{ cart?.problemCount }}
            </span>
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Button variant="outline" voice="sentence" class="u-touch" @click="openSheet">
            {{ $t('reservations.cart.review') }}
          </Button>
          <Button v-if="items.length > 0" as-child variant="brand" class="u-touch" @click="sheetOpen = false">
            <Link :href="route('reservations.create')">
              {{ $t('reservations.cart.continue_short') }}
              <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
          </Button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { AlertTriangle, ArrowRight } from '@lucide/vue';
import { computed } from 'vue';

import { Button } from '@/Components/ui/button';
import { SHELL_BOTTOM_ACTIONS_ID, useShellFocus } from '@/Composables/useShellFocus';

import { formatReservationPeriod } from './reservationPeriod';
import { useReservationCart } from './useReservationCart';

const shellFocus = useShellFocus();
const { cart, items, hasDraft, period, openSheet, sheetOpen } = useReservationCart();

const periodLabel = computed(() => (period.value
  ? formatReservationPeriod(period.value.start, period.value.end)
  : $t('reservations.cart.no_period')));
</script>
