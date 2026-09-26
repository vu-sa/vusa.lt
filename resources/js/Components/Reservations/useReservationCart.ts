import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';
import { computed, ref } from 'vue';

import type { ReservationCart } from './types';

/** One sheet per page: the bar, the add buttons and the record page all open the same one. */
const sheetOpen = ref(false);

/** Cart writes still in flight; the checkout shows "Saugoma…" until they land. */
const pendingWrites = ref(0);

// Async: a debounced quantity or text save must not cancel another cart write in flight.
const VISIT = {
  preserveScroll: true,
  preserveState: true,
  async: true,
  only: ['reservationCart'],
  onStart: () => { pendingWrites.value += 1; },
  onFinish: () => { pendingWrites.value = Math.max(0, pendingWrites.value - 1); },
};

/**
 * The acting user's reservation cart (a server-side draft). Every write redirects back and only
 * reloads the `reservationCart` prop, so the page around it keeps its state.
 */
export function useReservationCart() {
  const page = usePage();

  const cart = computed(() => (page.props.reservationCart as ReservationCart | null | undefined) ?? null);
  const items = computed(() => cart.value?.items ?? []);
  const hasPeriod = computed(() => !!cart.value?.start_time && !!cart.value?.end_time);
  const period = computed(() => (hasPeriod.value
    ? { start: cart.value!.start_time!, end: cart.value!.end_time! }
    : null));

  const quantityOf = (resourceId: string) => items.value.find(item => item.resource_id === resourceId)?.quantity ?? 0;

  const add = (resourceId: string, quantity = 1) => {
    router.post(route('reservationCart.items.store'), { resource_id: resourceId, quantity }, {
      ...VISIT,
      onSuccess: () => {
        toast.success($t('reservations.cart.added'), {
          action: { label: $t('reservations.cart.review'), onClick: () => { sheetOpen.value = true; } },
        });
      },
    });
  };

  /** No reservation started yet: this resource starts one, and the form opens with it. */
  const startWith = (resourceId: string) => {
    router.post(route('reservationCart.items.store'), { resource_id: resourceId, quantity: 1 }, {
      ...VISIT,
      onSuccess: () => router.visit(route('reservations.create')),
    });
  };

  const setQuantity = (resourceId: string, quantity: number) => {
    router.patch(route('reservationCart.items.update', resourceId), { quantity }, VISIT);
  };

  const remove = (resourceId: string) => {
    router.delete(route('reservationCart.items.destroy', resourceId), VISIT);
  };

  const setPeriod = (start: number, end: number) => {
    router.put(route('reservationCart.update'), { start_time: start, end_time: end }, VISIT);
  };

  const saveDetails = (details: { name?: string | null; description?: string | null }, onSaved?: () => void) => {
    router.put(route('reservationCart.update'), details, { ...VISIT, onSuccess: () => onSaved?.() });
  };

  const clear = (onCleared?: () => void) => {
    router.delete(route('reservationCart.destroy'), {
      ...VISIT,
      onSuccess: () => {
        sheetOpen.value = false;
        onCleared?.();
      },
    });
  };

  const openSheet = () => {
    sheetOpen.value = true;
  };

  const isSaving = computed(() => pendingWrites.value > 0);

  const hasDraft = computed(() => cart.value !== null);

  return { cart, items, hasDraft, hasPeriod, period, sheetOpen, isSaving, quantityOf, add, startWith, setQuantity, remove, setPeriod, saveDetails, clear, openSheet };
}
