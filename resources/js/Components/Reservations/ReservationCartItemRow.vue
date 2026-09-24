<template>
  <li
    :class="[
      'flex flex-col gap-2 py-3',
      item.problem && 'border-l-2 border-status-danger pl-3',
    ]"
    data-slot="reservation-cart-item"
    :data-problem="item.problem ?? undefined"
  >
    <div class="flex flex-wrap items-center justify-between gap-3">
      <Link
        :href="route('resources.show', item.resource_id)"
        class="flex min-w-0 flex-1 items-center gap-3 hover:text-brand"
      >
        <img
          v-if="item.image_url"
          :src="item.image_url"
          alt=""
          loading="lazy"
          class="size-10 shrink-0 border border-border object-cover"
        >
        <EntityTypeMark v-else :type="ModelEnum.RESOURCE" size="lg" icon-only />
        <span class="min-w-0">
          <span class="block truncate text-sm font-medium">{{ item.name ?? $t('Be pavadinimo') }}</span>
          <span class="flex flex-wrap gap-x-2 text-xs text-muted-foreground">
            <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
            <span class="tabular-nums">{{ availabilityLabel }}</span>
          </span>
        </span>
      </Link>

      <div class="flex shrink-0 items-center gap-2">
        <NumberField
          v-if="item.problem !== 'removed' && item.problem !== 'not_reservable'"
          v-model="quantity"
          :min="1"
          :max="maxQuantity"
          class="w-32"
          :aria-label="$t('reservations.cart.quantity')"
        />
        <Button
          type="button"
          variant="ghost"
          size="icon-sm"
          class="pointer-coarse:size-11"
          :title="$t('reservations.cart.remove')"
          :aria-label="$t('reservations.cart.remove_named', { name: item.name ?? '' })"
          @click="remove(item.resource_id)"
        >
          <Trash2 class="size-4" aria-hidden="true" />
        </Button>
      </div>
    </div>

    <div v-if="item.problem" class="flex flex-wrap items-center gap-2 text-xs text-status-danger">
      <AlertTriangle class="size-3.5 shrink-0" aria-hidden="true" />
      <span>{{ problemLabel }}</span>
      <Button
        v-if="item.problem === 'unavailable' && (item.available ?? 0) > 0"
        type="button"
        size="sm"
        variant="outline"
        voice="sentence"
        class="pointer-coarse:h-11"
        @click="setQuantity(item.resource_id, item.available ?? 1)"
      >
        {{ $t('reservations.cart.reduce_to', { count: String(item.available) }) }}
      </Button>
    </div>
  </li>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import type { ReservationCartItem } from './types';
import { useReservationCart } from './useReservationCart';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { Button } from '@/Components/ui/button';
import { NumberField } from '@/Components/ui/number-field';
import { ModelEnum } from '@/Types/enums';

const props = defineProps<{
  item: ReservationCartItem;
}>();

const { setQuantity, remove } = useReservationCart();

const quantity = ref(props.item.quantity);

watch(() => props.item.quantity, (value) => {
  quantity.value = value;
});

const saveQuantity = useDebounceFn((value: number) => {
  if (value !== props.item.quantity) {
    setQuantity(props.item.resource_id, value);
  }
}, 400);

watch(quantity, (value) => {
  if (Number.isInteger(value) && value >= 1) {
    void saveQuantity(value);
  }
});

// Never below the current quantity, or an over-limit line could not even be displayed.
const maxQuantity = computed(() => Math.max(props.item.quantity, props.item.available ?? props.item.capacity));

const availabilityLabel = computed(() => (props.item.available === null
  ? $t('reservations.cart.total', { capacity: String(props.item.capacity) })
  : $t('reservations.cart.free_of', { available: String(props.item.available), capacity: String(props.item.capacity) })));

const problemLabel = computed(() => {
  switch (props.item.problem) {
    case 'removed':
      return $t('reservations.cart.removed');
    case 'not_reservable':
      return $t('reservations.cart.not_reservable');
    default:
      return (props.item.available ?? 0) > 0
        ? $t('reservations.cart.only_available', { count: String(props.item.available) })
        : $t('reservations.cart.none_available');
  }
});
</script>
