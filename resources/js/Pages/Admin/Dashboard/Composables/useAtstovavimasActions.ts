import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { ModalState } from '../types';

export function useAtstovavimosActions(
  accessibleInstitutions: App.Entities.Institution[]
) {
  // Modal state
  const showMeetingModal = ref(false);
  const showAllMeetingModal = ref(false);
  const showAllInstitutionModal = ref(false);
  const showCreateCheckIn = ref<{ open: boolean; institutionId?: string } | null>(null);
  const showFullscreenGantt = ref(false);
  const fullscreenGanttType = ref<'user' | 'tenant'>('user');
  const selectedInstitution = ref<any | undefined>(undefined);
  const selectedSuggestedAt = ref<Date | undefined>(undefined);

  // Check-in actions
  const handleCheckInConfirm = (checkInId: string) => {
    router.post(route('check-ins.confirm', checkInId), {}, {
      preserveScroll: true,
      only: ['user']
    });
  };

  const handleAddCheckIn = (institutionId: string) => {
    showCreateCheckIn.value = { open: true, institutionId };
  };

  const handleAddFirstCheckIn = (institutions: any[]) => {
    const institutionNeedingAttention = institutions.find(inst => 
      !inst.active_check_in && 
      (!Array.isArray(inst.meetings) || !inst.meetings.some((meeting: any) => 
        new Date(meeting.start_time) > new Date()
      ))
    );
    
    if (institutionNeedingAttention) {
      handleAddCheckIn(institutionNeedingAttention.id);
    }
  };

  // Meeting actions
  const handleScheduleMeeting = (institutionId: string) => {
    const inst = accessibleInstitutions.find(i => i.id === institutionId);
    selectedInstitution.value = inst;
    showMeetingModal.value = true;
  };

  // Check-in dispute and resolution actions
  const onDispute = (id: string) => {
    const reason = window.prompt('Įveskite priežastį');
    if (reason === null) return;
    router.post(route('check-ins.dispute', id), { reason }, { preserveScroll: true });
  };

  const onResolve = (id: string, resolution: 'keep' | 'withdraw') => {
    router.post(route('check-ins.resolve', id), { resolution }, { preserveScroll: true });
  };

  const onSuppress = (id: string) => {
    const reason = window.prompt('Įveskite priežastį');
    if (!reason) return;
    router.post(route('check-ins.suppress', id), { reason }, { preserveScroll: true });
  };

  // Gantt and meeting creation
  const onGapCreateMeeting = (payload: { institution_id: string | number, suggestedAt: Date }) => {
    const tInstitutions = accessibleInstitutions;
    const inst = tInstitutions.find((i: any) => i && i.id === payload.institution_id);
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
  const handleRefresh = () => {
    router.reload({ only: ['user'] });
  };

  const handleShowInstitutionDetails = (id: string) => {
    router.visit(route('institutions.show', id));
  };

  // Check-in state helpers
  const stateInfo = (state: string) => {
    const map: Record<string, { text: string; color: string }> = {
      'App\\States\\InstitutionCheckIns\\Active': { text: 'Aktyvi', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' },
      'App\\States\\InstitutionCheckIns\\Expired': { text: 'Nebegalioja', color: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400' },
      'App\\States\\InstitutionCheckIns\\Invalidated': { text: 'Nebegalioja (yra posėdis)', color: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' },
      'App\\States\\InstitutionCheckIns\\Withdrawn': { text: 'Atšaukta', color: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400' },
      'App\\States\\InstitutionCheckIns\\Disputed': { text: 'Ginčijama', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' },
      'App\\States\\InstitutionCheckIns\\AdminSuppressed': { text: 'Slopinama', color: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' },
    };
    return map[state] ?? { text: state?.split('\\').pop() ?? 'Unknown', color: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300' };
  };

  const isActive = (state: string) => state === 'App\\States\\InstitutionCheckIns\\Active';
  const isDisputed = (state: string) => state === 'App\\States\\InstitutionCheckIns\\Disputed';
  const isSuppressed = (state: string) => state === 'App\\States\\InstitutionCheckIns\\AdminSuppressed';
  const canDispute = (state: string) => isActive(state);

  // Get modal state for serialization
  const getModalState = (): ModalState => ({
    showMeetingModal: showMeetingModal.value,
    showAllMeetingModal: showAllMeetingModal.value,
    showAllInstitutionModal: showAllInstitutionModal.value,
    showCreateCheckIn: showCreateCheckIn.value,
    showFullscreenGantt: showFullscreenGantt.value,
    fullscreenGanttType: fullscreenGanttType.value,
    selectedInstitution: selectedInstitution.value,
    selectedSuggestedAt: selectedSuggestedAt.value
  });

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
    handleCheckInConfirm,
    handleAddCheckIn,
    handleAddFirstCheckIn,

    // Meeting actions
    handleScheduleMeeting,

    // Check-in state actions
    onDispute,
    onResolve,
    onSuppress,

    // Gantt actions
    onGapCreateMeeting,
    onCloseMeetingModal,
    onGanttFullscreen,

    // General actions
    handleRefresh,
    handleShowInstitutionDetails,

    // Helper functions
    stateInfo,
    isActive,
    isDisputed,
    isSuppressed,
    canDispute,
    getModalState
  };
}
