<template>
  <NForm ref="formRef" :rules="rules" :model="meetingForm">
    <NGrid cols="2">
      <!-- <NFormItemGi label="Veiklos pavadinimas" path="title" required :span="2">
        <NSelect
          v-model:value="meetingForm.title"
          placeholder="Susitikimas su studentais"
          filterable
          tag
          :options="meetingOptions"
          ><template #action>
            <span
              class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
              >Gali įrašyti ir savo veiklą...</span
            >
          </template></NSelect
        >
      </NFormItemGi> -->
      <NFormItemGi label="Data" :span="2" path="start_time" required>
        <NDatePicker
          v-model:value="meetingForm.start_time"
          :first-day-of-week="0"
          :format="'yyyy-MM-dd HH:mm'"
          type="datetime"
          placeholder="Kada vyksta posėdis?"
          clearable
          :actions="['confirm']"
        />
      </NFormItemGi>
      <!-- <NFormItemGi
        v-if="meetingTypes"
        label="Tipas"
        path="meeting_type_id"
        required
      ></NFormItemGi> -->
      <!-- <NFormItemGi label="Statusas" path="status" required :span="2">
        <NRadioGroup v-model:value="meetingForm.status">
          <NRadio
            v-for="status in meetingStatusOptions"
            :key="status.value"
            :value="status.value"
            ><StatusTag :status="status.label"></StatusTag
          ></NRadio>
        </NRadioGroup>
      </NFormItemGi> -->

      <NFormItemGi :span="2" :show-label="false"
        ><NButton type="primary" @click="upsertMeeting"
          >Sukurti</NButton
        ></NFormItemGi
      >
    </NGrid>
  </NForm>
</template>

<script setup lang="ts">
import {
  type FormInst,
  NButton,
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NRadio,
  NRadioGroup,
  NSelect,
} from "naive-ui";
import { type InertiaForm, useForm } from "@inertiajs/inertia-vue3";
import { Method } from "@inertiajs/inertia";
import { ref } from "vue";

// import { meetingOptions, meetingStatusOptions } from "@/Composables/someTypes";
import StatusTag from "@/Components/Tags/StatusTag.vue";

const emit = defineEmits(["success"]);

const props = defineProps<{
  institution?: App.Entities.Institution;
  meeting: App.Entities.Meeting;
  // meetingTypes?: any;
  modelRoute: string;
  matter?: App.Entities.Matter;
  // This question form is from a quick action button, idk if it shouldn't be refactored
  mattersForm?: InertiaForm<Record<string, any>>;
}>();

const showModal = ref(false);
const meetingForm = useForm(props.meeting);
const formRef = ref<FormInst | null>(null);

const rules = {
  // title: {
  //   required: true,
  //   trigger: ["blur"],
  // },
  start_time: {
    required: true,
    trigger: ["blur"],
    message: "Posėdžio laikas yra privalomas",
    type: "number",
  },
  // type_id: {
  //   required: true,
  //   trigger: ["blur"],
  // },
};

const upsertMeeting = () => {
  const isPatch = props.modelRoute === "meetings.update";

  formRef.value?.validate((errors) => {
    if (errors) {
      /* empty */
    } else {
      meetingForm.transform((data) => ({
        ...data,
        institution_id: props.institution?.id ?? undefined,
        matter_id: props.matter?.id,
        mattersForm: props.mattersForm?.data(),
      }));

      meetingForm.submit(
        isPatch ? Method.PATCH : Method.POST,
        route(props.modelRoute, isPatch ? props.meeting.id : undefined),
        {
          onSuccess: () => {
            showModal.value = false;
            emit("success");
          },
        }
      );
    }
  });
};
</script>
