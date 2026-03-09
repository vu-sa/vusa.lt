<template>
  <div class="space-y-2">
    <Label class="flex items-center gap-1.5">
      <IFluentLink24Regular class="h-4 w-4" />
      {{ $t('Nuoroda') }}
    </Label>

    <div class="flex items-stretch gap-2">
      <div class="flex flex-1 items-center gap-0 overflow-hidden rounded-md border bg-muted/50">
        <span class="shrink-0 rounded-l-md border-r bg-muted px-3 py-2 text-sm text-muted-foreground">
          {{ baseUrl }}/
        </span>
        <Input
          :model-value="permalink"
          :disabled="disabled"
          class="rounded-l-none border-0 bg-transparent focus-visible:ring-0"
          :placeholder="$t('nuorodos-fragmentas')"
          @update:model-value="$emit('update:permalink', $event)"
        />
      </div>

      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="outline" size="icon" @click="copyUrl">
              <IFluentCopy24Regular v-if="!copied" class="h-4 w-4" />
              <IFluentCheckmark24Regular v-else class="h-4 w-4 text-green-600" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Kopijuoti nuorodą') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <TooltipProvider v-if="viewUrl">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="outline" size="icon" as="a" :href="viewUrl" target="_blank">
              <IFluentOpen24Regular class="h-4 w-4" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Atidaryti puslapį') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </div>

    <p v-if="disabled && explanation" class="flex items-center gap-1 text-xs text-muted-foreground">
      <IFluentInfo16Regular class="h-3.5 w-3.5 shrink-0" />
      {{ explanation }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useClipboard } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

const props = defineProps<{
  permalink?: string;
  baseUrl: string;
  disabled?: boolean;
  viewUrl?: string;
  explanation?: string;
}>();

defineEmits<{
  (e: 'update:permalink', value: string): void;
}>();

const copied = ref(false);
const { copy } = useClipboard();

const fullUrl = computed(() => {
  const slug = props.permalink || '';
  return `${props.baseUrl}/${slug}`;
});

const copyUrl = async () => {
  await copy(fullUrl.value);
  copied.value = true;
  setTimeout(() => {
    copied.value = false;
  }, 2000);
};
</script>
