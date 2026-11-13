import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useToasts } from '@/Composables/useToasts';
import type { AssignmentDuty, AssignmentTerm, AssignmentUser, DutyWithUsers, UserWithPivot } from '@/Types/DutyAssignment';

interface AssignmentSheetSource {
  /** Full term rows, an optional prop: undefined until loaded. */
  terms: () => AssignmentTerm[] | undefined;
  /** Whether the sheet's own data (study programmes) is still to load. */
  needsManagement: () => boolean;
}

/**
 * The "Priskirti" sheet (O21) opened from a duty row: a new holder, or one holder's term. The full
 * term only travels on request, so editing one waits for it rather than opening on partial data.
 */
export function useDutyAssignmentSheet(source: AssignmentSheetSource) {
  const open = ref(false);
  const loading = ref(false);
  const toasts = useToasts();
  const duty = ref<(AssignmentDuty & { current_users?: UserWithPivot[] }) | null>(null);
  const dutiable = ref<AssignmentTerm | null>(null);
  const user = ref<AssignmentUser | null>(null);

  /** Who already holds the duty right now cannot be assigned to it again. */
  const takenIds = computed(() => (duty.value?.current_users ?? []).map(holder => String(holder.id)));

  const openFor = (target: DutyWithUsers, holder: UserWithPivot | null) => {
    if (loading.value) return;
    const show = () => {
      const term = holder ? source.terms()?.find(candidate => candidate.id === holder.pivot?.id) : null;
      if (holder && !term) {
        toasts.error($t('Nepavyko atverti laikotarpio. Atnaujink puslapį ir bandyk dar kartą.'));
        return;
      }

      duty.value = target;
      dutiable.value = term ?? null;
      user.value = holder;
      open.value = true;
    };
    const only = [
      ...(source.needsManagement() ? ['management'] : []),
      ...(holder ? ['assignmentTerms'] : []),
    ];
    if (only.length) {
      loading.value = true;
      router.reload({ only, onSuccess: show, onError: () => toasts.error($t('Nepavyko įkelti duomenų. Bandyk dar kartą.')), onFinish: () => { loading.value = false; } });
    }
    else {
      show();
    }
  };

  return {
    open,
    loading,
    duty,
    dutiable,
    user,
    takenIds,
    assign: (target: DutyWithUsers) => openFor(target, null),
    editTerm: (target: DutyWithUsers, holder: UserWithPivot) => openFor(target, holder),
  };
}
