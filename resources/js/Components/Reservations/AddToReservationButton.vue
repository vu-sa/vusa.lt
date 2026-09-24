<template>
  <!-- No reservation started: browsing is looking, so the row opens the resource (or starts one). -->
  <Button
    v-if="!hasDraft && withoutDraft === 'view'"
    as-child
    variant="outline"
    voice="sentence"
    :size
    :class="size === 'sm' && 'pointer-coarse:h-11'"
    data-testid="add-to-reservation"
    data-state="view"
  >
    <Link :href="route('resources.show', resourceId)" @click.stop>
      {{ $t('reservations.cart.view') }}
    </Link>
  </Button>
  <Button
    v-else-if="!hasDraft"
    type="button"
    :variant
    :voice="variant === 'brand' ? 'brand' : 'sentence'"
    :size
    :class="size === 'sm' && 'pointer-coarse:h-11'"
    data-testid="add-to-reservation"
    data-state="reserve"
    @click.stop.prevent="startWith(resourceId)"
  >
    <CalendarPlus class="size-4" aria-hidden="true" />
    {{ $t('reservations.cart.reserve') }}
  </Button>
  <Button
    v-else-if="inCart > 0"
    type="button"
    variant="outline"
    voice="sentence"
    :size
    :class="size === 'sm' && 'pointer-coarse:h-11'"
    data-testid="add-to-reservation"
    data-state="in-cart"
    @click.stop.prevent="openSheet"
  >
    <Check class="size-4" aria-hidden="true" />
    {{ $t('reservations.cart.in_cart', { count: String(inCart) }) }}
  </Button>
  <Button
    v-else
    type="button"
    :variant
    :voice="variant === 'brand' ? 'brand' : 'sentence'"
    :size
    :class="size === 'sm' && 'pointer-coarse:h-11'"
    :aria-label="label ? undefined : $t('reservations.cart.add_to_reservation')"
    data-testid="add-to-reservation"
    data-state="add"
    @click.stop.prevent="onAdd"
  >
    <Plus class="size-4" aria-hidden="true" />
    {{ label ?? $t('reservations.cart.add') }}
  </Button>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarPlus, Check, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import { useReservationCart } from './useReservationCart';

import { Button, type ButtonVariants } from '@/Components/ui/button';

const props = withDefaults(defineProps<{
  resourceId: string;
  size?: ButtonVariants['size'];
  variant?: ButtonVariants['variant'];
  label?: string;
  /** Before any reservation is started: open the resource, or start a reservation with it. */
  withoutDraft?: 'view' | 'reserve';
}>(), {
  size: 'sm',
  variant: 'outline',
  label: undefined,
  withoutDraft: 'view',
});

const emit = defineEmits<{
  added: [];
}>();

const { hasDraft, quantityOf, add, startWith, openSheet } = useReservationCart();

const inCart = computed(() => quantityOf(props.resourceId));

const onAdd = () => {
  add(props.resourceId);
  emit('added');
};
</script>
