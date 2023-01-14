<template>
  <NMessageProvider>
    <FileUploader
      :button="meetingButton"
      :content-model="contentModel"
      :content-model-options="objectOptions"
      :sharepoint-file-type-options="sharepointFileTypeOptions"
      :prespecified-type="'Protokolai'"
      :related-object-name="institution.name"
    ></FileUploader>
  </NMessageProvider>
</template>

<script setup lang="tsx">
import { DocumentAdd24Filled } from "@vicons/fluent";
import { NButton, NIcon, NMessageProvider } from "naive-ui";
import { computed } from "vue";

import { modelTypes } from "@/Types/formOptions";
import FileUploader from "@/Components/SharepointFileManager/FileUploader.vue";

const props = defineProps<{
  institution: App.Entities.Institution;
  matters: App.Entities.Matter[];
}>();

const sharepointFileTypeOptions = computed(() => {
  return modelTypes.sharepointFile.map((type) => {
    return {
      label: type,
      value: type,
    };
  });
});

const objectOptions = computed(() => {
  return props.matters.map((matter) => {
    return {
      label: matter.title,
      key: matter.id,
      disabled: true,
      // map doings as children where type is Posėdis
      children: matter.meetings?.map((meeting) => {
        return {
          label: meeting.id,
          key: meeting.id,
        };
      }),
    };
  });
});

const contentModel = computed(() => ({
  type: "App\\Models\\Meeting",
  // modelTypes: [
  //   {
  //     title: "Posėdis",
  //   },
  // ],
  //   contentTypes: props.doing.types,
}));

const meetingButton = (
  <NButton size="small">
    {{
      default: () => "Įkelti protokolą?",
      icon: () => <NIcon component={DocumentAdd24Filled}></NIcon>,
    }}
  </NButton>
);
</script>
