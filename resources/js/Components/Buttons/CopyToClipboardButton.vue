<template>
  <Button variant="outline" @click="copyToClipboard(textToCopy)">
    <IFluentClipboardLink24Regular v-if="showIcon" />
    <slot />
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';
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
