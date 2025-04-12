<template>
  <Alert v-if="modelValue">
    <IFluentBookExclamationMark20Filled />
    <AlertTitle>
      {{ $t('Įsidėmėk') }}!
    </AlertTitle>
    <AlertDescription>
      <div class="pt-2 text-sm [&_p]:mb-2 [&_p]:leading-tight [&_ul]:ml-4  [&_ul]:list-disc">
        <slot />
      </div>
    </AlertDescription>
    <XIcon
      class="absolute top-2 right-2 h-4 w-4 cursor-pointer text-muted-foreground transition-colors hover:text-primary focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none"
      @click="modelValue = false" />
  </Alert>
</template>

<script setup lang="ts">
import { XIcon } from 'lucide-vue-next';
import { Alert, AlertTitle, AlertDescription } from '../ShadcnVue/ui/alert';

const modelValue = defineModel("modelValue", {
  type: Boolean,
  default: true,
});
</script>