<template>
  <NButton @click="copyToClipboard(textToCopy)">
    <template v-if="showIcon" #icon>
      <IFluentClipboardLink24Regular />
    </template>
    <slot />
  </NButton>
</template>

<script setup lang="ts">
import { useMessage } from "naive-ui";

const props = defineProps<{
  textToCopy: string;
  showIcon?: boolean;
  successText?: string;
  errorText?: string;
}>();

const message = useMessage();

const copyToClipboard = async (text: string) => {
  if (navigator.clipboard) {
    await navigator.clipboard.writeText(text);
    message.success(props.successText ?? "Nuoroda nukopijuota į iškarpinę!");
  } else {
    message.error(props.errorText ?? "Nepavyko nukopijuoti nuorodos į iškarpinę...");
  }
};
</script>
