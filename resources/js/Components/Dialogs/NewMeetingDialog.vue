<template>
  <!-- Dialog mode: wrap content in Dialog -->
  <Dialog v-if="asDialog" :open="showModal" @update:open="handleDialogClose">
    <DialogContent class="sm:max-w-5xl max-h-[90vh] overflow-y-auto p-6 md:p-8 pt-8">
      <DialogHeader class="relative">
        <DialogTitle class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <component :is="Icons.MEETING" class="h-5 w-5" />
            {{ $t('Sukurti susitikimą') }}
          </div>
        </DialogTitle>
        <DialogDescription>
          {{ $t('Sukurkite susitikimą su visa darbotvarkę ir papildoma informacija') }}
        </DialogDescription>
      </DialogHeader>

      <MeetingCreationWizard
        :meeting-creation="meetingCreation"
        :meeting-types="meetingTypes"
        :loading="loading"
        :is-quick-mode="isQuickMode"
        :total-steps="totalSteps"
        @step-change="handleStepChange"
        @institution-select="handleInstitutionSelect"
        @meeting-form-submit="handleMeetingFormSubmit"
        @agenda-items-submit="handleAgendaItemsFormSubmit"
        @final-submit="handleFinalSubmit"
      />
    </DialogContent>
  </Dialog>

  <!-- Inline mode: render content directly with card styling -->
  <div v-else class="bg-white dark:bg-zinc-950 border border-border rounded-lg p-6 md:p-8 max-w-5xl">
    <div class="mb-6">
      <h2 class="flex items-center gap-3 text-lg font-semibold">
        <component :is="Icons.MEETING" class="h-5 w-5" />
        {{ $t('Sukurti susitikimą') }}
      </h2>
      <p class="text-sm text-muted-foreground mt-1">
        {{ $t('Sukurkite susitikimą su visa darbotvarkę ir papildoma informacija') }}
      </p>
    </div>

    <MeetingCreationWizard
      :meeting-creation="meetingCreation"
      :meeting-types="meetingTypes"
      :loading="loading"
      :is-quick-mode="isQuickMode"
      :total-steps="totalSteps"
      @step-change="handleStepChange"
      @institution-select="handleInstitutionSelect"
      @meeting-form-submit="handleMeetingFormSubmit"
      @agenda-items-submit="handleAgendaItemsFormSubmit"
      @final-submit="handleFinalSubmit"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, watch, ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useFetch } from "@vueuse/core";

import { useMeetingCreation } from "@/Composables/useMeetingCreation";
import MeetingCreationWizard from "@/Components/Meetings/MeetingCreationWizard.vue";
import Icons from "@/Types/Icons/filled";
// Import Shadcn components
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";

const emit = defineEmits<(e: 'close') => void>();

const props = withDefaults(defineProps<{
  institution?: App.Entities.Institution;
  showModal: boolean;
  suggestedAt?: Date | string;
  /** When true, renders as a dialog/modal. When false, renders inline with card styling. */
  asDialog?: boolean;
}>(), {
  asDialog: true
});

// Meeting types state
const meetingTypes = ref<Array<{ id: number, title: string, model_type: string }>>([]);
const isLoadingTypes = ref(true);

// Initialize meeting creation composable
const meetingCreation = useMeetingCreation({
  preSelectedInstitution: props.institution,
  mode: 'detailed',
  onSuccess: (meeting) => {
    // Navigate to the created meeting and close modal
    emit('close');
  },
  onError: (errors) => {
    console.error('Meeting creation failed:', errors);
  }
});

// If a suggested date is passed, seed the meeting start_time once modal is shown
const seedFromProps = () => {
  if (!props.showModal) return

  if (props.institution) {
    meetingCreation.updateInstitution(props.institution)
    // Auto-navigate to step 2 when institution is provided
    meetingCreation.goToStep(2)
  }

  if (props.suggestedAt) {
    const dt = typeof props.suggestedAt === 'string' ? new Date(props.suggestedAt) : props.suggestedAt
    if (!Number.isNaN(dt?.getTime?.())) {
      meetingCreation.updateMeetingData({ start_time: dt.toISOString?.() ?? String(dt) })
      // Avoid showing early validation errors before user interacts
      meetingCreation.clearAllErrors()
    }
  }
}

watch([() => props.showModal, () => props.institution, () => props.suggestedAt], () => {
  seedFromProps()
}, { immediate: true })

// Computed properties
// Force detailed flow only
const isQuickMode = computed(() => false);
const totalSteps = computed(() => 4);
const loading = computed(() => meetingCreation.state.loading.submission);

// Methods
const handleDialogClose = () => {
  if (!loading.value) {
    emit('close');
  }
};

// Handle stepper step changes
const handleStepChange = (step: number) => {
  // Allow navigation to any step that's already been completed or the next available step
  if (step <= meetingCreation.state.maxCompletedStep + 1) {
    meetingCreation.goToStep(step);
  }
};

const handleInstitutionSelect = (institutionId: string) => {
  // Find the full institution object
  const duties = usePage().props.auth?.user?.current_duties || [];
  const duty = duties.find((d: any) => String(d.institution?.id) === String(institutionId));
  let institution = duty?.institution;

  // Fallback for admins/all-scope: search providedTenant institutions
  if (!institution) {
    const provided = (usePage().props as any)?.providedTenant?.institutions || [];
    institution = provided.find((i: any) => String(i?.id) === String(institutionId));
  }

  if (institution) {
    meetingCreation.updateInstitution(institution);
    meetingCreation.nextStep();
  }
};

const handleMeetingFormSubmit = (meetingData: any) => {
  meetingCreation.updateMeetingData(meetingData);

  meetingCreation.nextStep(); // Go to agenda step
};

const handleAgendaItemsFormSubmit = (agendaData: any) => {
  const titles = agendaData.agendaItemTitles || [];
  const broughtByStudentsFlags = agendaData.broughtByStudentsFlags || [];
  
  const agendaItems = titles.map((title: string, index: number) => ({
    title,
    description: '',
    order: index + 1,
    brought_by_students: broughtByStudentsFlags[index] || false,
  }));

  meetingCreation.updateAgendaItems(agendaItems);
  meetingCreation.nextStep(); // Go to review step
};

const handleFinalSubmit = () => {
  meetingCreation.submitMeeting();
};

// Fetch meeting types
const fetchMeetingTypes = async () => {
  try {
    isLoadingTypes.value = true;
    const { data, error } = await useFetch(route("api.v1.types.index"), { immediate: true }).get().json()
    if (error.value) throw error.value

    // Handle standardized API response format
    const responseData = data.value?.success ? data.value.data : data.value;
    // Ensure data is an array before filtering
    const typesData = Array.isArray(responseData) ? responseData : [];
    meetingTypes.value = typesData.filter((type: any) => type.model_type === "App\\Models\\Meeting");
  } catch (error) {
    console.error('Failed to fetch meeting types:', error);
    meetingTypes.value = [];
  } finally {
    isLoadingTypes.value = false;
  }
};

// Lifecycle
onMounted(() => {
  fetchMeetingTypes();
});
</script>
