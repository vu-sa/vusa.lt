<template>
  <NForm ref="formRef" :rules="rules" :model="meetingForm">
    <NFormItem path="start_time" required>
      <template #label>
        <span class="inline-flex items-center gap-1">
          <NIcon :component="Icons.DATE" />
          <span>{{ $t("forms.fields.date") }}</span>
        </span>
      </template>
      <NDatePicker v-model:value="meetingForm.start_time" :first-day-of-week="0" :format="'yyyy-MM-dd HH:mm'"
        :time-picker-props="{
          format: 'HH:mm',
          minutes: 5,
        }" type="datetime" :placeholder="`${$t('Kada vyksta posėdis')}?`" clearable :actions="['confirm']" />
    </NFormItem>
    <NFormItem class="w-full" path="type_id" required>
      <template #label>
        <span class="inline-flex items-center gap-1">
          <NIcon :component="Icons.TYPE" />
          <span>{{ $tChoice("forms.fields.type", 0) }}</span>
        </span>
      </template>
      <NSelect v-model:value="meetingForm.type_id" :options="meetingTypes" label-field="title" value-field="id"
        placeholder="Koks posėdžio tipas?" />
    </NFormItem>
    <NButton @click="handleSubmit">
      {{ $t("Toliau") }}...
    </NButton>
  </NForm>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  type FormInst,
  type FormRules,
} from "naive-ui";
import { ref } from "vue";

import Icons from "@/Types/Icons/filled";

const emit = defineEmits<{
  (event: "submit", form: any): void;
}>();

const props = defineProps<{
  loading?: boolean;
  meeting: App.Entities.Meeting;
  // meetingTypes?: any;
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

const rules: FormRules = {
  // title: {
  //   required: true,
  //   trigger: ["blur-sm"],
  // },
  start_time: {
    required: true,
    trigger: ["blur-sm"],
    message: $t("validation.required", { attribute: $t("Data") }),
    type: "number",
  },
  // type_id: {
  //   required: true,
  //   trigger: ["blur-sm"],
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

const fetchMeetingTypes = async () => {
  const response = await fetch(route("api.types.index"));
  const data: App.Entities.Type[] = await response.json();

  return data.filter((type) => type.model_type === "App\\Models\\Meeting");
};

const meetingTypes = await fetchMeetingTypes();

// get props.meeting.types[0].id if exists
if (props.meeting.types?.length) {
  meetingForm.value.type_id = props.meeting.types[0].id;
}
</script>
