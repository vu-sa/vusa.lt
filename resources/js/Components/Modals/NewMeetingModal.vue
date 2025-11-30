<template>
  <Dialog :open="showModal" @update:open="handleDialogClose">
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

      <div class="flex flex-col gap-6 lg:flex-row">
        <!-- Stepper -->
        <div class="lg:w-80 lg:border-r border-border lg:pr-6">
          <div class="space-y-4">
            <div class="flex items-center justify-between text-sm text-muted-foreground mb-2">
              <span>{{ $t('Naujausias žingsnis') }}</span>
              <span>{{ meetingCreation.state.currentStep }}/{{ totalSteps }}</span>
            </div>

            <Stepper
              orientation="vertical"
              :linear="false"
              class="mx-auto flex w-full max-w-md flex-col justify-start gap-4"
              v-model="meetingCreation.state.currentStep"
            >
              <!-- Step 1: Institution -->
              <StepperItem
                :step="1"
                v-slot="{ state }"
                class="relative flex w-full items-start gap-6"
                :disabled="loading"
              >
                <StepperSeparator
                  class="absolute left-[18px] top-[38px] block h-[105%] w-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                />

                <StepperTrigger as-child>
                  <button
                    type="button"
                    class="z-10 rounded-full shrink-0 w-8 h-8 flex items-center justify-center border-2 transition-all"
                    :class="[
                      state === 'completed' || state === 'active'
                        ? 'bg-primary border-primary text-primary-foreground'
                        : 'border-muted-foreground/30 text-muted-foreground',
                      state === 'completed' && 'bg-green-100 border-green-500 text-green-600 dark:bg-green-900 dark:text-green-400'
                    ]"
                  >
                    <CheckCircle v-if="state === 'completed'" class="h-4 w-4" />
                    <component :is="Icons.INSTITUTION" v-else class="h-4 w-4" />
                  </button>
                </StepperTrigger>

                <div class="flex flex-col gap-1 flex-1 min-w-0">
                  <StepperTitle
                    :class="[state === 'active' && 'text-primary']"
                    class="text-sm font-semibold"
                  >
                    {{ $t('Pasirinkite instituciją') }}
                  </StepperTitle>
                  <StepperDescription
                    v-if="meetingCreation.state.institution?.name"
                    :class="[state === 'active' && 'text-primary']"
                    class="text-xs text-muted-foreground truncate"
                  >
                    {{ meetingCreation.state.institution.name }}
                  </StepperDescription>
                </div>
              </StepperItem>

              <!-- Step 2: Meeting Details -->
              <StepperItem
                :step="2"
                v-slot="{ state }"
                class="relative flex w-full items-start gap-6"
                :disabled="loading || 2 > meetingCreation.state.maxCompletedStep + 1"
              >
                <StepperSeparator
                  class="absolute left-[18px] top-[38px] block h-[105%] w-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                />

                <StepperTrigger as-child>
                  <button
                    type="button"
                    class="z-10 rounded-full shrink-0 w-8 h-8 flex items-center justify-center border-2 transition-all"
                    :class="[
                      state === 'completed' || state === 'active'
                        ? 'bg-primary border-primary text-primary-foreground'
                        : 'border-muted-foreground/30 text-muted-foreground',
                      state === 'completed' && 'bg-green-100 border-green-500 text-green-600 dark:bg-green-900 dark:text-green-400'
                    ]"
                  >
                    <CheckCircle v-if="state === 'completed'" class="h-4 w-4" />
                    <component :is="Icons.MEETING" v-else class="h-4 w-4" />
                  </button>
                </StepperTrigger>

                <div class="flex flex-col gap-1 flex-1 min-w-0">
                  <StepperTitle
                    :class="[state === 'active' && 'text-primary']"
                    class="text-sm font-semibold"
                  >
                    {{ $t('Susitikimo detalės') }}
                  </StepperTitle>
                  <StepperDescription
                    v-if="formatMeetingTime()"
                    :class="[state === 'active' && 'text-primary']"
                    class="text-xs text-muted-foreground truncate"
                  >
                    {{ formatMeetingTime() }}
                  </StepperDescription>
                  <StepperDescription
                    v-if="getMeetingTypeName()"
                    :class="[state === 'active' && 'text-primary']"
                    class="text-xs text-muted-foreground truncate"
                  >
                    {{ getMeetingTypeName() }}
                  </StepperDescription>
                </div>
              </StepperItem>

              <!-- Step 3: Agenda -->
              <StepperItem
                v-if="!isQuickMode"
                :step="3"
                v-slot="{ state }"
                class="relative flex w-full items-start gap-6"
                :disabled="loading || 3 > meetingCreation.state.maxCompletedStep + 1"
              >
                <StepperSeparator
                  class="absolute left-[18px] top-[38px] block h-[105%] w-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                />

                <StepperTrigger as-child>
                  <button
                    type="button"
                    class="z-10 rounded-full shrink-0 w-8 h-8 flex items-center justify-center border-2 transition-all"
                    :class="[
                      state === 'completed' || state === 'active'
                        ? 'bg-primary border-primary text-primary-foreground'
                        : 'border-muted-foreground/30 text-muted-foreground',
                      state === 'completed' && 'bg-green-100 border-green-500 text-green-600 dark:bg-green-900 dark:text-green-400'
                    ]"
                  >
                    <CheckCircle v-if="state === 'completed'" class="h-4 w-4" />
                    <component :is="Icons.AGENDA_ITEM" v-else class="h-4 w-4" />
                  </button>
                </StepperTrigger>

                <div class="flex flex-col gap-1 flex-1 min-w-0">
                  <StepperTitle
                    :class="[state === 'active' && 'text-primary']"
                    class="text-sm font-semibold"
                  >
                    {{ $t('Darbotvarkė') }}
                  </StepperTitle>
                  <StepperDescription
                    v-if="meetingCreation.state.agendaItems.length"
                    :class="[state === 'active' && 'text-primary']"
                    class="text-xs text-muted-foreground truncate"
                  >
                    {{ meetingCreation.state.agendaItems.length }} {{ $t('klausimai') }}
                  </StepperDescription>
                </div>
              </StepperItem>

              <!-- Step 4: Review -->
              <StepperItem
                v-if="!isQuickMode"
                :step="4"
                v-slot="{ state }"
                class="relative flex w-full items-start gap-6"
                :disabled="loading || 4 > meetingCreation.state.maxCompletedStep + 1"
              >
                <StepperTrigger as-child>
                  <button
                    type="button"
                    class="z-10 rounded-full shrink-0 w-8 h-8 flex items-center justify-center border-2 transition-all"
                    :class="[
                      state === 'completed' || state === 'active'
                        ? 'bg-primary border-primary text-primary-foreground'
                        : 'border-muted-foreground/30 text-muted-foreground',
                      state === 'completed' && 'bg-green-100 border-green-500 text-green-600 dark:bg-green-900 dark:text-green-400'
                    ]"
                  >
                    <CheckCircle class="h-4 w-4" />
                  </button>
                </StepperTrigger>

                <div class="flex flex-col gap-1 flex-1 min-w-0">
                  <StepperTitle
                    :class="[state === 'active' && 'text-primary']"
                    class="text-sm font-semibold"
                  >
                    {{ $t('Peržiūra') }}
                  </StepperTitle>
                  <StepperDescription
                    :class="[state === 'active' && 'text-primary']"
                    class="text-xs text-muted-foreground truncate"
                  >
                    {{ $t('Patikrinkite informaciją prieš kūrimą') }}
                  </StepperDescription>
                </div>
              </StepperItem>
            </Stepper>
          </div>
        </div>

        <!-- Step Content -->
        <div class="flex-1 min-h-[400px]">
          <Suspense>
            <FadeTransition :key="meetingCreation.state.currentStep" mode="out-in">
              <InstitutionSelectorForm v-if="meetingCreation.state.currentStep === 1"
                :selected-institution="meetingCreation.state.institution" @submit="handleInstitutionSelect" />
              <MeetingDetailsForm v-else-if="meetingCreation.state.currentStep === 2"
                :meeting="meetingCreation.state.meeting" :institution-id="meetingCreation.state.institution?.id"
                :loading="meetingCreation.state.loading.validation" :meeting-types @submit="handleMeetingFormSubmit" />
              <AgendaItemsForm v-else-if="meetingCreation.state.currentStep === 3"
                :loading="meetingCreation.state.loading.submission"
                :institution-id="meetingCreation.state.institution?.id"
                :agenda-items="meetingCreation.state.agendaItems" :recent-meetings="props.recentMeetings"
                @submit="handleAgendaItemsFormSubmit" />
              <MeetingReviewForm v-else-if="meetingCreation.state.currentStep === 4"
                :loading="meetingCreation.state.loading.submission" :meeting-state="meetingCreation.state"
                @edit-step="meetingCreation.goToStep" @back="meetingCreation.previousStep"
                @submit="handleFinalSubmit" />
            </FadeTransition>
            <template #fallback>
              <div class="flex items-center justify-center h-64">
                <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
              </div>
            </template>
          </Suspense>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, watch, ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { CheckCircle, Loader2 } from "lucide-vue-next";
import { useFetch } from "@vueuse/core";

import { useMeetingCreation } from "@/Composables/useMeetingCreation";
import AgendaItemsForm from "@/Components/AdminForms/Special/AgendaItemsForm.vue";
import MeetingDetailsForm from "@/Components/AdminForms/MeetingDetailsForm.vue";
import MeetingReviewForm from "@/Components/AdminForms/MeetingReviewForm.vue";
import InstitutionSelectorForm from "@/Components/AdminForms/Special/InstitutionSelectorForm.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue"
import Icons from "@/Types/Icons/filled";
// Import Shadcn components
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import {
  Stepper,
  StepperItem,
  StepperTrigger,
  StepperIndicator,
  StepperTitle,
  StepperDescription,
  StepperSeparator,
} from "@/Components/ui/stepper";

// Import Lucide icons

const emit = defineEmits<(e: 'close') => void>();

const props = defineProps<{
  institution?: App.Entities.Institution;
  showModal: boolean;
  suggestedAt?: Date | string;
  recentMeetings?: Array<{ id: string; title: string; start_time: string; institution_name: string; agenda_items: { title: string }[] }>;
}>();

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

// Step navigation handled by Shadcn stepper v-model binding

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
  const agendaItems = (agendaData.agendaItemTitles || []).map((title: string, index: number) => ({
    title,
    description: '',
    order: index + 1
  }));

  meetingCreation.updateAgendaItems(agendaItems);
  meetingCreation.nextStep(); // Go to review step
};

const handleFinalSubmit = () => {
  meetingCreation.submitMeeting();
};

// Helper methods for stepper display
const formatMeetingTime = (): string => {
  const startTime = meetingCreation.state.meeting.start_time;
  if (!startTime) return '';

  const date = new Date(startTime);
  return date.toLocaleString(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit'
  });
};

const getAgendaSummary = (): string => {
  const items = meetingCreation.state.agendaItems;
  if (items.length === 0) return '';
  if (items.length === 1) return '1 klausimas';
  return `${items.length} klausimai`;
};

const getMeetingTypeName = (): string => {
  const typeId = meetingCreation.state.meeting.type_id;
  if (!typeId) return '';

  // Use the fetched meeting types
  const type = meetingTypes.value.find((t: any) => t.id === typeId);

  if (type) {
    return type.title;
  }

  // Fallback - show type ID
  return `${$t('Tipas')}: #${typeId}`;
};

// Fetch meeting types
const fetchMeetingTypes = async () => {
  try {
    isLoadingTypes.value = true;
    const { data, error } = await useFetch(route("api.types.index"), { immediate: true }).get().json()
    if (error.value) throw error.value

    // Ensure data is an array before filtering
    const typesData = Array.isArray(data.value) ? data.value : [];
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
