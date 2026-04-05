import { ref, reactive, computed, watch, readonly } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import type { MeetingTypeValue } from '@/Types/MeetingType';

export interface MeetingFormData {
  institution_id: string;
  start_time: string;
  type: MeetingTypeValue;
  description?: string;
}

export interface AgendaItemFormData {
  title: string;
  description?: string;
  order: number;
  brought_by_students?: boolean;
}

export interface MeetingCreationState {
  mode: 'quick' | 'detailed';
  currentStep: number;
  maxCompletedStep: number;
  isOpen: boolean;
  institution?: App.Entities.Institution;
  meeting: Partial<MeetingFormData>;
  agendaItems: AgendaItemFormData[];
  errors: Record<string, string[]>;
  loading: {
    submission: boolean;
    validation: boolean;
    templates: boolean;
  };
  validation: {
    institution: boolean;
    meeting: boolean;
    agenda: boolean;
    canProceed: boolean;
  };
}

export interface UseMeetingCreationOptions {
  preSelectedInstitution?: App.Entities.Institution;
  mode?: 'quick' | 'detailed';
  onSuccess?: (meeting: any) => void;
  onError?: (errors: any) => void;
}

export function useMeetingCreation(options: UseMeetingCreationOptions = {}) {
  // Initialize state
  const state = reactive<MeetingCreationState>({
    mode: options.mode || 'detailed',
    currentStep: options.preSelectedInstitution ? 2 : 1,
    maxCompletedStep: 0,
    isOpen: false,
    institution: options.preSelectedInstitution,
    meeting: {
      institution_id: options.preSelectedInstitution?.id || '',
      start_time: '',
      type: undefined, // Don't preselect any meeting type
      description: '',
    },
    agendaItems: [],
    errors: {},
    loading: {
      submission: false,
      validation: false,
      templates: false,
    },
    validation: {
      institution: !!options.preSelectedInstitution,
      meeting: false,
      agenda: false,
      canProceed: false,
    },
  });

  // Computed properties
  const isQuickMode = computed(() => state.mode === 'quick');
  const totalSteps = computed(() => isQuickMode.value ? 3 : 4); // Institution, Meeting, (Agenda), Review
  const currentStepValid = computed(() => {
    switch (state.currentStep) {
      case 1: return state.validation.institution;
      case 2: return state.validation.meeting;
      case 3: return isQuickMode.value ? true : state.validation.agenda;
      case 4: return true; // Review step
      default: return false;
    }
  });

  const canProceedToNext = computed(() => {
    return currentStepValid.value && !state.loading.submission;
  });

  const canGoToPrevious = computed(() => {
    return state.currentStep > 1 && !state.loading.submission;
  });

  // Step management
  const goToStep = (step: number) => {
    if (step < 1 || step > totalSteps.value) return;
    if (step > state.maxCompletedStep + 1) return; // Can't skip ahead too far

    state.currentStep = step;
    clearStepErrors(step);
  };

  const nextStep = () => {
    if (!canProceedToNext.value) return;

    if (state.currentStep > state.maxCompletedStep) {
      state.maxCompletedStep = state.currentStep;
    }

    if (state.currentStep < totalSteps.value) {
      state.currentStep++;
    }
    else {
      // Final step - submit
      submitMeeting();
    }
  };

  const previousStep = () => {
    if (canGoToPrevious.value) {
      state.currentStep--;
    }
  };

  // Validation methods
  const validateInstitution = (institutionId: string): boolean => {
    clearStepErrors(1);

    if (!institutionId) {
      setStepError(1, 'institution_id', [$t('Institucija yra privaloma')]);
      state.validation.institution = false;
      return false;
    }

    state.validation.institution = true;
    return true;
  };

  const validateMeeting = (meetingData: Partial<MeetingFormData>): boolean => {
    clearStepErrors(2);
    let isValid = true;

    if (!meetingData.start_time) {
      setStepError(2, 'start_time', [$t('Data ir laikas yra privalomi')]);
      isValid = false;
    }

    // type is optional (null = Other), no validation needed

    // Check for scheduling conflicts
    if (meetingData.start_time && state.institution) {
      const conflicts = checkScheduleConflicts(meetingData.start_time, state.institution.id);
      if (conflicts.length > 0) {
        setStepError(2, 'start_time', [$t('Šiuo laiku jau yra suplanuotas susitikimas')]);
        isValid = false;
      }
    }

    state.validation.meeting = isValid;
    return isValid;
  };

  const validateAgenda = (agendaItems: AgendaItemFormData[]): boolean => {
    clearStepErrors(3);

    // Agenda is optional in detailed mode, always valid in quick mode
    if (isQuickMode.value) {
      state.validation.agenda = true;
      return true;
    }

    // Check for empty agenda items
    const hasEmptyItems = agendaItems.some(item => !item.title.trim());
    if (hasEmptyItems) {
      setStepError(3, 'agendaItems', [$t('Klausimas negali būti tuščias')]);
      state.validation.agenda = false;
      return false;
    }

    state.validation.agenda = true;
    return true;
  };

  // Error management
  const clearStepErrors = (step: number) => {
    const stepKeys = {
      1: ['institution_id'],
      2: ['start_time', 'type', 'description'],
      3: ['agendaItems'],
      4: [],
    };

    const keysToReset = stepKeys[step as keyof typeof stepKeys] || [];
    keysToReset.forEach((key) => {
      delete state.errors[key];
    });
  };

  const setStepError = (step: number, field: string, errors: string[]) => {
    state.errors[field] = errors;
  };

  const clearAllErrors = () => {
    state.errors = {};
  };

  // Schedule conflict checking
  const checkScheduleConflicts = (startTime: string, institutionId: string): any[] => {
    // This would normally make an API call to check conflicts
    // For now, return empty array (no conflicts)
    return [];
  };

  // Form data management
  const updateInstitution = (institution: App.Entities.Institution) => {
    state.institution = institution;
    state.meeting.institution_id = institution.id;

    if (validateInstitution(institution.id)) {
      // Mark step 1 as completed so we can navigate to step 2
      if (state.maxCompletedStep < 1) {
        state.maxCompletedStep = 1;
      }
      // Auto-advance in quick mode if this completes the form
      if (isQuickMode.value && state.validation.meeting) {
        nextStep();
      }
    }
  };

  const updateMeetingData = (meetingData: Partial<MeetingFormData>) => {
    Object.assign(state.meeting, meetingData);

    if (validateMeeting(state.meeting as MeetingFormData)) {
      // Auto-advance if in quick mode
      if (isQuickMode.value) {
        nextStep();
      }
    }
  };

  const updateAgendaItems = (items: AgendaItemFormData[]) => {
    state.agendaItems = items;
    validateAgenda(items);
  };

  // Mode switching
  const switchMode = (newMode: 'quick' | 'detailed') => {
    state.mode = newMode;

    // Adjust current step if necessary
    if (newMode === 'quick' && state.currentStep > 3) {
      state.currentStep = 3;
    }

    // Revalidate current step
    switch (state.currentStep) {
      case 3:
        validateAgenda(state.agendaItems);
        break;
    }
  };

  // Form submission
  const submitMeeting = async () => {
    if (state.loading.submission) return;

    state.loading.submission = true;
    clearAllErrors();

    try {
      // Prepare submission data
      const submissionData = {
        ...state.meeting,
        agendaItems: state.agendaItems.length > 0 ? state.agendaItems : undefined,
      };

      // Use Inertia router for proper form handling
      router.post(route('meetings.store'), submissionData, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          // Success callback - meeting creation succeeded
          options.onSuccess?.(submissionData);

          // Reset form after successful creation
          resetForm();
          state.loading.submission = false;
        },
        onError: (errors) => {
          handleSubmissionErrors({ errors });
          options.onError?.(errors);
          state.loading.submission = false;
        },
      });
    }
    catch (error) {
      console.error('Meeting creation failed:', error);
      state.errors.general = [$t('Nepavyko sukurti susitikimo. Bandykite dar kartą.')];
      options.onError?.(error);
      state.loading.submission = false;
    }
  };

  const handleSubmissionErrors = (errorData: any) => {
    if (errorData.errors) {
      Object.assign(state.errors, errorData.errors);

      // Go back to the step with errors
      if (errorData.errors.institution_id) {
        state.currentStep = 1;
      }
      else if (errorData.errors.start_time || errorData.errors.type_id) {
        state.currentStep = 2;
      }
      else if (errorData.errors.agendaItems) {
        state.currentStep = 3;
      }
    }
    else {
      state.errors.general = [errorData.message || $t('Nepavyko sukurti susitikimo')];
    }
  };

  // Form management
  const resetForm = () => {
    state.currentStep = options.preSelectedInstitution ? 2 : 1;
    state.maxCompletedStep = 0;
    state.institution = options.preSelectedInstitution;
    state.meeting = {
      institution_id: options.preSelectedInstitution?.id || '',
      start_time: '',
      type_id: 0,
      description: '',
    };
    state.agendaItems = [];
    state.errors = {};
    state.validation = {
      institution: !!options.preSelectedInstitution,
      meeting: false,
      agenda: false,
      canProceed: false,
    };
  };

  const openModal = () => {
    state.isOpen = true;
  };

  const closeModal = () => {
    state.isOpen = false;
    // Don't reset form immediately, allow user to reopen and continue
  };

  // Watch for validation state changes
  watch(
    () => [state.validation.institution, state.validation.meeting, state.validation.agenda],
    () => {
      state.validation.canProceed = currentStepValid.value;
    },
    { deep: true },
  );

  return {
    // State
    state: readonly(state),

    // Computed
    isQuickMode,
    totalSteps,
    currentStepValid,
    canProceedToNext,
    canGoToPrevious,

    // Step management
    goToStep,
    nextStep,
    previousStep,

    // Data updates
    updateInstitution,
    updateMeetingData,
    updateAgendaItems,

    // Mode switching
    switchMode,

    // Form management
    submitMeeting,
    resetForm,
    openModal,
    closeModal,

    // Validation
    validateInstitution,
    validateMeeting,
    validateAgenda,

    // Utilities
    clearAllErrors,
  };
}
