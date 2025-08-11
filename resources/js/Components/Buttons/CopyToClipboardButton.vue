<template>
  <NButton @click="copyToClipboard(textToCopy)">
    <template v-if="showIcon" #icon>
      <IFluentClipboardLink24Regular />
    </template>
    <slot />
  </NButton>
</template>

<script setup lang="ts">
import { useToasts } from '@/Composables/useToasts';

const props = defineProps<{
  textToCopy: string;
  showIcon?: boolean;
  successText?: string;
  errorText?: string;
}>();

const toasts = useToasts();

const copyToClipboard = async (text: string) => {
  if (navigator.clipboard) {
    await navigator.clipboard.writeText(text);
    toasts.success(props.successText ?? "Nuoroda nukopijuota į iškarpinę!");
  } else {
    toasts.error(props.errorText ?? "Nepavyko nukopijuoti nuorodos į iškarpinę...");
  }
};
</script>
