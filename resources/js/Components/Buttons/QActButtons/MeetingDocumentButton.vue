<template>
  <NMessageProvider>
    <FileUploader
      :button="meetingButton"
      :content-model="contentModel"
      :content-model-options="objectOptions"
      :content-type-options="contentTypeOptions"
      :prespecified-type="'Protokolai'"
      :related-object-name="dutyInstitution.name"
    ></FileUploader>
  </NMessageProvider>
</template>

<script setup lang="tsx">
import { DocumentAdd24Filled } from "@vicons/fluent";
import { NButton, NIcon, NMessageProvider } from "naive-ui";
import { computed } from "vue";

import { contentTypeOptions } from "@/Composables/someTypes";
import FileUploader from "@/Components/SharepointFileManager/FileUploader.vue";

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
      // map doings as children where type is Posėdis
      children: question.doings
        .filter((doing) => doing.types.some((type) => type.title === "Posėdis"))
        .map((doing) => {
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
  modelTypes: [
    {
      title: "Posėdis",
    },
  ],
  //   contentTypes: props.doing.types,
}));

const meetingButton = (
  <NButton size="small">
    {{
      default: () => "Įkelti protokolą?",
      icon: <NIcon component={DocumentAdd24Filled}></NIcon>,
    }}
  </NButton>
);
</script>
