<template>
  <NButton size="small" @click="showMeetingForm = true"
    ><template #icon
      ><NIcon :component="PeopleTeamAdd24Filled"></NIcon></template
    >Pranešti?</NButton
  >
  <CardModal
    v-model:show="showMeetingForm"
    display-directive="show"
    class="prose prose-sm max-w-xl transition dark:prose-invert"
    :title="`${$t('Pranešti apie artėjantį posėdį')}`"
    @close="showMeetingForm = false"
  >
    <!-- <template #header-extra>
      <NButton v-if="current === 1" text
        ><template #icon
          ><NIcon size="20" :component="PuzzlePiece20Regular" /></template
      ></NButton>
    </template> -->
    <NSteps class="mb-8" :current="(current as number)" :status="currentStatus">
      <NStep title="Sukurk posėdį">
        <template #icon>
          <NIcon :component="Icons.MEETING"></NIcon>
        </template>
      </NStep>
      <NStep title="Įrašyk klausimus">
        <template #icon>
          <NIcon :component="Icons.MATTER"></NIcon>
        </template>
      </NStep>
    </NSteps>
    <FadeTransition mode="out-in">
      <MeetingForm
        v-if="current === 1"
        :meeting="meetingTemplate"
        @submit="handleMeetingFormSubmit"
      ></MeetingForm>
      <AgendaItemsForm
        v-else-if="current === 2"
        :loading="loading"
        @submit="handleAgendaItemsFormSubmit"
      />
    </FadeTransition>
    <div class="absolute bottom-8 right-12">
      <FadeTransition>
        <NButton
          v-if="!showAlert && current === 2"
          type="tertiary"
          color="#bbbbbb"
          text
          @click="showAlert = true"
          ><template #icon
            ><NIcon size="48" :component="Question24Regular" /></template
        ></NButton>
      </FadeTransition>
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { NButton, NIcon, NStep, NSteps } from "naive-ui";
import { PeopleTeamAdd24Filled, Question24Regular } from "@vicons/fluent";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import { useStorage } from "@vueuse/core";

import AgendaItemsForm from "@/Components/AdminForms/Special/AgendaItemsForm.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Icons from "@/Types/Icons/regular";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";

const props = defineProps<{
  institution: App.Entities.Institution;
}>();

const showMeetingForm = ref(false);
const loading = ref(false);
const current = ref(1);
const currentStatus = ref<"process">("process");
const showAlert = useStorage("new-meeting-button-alert", true);

const meetingForm = ref<Record<string, any> | null>(null);
const agendaItemsForm = ref<Record<string, any> | null>(null);

const meetingTemplate = {
  institution_id: props.institution.id,
  start_time: null,
};

const handleMeetingFormSubmit = (meeting: Record<string, any>) => {
  meetingForm.value = meeting;
  current.value = 2;
};

const handleAgendaItemsFormSubmit = (agendaItems: Record<string, any>) => {
  loading.value = true;
  agendaItemsForm.value = agendaItems;
  // submit both items
  // submit meeting
  if (!meetingForm.value) {
    return;
  }

  let meetingFormToSubmit = useForm<Record<string, any>>(meetingForm.value);
  let agendaItemsFormToSubmit = useForm<Record<string, any>>(
    agendaItemsForm.value
  );

  console.log(meetingFormToSubmit, agendaItemsFormToSubmit);

  // submit meeting
  meetingFormToSubmit
    .transform((data) => ({
      ...data,
      // add institution_id
      institution_id: props.institution.id,
    }))
    .post(route("meetings.store"), {
      // after success, submit agenda items
      onSuccess: (page) => {
        agendaItemsFormToSubmit
          .transform((data) => ({
            ...data,
            meeting_id: page.props.flash.data.id,
          }))
          .post(route("agendaItems.store"), {
            onSuccess: () => {
              loading.value = false;
              showMeetingForm.value = false;
              meetingForm.value = null;
              agendaItemsForm.value = null;
            },
            preserveScroll: true,
          });
      },
      preserveScroll: true,
    });
};
</script>
