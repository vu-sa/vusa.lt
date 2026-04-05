import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useAtstovavimosActions(
  accessibleInstitutions: App.Entities.Institution[],
) {
  // Modal state
  const showMeetingModal = ref(false);
  const showAllMeetingModal = ref(false);
  const showAllInstitutionModal = ref(false);
  const showCreateCheckIn = ref<{ open: boolean; institutionId?: string; startDate?: Date; endDate?: Date } | null>(null);
  const showFullscreenGantt = ref(false);
  const fullscreenGanttType = ref<'user' | 'tenant'>('user');
  const selectedInstitution = ref<any | undefined>(undefined);
  const selectedSuggestedAt = ref<Date | undefined>(undefined);

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
    selectedInstitution.value = inst;
    showMeetingModal.value = true;
  };

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

    selectedInstitution.value = inst;
    selectedSuggestedAt.value = payload.suggestedAt;
    showMeetingModal.value = true;
  };

  const onCloseMeetingModal = () => {
    showMeetingModal.value = false;
    selectedInstitution.value = undefined;
    selectedSuggestedAt.value = undefined;
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
    showMeetingModal,
    showAllMeetingModal,
    showAllInstitutionModal,
    showCreateCheckIn,
    showFullscreenGantt,
    fullscreenGanttType,
    selectedInstitution,
    selectedSuggestedAt,

    // Check-in actions
    handleAddCheckIn,
    onGapCreateCheckIn,

    // Meeting actions
    handleScheduleMeeting,

    // Gantt actions
    onGapCreateMeeting,
    onCloseMeetingModal,
    onGanttFullscreen,

    // General actions
    handleRefresh,
    handleShowInstitutionDetails,
  };
}
