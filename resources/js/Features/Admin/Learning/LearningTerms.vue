<script setup lang="ts">
import { BookOpen } from '@lucide/vue';

import { controlVariants } from '@/Components/ui/control';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import type { LearningStepData } from '@/Types/learning';
defineProps<{ terms: LearningStepData['glossaryTerms'] }>();
</script>

<template>
  <div v-if="terms.length" class="space-y-1">
    <p class="flex items-center gap-1.5 text-xs text-muted-foreground"><BookOpen class="size-3.5" aria-hidden="true" />{{ $t('learning.path.related_terms') }}</p>
    <div class="flex flex-wrap gap-2">
      <Popover v-for="term in terms" :key="term.id">
        <PopoverTrigger as-child>
          <button type="button" :class="[controlVariants({ size: 'sm', voice: 'sentence' }), 'min-h-11 max-w-full whitespace-normal text-left']">{{ term.term }}</button>
        </PopoverTrigger>
        <PopoverContent class="max-w-[calc(100vw-2rem)]">
          <p class="font-semibold">{{ term.term }}</p>
          <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ term.definition }}</p>
        </PopoverContent>
      </Popover>
    </div>
  </div>
</template>
