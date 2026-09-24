<template>
  <div
    v-if="items.length > 0"
    class="sticky bottom-3 z-30 mt-8 border border-foreground/80 bg-popover text-popover-foreground"
    role="region"
    :aria-label="$t('reservations.cart.title')"
    data-slot="reservation-cart-bar"
  >
    <div class="flex flex-wrap items-center justify-between gap-3 p-3">
      <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-wide">
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
        <Button as-child variant="brand" class="u-touch">
          <Link :href="route('reservations.create')">
            {{ $t('reservations.cart.continue_short') }}
            <ArrowRight class="size-4" aria-hidden="true" />
          </Link>
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { AlertTriangle, ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

import { formatReservationPeriod } from './reservationPeriod';
import { useReservationCart } from './useReservationCart';

import { Button } from '@/Components/ui/button';

const { cart, items, period, openSheet } = useReservationCart();

const periodLabel = computed(() => (period.value
  ? formatReservationPeriod(period.value.start, period.value.end)
  : $t('reservations.cart.no_period')));
</script>
