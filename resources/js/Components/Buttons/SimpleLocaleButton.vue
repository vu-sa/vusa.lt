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
        {{ locale === 'lt' ? $t('Pildyti anglų kalbos laukelius') : $t('Pildyti lietuvių kalbos laukelius') }}
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { Button } from '@/Components/ui/button';
import type { LocaleEnum } from '@/Types/enums';

defineEmits<(event: 'update:locale', locale: LocaleEnum) => void>();

defineProps<{
  locale: string;
}>();
</script>
