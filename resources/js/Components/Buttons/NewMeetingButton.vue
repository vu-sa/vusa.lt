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
      <NStep title="Nurodyk posėdžio datą">
        <template #icon>
          <NIcon :component="IconsRegular.MEETING"></NIcon>
        </template>
      </NStep>
      <NStep title="Įrašyk klausimus">
        <template #icon>
          <NIcon :component="IconsRegular.AGENDA_ITEM"></NIcon>
        </template>
      </NStep>
    </NSteps>
    <FadeTransition mode="out-in">
      <MeetingForm
        v-if="current === 1"
        :meeting="meetingTemplateWithId"
        @submit="handleMeetingFormSubmit"
      ></MeetingForm>
      <AgendaItemsForm
        v-else-if="current === 2"
        :loading="loading"
        @submit="handleAgendaItemsFormSubmit"
      />
    </FadeTransition>
    <FadeTransition>
      <ModalHelperButton
        v-if="!showAlert && current === 2"
        @click="showAlert = true"
      />
    </FadeTransition>
  </CardModal>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { NButton, NIcon, NStep, NSteps } from "naive-ui";
import { PeopleTeamAdd24Filled } from "@vicons/fluent";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import { meetingTemplate } from "@/Types/formTemplates";
import AgendaItemsForm from "@/Components/AdminForms/Special/AgendaItemsForm.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import IconsRegular from "@/Types/Icons/regular";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import ModalHelperButton from "./ModalHelperButton.vue";

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

// use meetingTemplate but change institution_id to props.institution.id
const meetingTemplateWithId = {
  ...meetingTemplate,
  institution_id: props.institution.id,
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
        if (page.props.flash.data.id === undefined) {
          return;
        }
        agendaItemsFormToSubmit
          .transform((data) => ({
            ...data,
            meeting_id: page.props.flash.data.id,
          }))
          .post(route("agendaItems.store"), {
            onSuccess: () => {
              showMeetingForm.value = false;
              meetingForm.value = null;
              agendaItemsForm.value = null;
            },
            preserveScroll: true,
          });
      },
      preserveScroll: true,
      onFinish: () => {
        loading.value = false;
      },
    });
};
</script>
