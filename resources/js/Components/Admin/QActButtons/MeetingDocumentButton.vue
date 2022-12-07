<template>
  <NMessageProvider>
    <FileUploader
      :show-object-name="true"
      :content-type-options="contentTypeOptions"
      :content-model="contentModel"
      :object-options="objectOptions"
      :institution="dutyInstitution"
      :button="MeetingButtonTemplate"
    ></FileUploader>
  </NMessageProvider>
</template>

<script setup lang="ts">
import { NMessageProvider } from "naive-ui";
import { computed } from "vue";

import { contentTypeOptions } from "@/Composables/someTypes";
import FileUploader from "@/Components/Admin/Buttons/FileUploader.vue";
import MeetingButtonTemplate from "./MeetingButtonTemplate.vue";

const props = defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  questions: Record<string, any>[];
}>();

const objectOptions = computed(() => {
  return props.questions.map((question) => {
    return {
      label: question.title,
      key: question.id,
      disabled: true,
      children: question.doings.map((doing) => {
        return {
          label: doing.title,
          key: doing.id,
        };
      }),
    };
  });
});

const contentModel = computed(() => ({
  type: "App\\Models\\Doing",
  //   contentTypes: props.doing.types,
}));
</script>
