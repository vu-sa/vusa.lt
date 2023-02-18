<template>
  <NForm ref="formRef" :rules="rules" :model="meetingForm">
    <NFormItem path="start_time" required>
      <template #label>
        <span class="inline-flex items-center gap-1"
          ><NIcon :component="Icons.DATE"></NIcon>
          <span>{{ $t("forms.fields.date") }}</span></span
        >
      </template>
      <NDatePicker
        v-model:value="meetingForm.start_time"
        :first-day-of-week="0"
        :format="'yyyy-MM-dd HH:mm'"
        :time-picker-props="{
          format: 'HH:mm',
        }"
        type="datetime"
        :placeholder="`${$t('Kada vyksta posėdis')}?`"
        clearable
        :actions="['confirm']"
      />
    </NFormItem>
    <NButton @click="handleSubmit">{{ $t("Toliau") }}...</NButton>
  </NForm>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  type FormInst,
  NButton,
  NDatePicker,
  NForm,
  NFormItem,
  NIcon,
} from "naive-ui";
import { reactive, ref } from "vue";

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

const rules = reactive({
  // title: {
  //   required: true,
  //   trigger: ["blur"],
  // },
  start_time: {
    required: true,
    trigger: ["blur"],
    message: $t("validation.required", { attribute: $t("Data") }),
    type: "number",
  },
  // type_id: {
  //   required: true,
  //   trigger: ["blur"],
  // },
});

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
