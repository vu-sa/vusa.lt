<template>
  <div>
    <Sheet v-model:open="sheetOpen">
      <SheetContent
        data-slot="reservation-cart-sheet"
        :side="isMobile ? 'bottom' : 'right'"
        :class="['flex flex-col p-0', isMobile ? 'h-[92dvh] max-h-[92dvh]' : 'w-full sm:max-w-xl']"
      >
        <SheetHeader class="border-b border-border px-6 py-4">
          <SheetTitle class="text-xl font-semibold tracking-tight text-foreground">
            {{ $t('reservations.cart.title') }}
          </SheetTitle>
          <SheetDescription class="text-sm text-muted-foreground">
            {{ $tChoice('reservations.cart.items', items.length, { count: String(items.length) }) }}
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
          <div
            v-if="(cart?.problemCount ?? 0) > 0"
            class="border-l-2 border-status-danger bg-status-danger-surface px-4 py-3 text-sm"
            role="alert"
            data-testid="reservation-cart-conflicts"
          >
            <p class="font-semibold text-status-danger">
              {{ $t('reservations.cart.conflicts_title') }}
            </p>
            <p class="mt-0.5 text-muted-foreground">
              {{ $t('reservations.cart.conflicts_description') }}
            </p>
          </div>

          <section class="space-y-2">
            <h3 class="text-base font-semibold text-foreground">
              {{ $t('reservations.cart.period') }}
            </h3>
            <p class="text-xs text-muted-foreground">
              {{ $t('reservations.cart.period_hint') }}
            </p>
            <ReservationPeriodFields
              id-prefix="cart-period"
              :model-value="period"
              :start-error="errors.start_time"
              :end-error="errors.end_time"
              @update:model-value="setPeriod($event.start, $event.end)"
            />
          </section>

          <section>
            <p v-if="items.length === 0" class="border-y border-border py-6 text-sm text-muted-foreground">
              {{ $t('reservations.cart.empty_description') }}
            </p>
            <ul v-else class="divide-y divide-border border-y border-border">
              <ReservationCartItemRow v-for="item in items" :key="item.resource_id" :item />
            </ul>
          </section>

          <p v-if="cart?.ttlDays" class="text-xs text-muted-foreground">
            {{ $t('reservations.cart.expires', { days: String(cart.ttlDays) }) }}
          </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border bg-card px-6 py-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
          <Button
            v-if="cart"
            variant="ghost"
            voice="sentence"
            type="button"
            class="u-touch"
            @click="clearOpen = true"
          >
            {{ $t('reservations.cart.clear') }}
          </Button>
          <span v-else />

          <Button v-if="items.length > 0" as-child variant="brand" class="u-touch">
            <Link :href="route('reservations.create')" @click="sheetOpen = false">
              {{ $t('reservations.cart.continue') }}
              <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
          </Button>
          <Button v-else as-child variant="outline" voice="sentence" class="u-touch">
            <Link :href="route('resources.index')" @click="sheetOpen = false">
              {{ $t('reservations.cart.browse') }}
            </Link>
          </Button>
        </div>
      </SheetContent>
    </Sheet>

    <ConfirmDialog
      v-model:open="clearOpen"
      :title="$t('reservations.cart.clear_title')"
      :description="$t('reservations.cart.clear_description')"
      :confirm-label="$t('reservations.cart.clear')"
      destructive
      @confirm="clear()"
    />
  </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ArrowRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import ReservationCartItemRow from './ReservationCartItemRow.vue';
import ReservationPeriodFields from './ReservationPeriodFields.vue';
import { useReservationCart } from './useReservationCart';

import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { useIsMobile } from '@/Composables/useIsMobile';

const { cart, items, period, sheetOpen, setPeriod, clear } = useReservationCart();

const isMobile = useIsMobile();
const clearOpen = ref(false);

const errors = computed(() => (usePage().props.errors ?? {}) as Record<string, string>);
</script>
