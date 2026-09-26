<template>
  <Button variant="outline" :voice @click="copyToClipboard(textToCopy)">
    <IFluentClipboardLink24Regular v-if="showIcon" />
    <slot />
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';
import type { ButtonVariants } from '@/Components/ui/button';
import { useToasts } from '@/Composables/useToasts';

const props = withDefaults(defineProps<{
  textToCopy: string;
  showIcon?: boolean;
  successText?: string;
  errorText?: string;
  voice?: ButtonVariants['voice'];
}>(), {
  successText: undefined,
  errorText: undefined,
  voice: undefined,
});

const toasts = useToasts();

const copyToClipboard = async (text: string) => {
  if (navigator.clipboard) {
    await navigator.clipboard.writeText(text);
    toasts.success(props.successText ?? 'Nuoroda nukopijuota į iškarpinę!');
  }
  else {
    toasts.error(props.errorText ?? 'Nepavyko nukopijuoti nuorodos į iškarpinę...');
  }
};
</script>
