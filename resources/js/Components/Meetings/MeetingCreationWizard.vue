<template>
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
          :model-value="meetingCreation.state.currentStep"
          @update:model-value="(step: number | undefined) => step !== undefined && emit('stepChange', step)"
        >
          <!-- Step 1: Institution -->
          <StepperItem
            :step="1"
            v-slot="{ state }"
            class="relative flex w-full items-start gap-4"
            :disabled="loading"
          >
            <StepperSeparator
              class="absolute left-[17px] top-[38px] block h-[calc(100%+16px)] w-0.5 shrink-0 rounded-full"
              :class="state === 'completed' ? 'bg-green-500' : 'bg-border'"
            />

            <StepperTrigger as-child>
              <button
                type="button"
                class="z-10 size-9 rounded-full shrink-0 flex items-center justify-center border-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:cursor-not-allowed"
                :class="[
                  state === 'completed' && 'bg-green-50 border-green-500 text-green-600 dark:bg-green-950 dark:border-green-500 dark:text-green-400',
                  state === 'active' && 'bg-primary border-primary text-primary-foreground ring-2 ring-ring ring-offset-2 ring-offset-background',
                  state === 'inactive' && 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-700 text-muted-foreground'
                ]"
                :disabled="loading"
              >
                <CheckCircle v-if="state === 'completed'" class="size-4" />
                <component :is="Icons.INSTITUTION" v-else class="size-4" />
              </button>
            </StepperTrigger>

            <div class="flex flex-col gap-0.5 pt-1">
              <StepperTitle
                :class="[state === 'active' && 'text-primary']"
                class="text-sm font-semibold leading-tight"
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
            class="relative flex w-full items-start gap-4"
            :disabled="loading || (2 > meetingCreation.state.maxCompletedStep + 1 && meetingCreation.state.currentStep < 2)"
          >
            <StepperSeparator
              class="absolute left-[17px] top-[38px] block h-[calc(100%+16px)] w-0.5 shrink-0 rounded-full"
              :class="state === 'completed' ? 'bg-green-500' : 'bg-border'"
            />

            <StepperTrigger as-child>
              <button
                type="button"
                class="z-10 size-9 rounded-full shrink-0 flex items-center justify-center border-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:cursor-not-allowed"
                :class="[
                  state === 'completed' && 'bg-green-50 border-green-500 text-green-600 dark:bg-green-950 dark:border-green-500 dark:text-green-400',
                  state === 'active' && 'bg-primary border-primary text-primary-foreground ring-2 ring-ring ring-offset-2 ring-offset-background',
                  state === 'inactive' && 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-700 text-muted-foreground'
                ]"
                :disabled="loading || (2 > meetingCreation.state.maxCompletedStep + 1 && meetingCreation.state.currentStep < 2)"
              >
                <CheckCircle v-if="state === 'completed'" class="size-4" />
                <component :is="Icons.MEETING" v-else class="size-4" />
              </button>
            </StepperTrigger>

            <div class="flex flex-col gap-0.5 pt-1">
              <StepperTitle
                :class="[state === 'active' && 'text-primary']"
                class="text-sm font-semibold leading-tight"
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
            class="relative flex w-full items-start gap-4"
            :disabled="loading || (3 > meetingCreation.state.maxCompletedStep + 1 && meetingCreation.state.currentStep < 3)"
          >
            <StepperSeparator
              class="absolute left-[17px] top-[38px] block h-[calc(100%+16px)] w-0.5 shrink-0 rounded-full"
              :class="state === 'completed' ? 'bg-green-500' : 'bg-border'"
            />

            <StepperTrigger as-child>
              <button
                type="button"
                class="z-10 size-9 rounded-full shrink-0 flex items-center justify-center border-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:cursor-not-allowed"
                :class="[
                  state === 'completed' && 'bg-green-50 border-green-500 text-green-600 dark:bg-green-950 dark:border-green-500 dark:text-green-400',
                  state === 'active' && 'bg-primary border-primary text-primary-foreground ring-2 ring-ring ring-offset-2 ring-offset-background',
                  state === 'inactive' && 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-700 text-muted-foreground'
                ]"
                :disabled="loading || (3 > meetingCreation.state.maxCompletedStep + 1 && meetingCreation.state.currentStep < 3)"
              >
                <CheckCircle v-if="state === 'completed'" class="size-4" />
                <component :is="Icons.AGENDA_ITEM" v-else class="size-4" />
              </button>
            </StepperTrigger>

            <div class="flex flex-col gap-0.5 pt-1">
              <StepperTitle
                :class="[state === 'active' && 'text-primary']"
                class="text-sm font-semibold leading-tight"
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
            class="relative flex w-full items-start gap-4"
            :disabled="loading || (4 > meetingCreation.state.maxCompletedStep + 1 && meetingCreation.state.currentStep < 4)"
          >
            <StepperTrigger as-child>
              <button
                type="button"
                class="z-10 size-9 rounded-full shrink-0 flex items-center justify-center border-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:cursor-not-allowed"
                :class="[
                  state === 'completed' && 'bg-green-50 border-green-500 text-green-600 dark:bg-green-950 dark:border-green-500 dark:text-green-400',
                  state === 'active' && 'bg-primary border-primary text-primary-foreground ring-2 ring-ring ring-offset-2 ring-offset-background',
                  state === 'inactive' && 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-700 text-muted-foreground'
                ]"
                :disabled="loading || (4 > meetingCreation.state.maxCompletedStep + 1 && meetingCreation.state.currentStep < 4)"
              >
                <CheckCircle class="size-4" />
              </button>
            </StepperTrigger>

            <div class="flex flex-col gap-0.5 pt-1">
              <StepperTitle
                :class="[state === 'active' && 'text-primary']"
                class="text-sm font-semibold leading-tight"
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
            :selected-institution="meetingCreation.state.institution" @submit="(id) => emit('institutionSelect', id)" />
          <MeetingDetailsForm v-else-if="meetingCreation.state.currentStep === 2"
            :meeting="meetingCreation.state.meeting" :institution-id="meetingCreation.state.institution?.id"
            :loading="meetingCreation.state.loading.validation" :meeting-types @submit="(data) => emit('meetingFormSubmit', data)" />
          <AgendaItemsForm v-else-if="meetingCreation.state.currentStep === 3"
            :loading="meetingCreation.state.loading.submission"
            :institution-id="meetingCreation.state.institution?.id"
            :agenda-items="meetingCreation.state.agendaItems" 
            :show-hint="false"
            @submit="(data) => emit('agendaItemsSubmit', data)" />
          <MeetingReviewForm v-else-if="meetingCreation.state.currentStep === 4"
            :loading="meetingCreation.state.loading.submission" :meeting-state="meetingCreation.state"
            @edit-step="meetingCreation.goToStep" @back="meetingCreation.previousStep"
            @submit="() => emit('finalSubmit')" />
        </FadeTransition>
        <template #fallback>
          <div class="flex items-center justify-center h-64">
            <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
          </div>
        </template>
      </Suspense>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { CheckCircle, Loader2 } from "lucide-vue-next";

import { useMeetingCreation } from "@/Composables/useMeetingCreation";
import AgendaItemsForm from "@/Components/AdminForms/Special/AgendaItemsForm.vue";
import MeetingDetailsForm from "@/Components/AdminForms/MeetingDetailsForm.vue";
import MeetingReviewForm from "@/Components/AdminForms/MeetingReviewForm.vue";
import InstitutionSelectorForm from "@/Components/AdminForms/Special/InstitutionSelectorForm.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue"
import Icons from "@/Types/Icons/filled";
import {
  Stepper,
  StepperItem,
  StepperTrigger,
  StepperTitle,
  StepperDescription,
  StepperSeparator,
} from "@/Components/ui/stepper";

const props = defineProps<{
  meetingCreation: ReturnType<typeof useMeetingCreation>;
  meetingTypes: Array<{ id: number, title: string, model_type: string }>;
  loading: boolean;
  isQuickMode: boolean;
  totalSteps: number;
}>();

const emit = defineEmits<{
  (e: 'stepChange', step: number): void;
  (e: 'institutionSelect', institutionId: string): void;
  (e: 'meetingFormSubmit', data: any): void;
  (e: 'agendaItemsSubmit', data: any): void;
  (e: 'finalSubmit'): void;
}>();

// Helper methods for stepper display
const formatMeetingTime = (): string => {
  const startTime = props.meetingCreation.state.meeting.start_time;
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

const getMeetingTypeName = (): string => {
  const typeId = props.meetingCreation.state.meeting.type_id;
  if (!typeId) return '';

  const type = props.meetingTypes.find((t) => t.id === typeId);
  if (type) {
    return type.title;
  }

  return `${$t('Tipas')}: #${typeId}`;
};
</script>
