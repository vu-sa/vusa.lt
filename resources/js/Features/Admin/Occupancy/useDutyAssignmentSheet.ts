import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
  const duty = ref<(AssignmentDuty & { current_users?: UserWithPivot[] }) | null>(null);
  const dutiable = ref<AssignmentTerm | null>(null);
  const user = ref<AssignmentUser | null>(null);

  /** Who already holds the duty right now cannot be assigned to it again. */
  const takenIds = computed(() => (duty.value?.current_users ?? []).map(holder => String(holder.id)));

  const openFor = (target: DutyWithUsers, holder: UserWithPivot | null) => {
    const show = () => {
      const term = holder ? source.terms()?.find(candidate => candidate.id === holder.pivot?.id) : null;
      if (holder && !term) return;

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
      router.reload({ only, onSuccess: show });
    }
    else {
      show();
    }
  };

  return {
    open,
    duty,
    dutiable,
    user,
    takenIds,
    assign: (target: DutyWithUsers) => openFor(target, null),
    editTerm: (target: DutyWithUsers, holder: UserWithPivot) => openFor(target, holder),
  };
}
