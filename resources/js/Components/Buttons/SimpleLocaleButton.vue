<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <!-- `type="button"`: this lives inside admin <form>s, where a bare <button>
             defaults to submit — flipping the language used to save the form. -->
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="$emit('update:locale', locale === 'lt' ? 'en' : 'lt')"
        >
          {{ locale === "lt" ? "🇱🇹" : "🇬🇧" }}
        </Button>
      </TooltipTrigger>
      <TooltipContent>
        <template v-if="locale === 'lt'">
          Pildyti anglų kalbos laukelius
        </template>
        <template v-else>
          Fill in Lithuanian fields
        </template>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { Button } from '@/Components/ui/button';
import type { LocaleEnum } from '@/Types/enums';

defineEmits<(event: 'update:locale', locale: LocaleEnum) => void>();

defineProps<{
  locale: string;
}>();
</script>
