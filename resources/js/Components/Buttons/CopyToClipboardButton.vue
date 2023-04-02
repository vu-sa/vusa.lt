<template>
  <NButton @click="copyToClipboard(textToCopy)"
    ><template #icon> <NIcon :component="ClipboardLink24Regular" /></template>
    {{ $t("Kopijuoti") }}
  </NButton>
</template>

<script setup lang="tsx">
import { ClipboardLink24Regular } from "@vicons/fluent";
import { NButton, NIcon, useMessage } from "naive-ui";

defineProps<{
  textToCopy: string;
}>();

const message = useMessage();

const copyToClipboard = async (text: string) => {
  if (navigator.clipboard) {
    await navigator.clipboard.writeText(text);
    message.success("Nuoroda nukopijuota!");
  } else {
    message.error("Nepavyko nukopijuoti nuorodos...");
  }
};
</script>
