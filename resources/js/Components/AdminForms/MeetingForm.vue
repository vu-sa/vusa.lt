<template>
  <NForm ref="formRef" :rules="rules" :model="meetingForm">
    <NFormItem path="start_time" required>
      <template #label>
        <span class="inline-flex items-center gap-1"
          ><NIcon :component="Icons.DATE"></NIcon> <span>Data</span></span
        >
      </template>
      <NDatePicker
        v-model:value="meetingForm.start_time"
        :first-day-of-week="0"
        :format="'yyyy-MM-dd HH:mm'"
        type="datetime"
        placeholder="Kada vyksta posėdis?"
        clearable
        :actions="['confirm']"
      />
    </NFormItem>
    <NButton type="primary" @click="handleSubmit">Toliau...</NButton>
  </NForm>
</template>

<script setup lang="ts">
import {
  type FormInst,
  NButton,
  NDatePicker,
  NForm,
  NFormItem,
  NIcon,
} from "naive-ui";
import { ref } from "vue";

import Icons from "@/Types/Icons/filled";

const emit = defineEmits<{
  (event: "submit", form: any): void;
  (event: "success", ...args: any[]): void;
}>();

const props = defineProps<{
  loading?: boolean;
  meeting: App.Entities.Meeting;
  // meetingTypes?: any;
  modelRoute?: string;
  matter?: App.Entities.Matter;
  // This question form is from a quick action button, idk if it shouldn't be refactored
  mattersForm?: Record<string, any>;
}>();

// check if meeting start_time is in string, then convert it to timestamp
const meetingToForm = (meeting: App.Entities.Meeting) => ({
  ...meeting,
  start_time: meeting.start_time
    ? new Date(meeting.start_time).getTime()
    : undefined,
});

const meetingForm = ref(meetingToForm(props.meeting));
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

const handleSubmit = () => {
  formRef.value?.validate((errors) => {
    if (errors) {
      /* empty */
    } else {
      emit("submit", meetingForm.value);
    }
  });
};
</script>
