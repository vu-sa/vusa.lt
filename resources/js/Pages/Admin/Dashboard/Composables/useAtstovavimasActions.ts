import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

import { useActionWindow, type ActionWindowInstitutionRef } from '@/Composables/useActionWindow';

export function useAtstovavimasActions(
  accessibleInstitutions: App.Entities.Institution[],
) {
  const actionWindow = useActionWindow();

  /** `is_internal` decides whether the window may offer to announce the meeting. */
  const institutionRef = (
    institution?: { id: string | number; name: string; is_internal?: boolean },
  ): ActionWindowInstitutionRef | null => institution
    ? { id: String(institution.id), name: institution.name, isInternal: institution.is_internal }
    : null;

  // Modal state
  const showAllMeetingModal = ref(false);
  const showAllInstitutionModal = ref(false);
  const showCreateCheckIn = ref<{ open: boolean; institutionId?: string; startDate?: Date; endDate?: Date } | null>(null);
  const showFullscreenGantt = ref(false);
  const fullscreenGanttType = ref<'user' | 'tenant'>('user');

  // Check-in actions
  const handleAddCheckIn = (institutionId: string) => {
    showCreateCheckIn.value = { open: true, institutionId };
  };

  // Check-in creation from Gantt drag selection
  const onGapCreateCheckIn = (payload: { institution_id: string | number; startDate: Date; endDate: Date }) => {
    showCreateCheckIn.value = {
      open: true,
      institutionId: String(payload.institution_id),
      startDate: payload.startDate,
      endDate: payload.endDate,
    };
  };

  // Meeting actions
  const handleScheduleMeeting = (institutionId: string) => {
    const inst = accessibleInstitutions.find(i => i.id === institutionId);
    actionWindow.open({ flow: 'meeting.create', institution: institutionRef(inst) });
  };

  const openMeetingWindow = () => actionWindow.open({ flow: 'meeting.create' });

  // Gantt and meeting creation
  // payload.institutionName is optional - provided when institution is external (not in user's accessible list)
  const onGapCreateMeeting = (payload: { institution_id: string | number; suggestedAt: Date; institutionName?: string }) => {
    const tInstitutions = accessibleInstitutions;
    let inst = tInstitutions.find((i: any) => i && i.id === payload.institution_id);

    // If institution not found in accessible list, create a minimal external institution object
    if (!inst && payload.institutionName) {
      inst = {
        id: String(payload.institution_id),
        name: payload.institutionName,
        isExternal: true, // Flag to indicate this is an external institution
      } as any;
    }

    actionWindow.open({
      flow: 'meeting.create',
      institution: institutionRef(inst),
      suggestedAt: payload.suggestedAt,
    });
  };

  const onGanttFullscreen = (type: 'user' | 'tenant') => {
    fullscreenGanttType.value = type;
    showFullscreenGantt.value = true;
  };

  // Navigation and refresh
  const handleRefresh = (tenantIds: string[] = []) => {
    router.reload({
      only: ['user', 'userInstitutions', 'relatedInstitutions', 'tenantInstitutions', 'mayHaveRelatedInstitutions'],
      data: { tenantIds },
    });
  };

  const handleShowInstitutionDetails = (id: string) => {
    router.visit(route('institutions.show', id));
  };

  return {
    // Modal state
    openMeetingWindow,
    showAllMeetingModal,
    showAllInstitutionModal,
    showCreateCheckIn,
    showFullscreenGantt,
    fullscreenGanttType,

    // Check-in actions
    handleAddCheckIn,
    onGapCreateCheckIn,

    // Meeting actions
    handleScheduleMeeting,

    // Gantt actions
    onGapCreateMeeting,
    onGanttFullscreen,

    // General actions
    handleRefresh,
    handleShowInstitutionDetails,
  };
}
