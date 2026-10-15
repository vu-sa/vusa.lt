<template>
  <Button variant="outline" :voice @click="copyToClipboard(textToCopy)">
    <IFluentClipboardLink24Regular v-if="showIcon" />
    <slot />
  </Button>
</template>

<script setup lang="ts">
import { useClipboard } from '@vueuse/core';

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
const { copy, isSupported } = useClipboard({ legacy: true });

const copyToClipboard = async (text: string) => {
  if (isSupported.value) {
    try {
      await copy(text);
      toasts.success(props.successText ?? 'Nuoroda nukopijuota į iškarpinę!');
      return;
    }
    catch {
      // Fall through to error toast
    }
  }
  toasts.error(props.errorText ?? 'Nepavyko nukopijuoti nuorodos į iškarpinę...');
};
</script>
