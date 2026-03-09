<template>
  <div v-if="total > 0" class="flex items-center gap-1">
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger class="flex items-center gap-0.5">
          <!-- Vote matches (student position accepted) -->
          <span v-if="matches > 0" class="flex items-center">
            <CheckIcon class="h-3 w-3 text-green-600 dark:text-green-400" />
            <span class="text-[10px] text-green-600 dark:text-green-400 font-medium">{{ matches }}</span>
          </span>
          
          <!-- Vote mismatches (student position not accepted) -->
          <span v-if="mismatches > 0" class="flex items-center ml-1">
            <XIcon class="h-3 w-3 text-amber-600 dark:text-amber-400" />
            <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">{{ mismatches }}</span>
          </span>
          
          <!-- Incomplete vote data -->
          <span v-if="incomplete > 0" class="flex items-center ml-1">
            <MinusIcon class="h-3 w-3 text-zinc-500 dark:text-zinc-400" />
            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">{{ incomplete }}</span>
          </span>
        </TooltipTrigger>
        <TooltipContent>
          <div class="text-xs space-y-1">
            <p v-if="matches > 0">{{ $t('Studentų pozicija priimta') }}: {{ matches }}</p>
            <p v-if="mismatches > 0">{{ $t('Studentų pozicija nepriimta') }}: {{ mismatches }}</p>
            <p v-if="incomplete > 0">{{ $t('Nepilni duomenys') }}: {{ incomplete }}</p>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { CheckIcon, XIcon, MinusIcon } from 'lucide-vue-next';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { trans as $t } from 'laravel-vue-i18n';

const props = withDefaults(defineProps<{
  matches?: number;
  mismatches?: number;
  incomplete?: number;
}>(), {
  matches: 0,
  mismatches: 0,
  incomplete: 0
});

const total = computed(() => props.matches + props.mismatches + props.incomplete);
</script>
