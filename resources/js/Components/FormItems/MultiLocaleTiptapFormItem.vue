<template>
  <div class="space-y-2">
    <div class="inline-flex items-center gap-2">
      <Label for="description" class="flex items-center gap-1.5">
        {{ label }}
        <TooltipProvider v-if="hint">
          <Tooltip>
            <TooltipTrigger as-child>
              <button type="button" class="inline-flex">
                <IFluentInfo16Regular class="h-3.5 w-3.5 cursor-help text-muted-foreground" />
              </button>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-xs">
              {{ hint }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </Label>
      <SimpleLocaleButton v-model:locale="inputLang" />
    </div>
    <TiptapEditor v-if="inputLang === 'lt'" v-model="input.lt" preset="full" :html="true" />
    <TiptapEditor v-else v-model="input.en" preset="full" :html="true" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import TiptapEditor from '../TipTap/TiptapEditor.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';

import { Label } from '@/Components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

defineProps<{
  label: string;
  hint?: string;
}>();

const inputLang = ref(usePage().props.app.locale);

const input = defineModel<{ lt: string; en: string }>('input', {
  default: { lt: '', en: '' },
});
</script>
