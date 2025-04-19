<template>
  <Dialog :open="showModal" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-4xl">
      <DialogHeader>
        <DialogTitle>{{ $t('Pranešti apie posėdį') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Sukurkite naują posėdį ir pridėkite darbotvarkės punktus') }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="flex flex-col gap-6 md:flex-row">
        <!-- Shadcn Stepper Component -->
        <div class="size-fit md:border-r border-border p-4">
          <Stepper
            v-model="current"
            orientation="vertical"
            class="mx-auto flex w-full max-w-xs flex-col gap-4 items-center justify-center"
          >
            <StepperItem :step="1">
              <StepperTrigger>
                <StepperIndicator>
                  <component :is="IconsRegular.INSTITUTION" class="size-6" />
                </StepperIndicator>
                <div>
                  <StepperTitle>{{ $t('Pasirink instituciją') }}</StepperTitle>
                  <StepperDescription>{{ $t('Pradėkite nuo institucijos pasirinkimo') }}</StepperDescription>
                </div>
              </StepperTrigger>
              <StepperSeparator />
            </StepperItem>

            <StepperItem :step="2">
              <StepperTrigger>
                <StepperIndicator>
                  <component :is="IconsRegular.MEETING" class="size-6" />
                </StepperIndicator>
                <div>
                  <StepperTitle>{{ $t('Nurodyk posėdžio datą') }}</StepperTitle>
                  <StepperDescription>{{ $t('Įveskite posėdžio detales ir datą') }}</StepperDescription>
                </div>
              </StepperTrigger>
              <StepperSeparator />
            </StepperItem>

            <StepperItem :step="3">
              <StepperTrigger>
                <StepperIndicator>
                  <component :is="IconsRegular.AGENDA_ITEM" class="size-6" />
                </StepperIndicator>
                <div>
                  <StepperTitle>{{ $t('Įrašyk darbotvarkės klausimus') }}</StepperTitle>
                  <StepperDescription>{{ $t('Pridėkite darbotvarkės klausimus posėdžiui') }}</StepperDescription>
                </div>
              </StepperTrigger>
            </StepperItem>
          </Stepper>
        </div>

        <!-- Step Content -->
        <div class="flex-1">
          <Suspense>
            <FadeTransition mode="out-in">
              <InstitutionSelectorForm v-if="current === 1" class="flex w-full flex-col items-start justify-center"
                @submit="handleInstitutionSelect" />
              <MeetingForm v-else-if="current === 2" class="flex w-full flex-col items-start justify-center"
                :meeting="meetingTemplate" @submit="handleMeetingFormSubmit" />
              <AgendaItemsForm v-else-if="current === 3" :loading="loading" @submit="handleAgendaItemsFormSubmit" />
            </FadeTransition>
          </Suspense>
        </div>
      </div>

      <DialogFooter>
        <FadeTransition>
          <ModalHelperButton v-if="!showAlert && current === 3" @click="showAlert = true" />
        </FadeTransition>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { ref, watch, reactive, provide, onBeforeUnmount } from "vue";

import { meetingTemplate } from "@/Types/formTemplates";
import { router, useForm } from "@inertiajs/vue3";
import AgendaItemsForm from "@/Components/AdminForms/Special/AgendaItemsForm.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import IconsRegular from "@/Types/Icons/regular";
import InstitutionSelectorForm from "../AdminForms/Special/InstitutionSelectorForm.vue";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import ModalHelperButton from "../Buttons/ModalHelperButton.vue";

// Import Shadcn Dialog and Stepper components
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";

import {
  Stepper,
  StepperDescription,
  StepperIndicator,
  StepperItem,
  StepperSeparator,
  StepperTitle,
  StepperTrigger,
} from "@/Components/ui/stepper";

const emit = defineEmits(["close"]);

const props = defineProps<{
  institution?: App.Entities.Institution;
  showModal: boolean;
}>();

const loading = ref(false);
// Use reactive object to store form data in memory
const formState = reactive({
  institution_id: props.institution?.id || '',
  meetingData: {},
  agendaItemsData: {
    moreAgendaItemsUndefined: false,
    agendaItemTitles: [],
  }
});

// Initialize step based on whether we have an institution already
const current = ref(formState.institution_id ? 2 : 1);
const showAlert = ref(true);

// Provide form state to child components
provide('meetingFormState', formState);

// Inertia form for submission
const meetingAgendaForm = useForm({
  meeting: {},
  agendaItems: [],
});

// Watch for modal close and reset if needed
watch(() => props.showModal, (newVal) => {
  if (!newVal) {
    // Update the form instance with current in-memory data
    meetingAgendaForm.meeting = { ...formState.meetingData };
    meetingAgendaForm.agendaItems = { ...formState.agendaItemsData };
  }
});

// Function to update current step with validation
const updateStep = (step: number) => {
  // Only allow going back in steps or proceeding if data is properly filled
  if (step < current.value) {
    current.value = step;
  }
};

// Clear form data when component is unmounted if form was successfully submitted
const formSubmitted = ref(false);
onBeforeUnmount(() => {
  if (formSubmitted.value) {
    // Reset form state
    formState.institution_id = props.institution?.id || '';
    formState.meetingData = {};
    formState.agendaItemsData = {
      moreAgendaItemsUndefined: false,
      agendaItemTitles: [],
    };
  }
});

const handleInstitutionSelect = (id: string) => {
  formState.institution_id = id;
  current.value = 2;
};

const handleMeetingFormSubmit = (meeting: Record<string, any>) => {
  // Save to in-memory state
  formState.meetingData = meeting;
  // Update form data
  meetingAgendaForm.meeting = meeting;
  current.value = 3;
};

const handleAgendaItemsFormSubmit = (agendaItems: Record<string, any>) => {
  loading.value = true;
  // Save to in-memory state
  formState.agendaItemsData = agendaItems;
  // Update form data
  meetingAgendaForm.agendaItems = agendaItems;

  // Prepare complete form data for submission
  const formData = {
    // Meeting data
    ...formState.meetingData,
    // Add institution_id
    institution_id: formState.institution_id,
    // Agenda items data as a nested property
    agenda_items: {
      moreAgendaItemsUndefined: formState.agendaItemsData.moreAgendaItemsUndefined,
      titles: formState.agendaItemsData.agendaItemTitles
    }
  };

  // Submit everything in a single request
  meetingAgendaForm
    .transform(() => formData)
    .post(route("meetings.store"), {
      onSuccess: (page) => {
        const id = page.props?.flash?.data.id;
        if (id) {
          // Mark as submitted so state gets cleared on unmount
          formSubmitted.value = true;
          // Close modal
          emit("close");
          // Reset current step for next use
          current.value = 1;
          // Reset form
          meetingAgendaForm.reset();
          // Navigate to the new meeting
          router.visit(route("meetings.show", id));
        }
      },
      onError: (errors) => {
        console.error("Error submitting meeting form:", errors);
      },
      onFinish: () => {
        loading.value = false;
      },
      preserveScroll: true,
    });
};
</script>
