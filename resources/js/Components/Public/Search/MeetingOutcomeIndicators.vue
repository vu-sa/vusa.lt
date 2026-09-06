<template>
  <div v-if="total > 0" class="flex items-center gap-1">
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger class="flex items-center gap-0.5">
          <!-- Vote matches (student position accepted) -->
          <span v-if="matches > 0" class="flex items-center">
            <IFluentCheckmark12Regular class="h-3 w-3 text-status-success" />
            <span class="text-[0.625rem] text-status-success font-medium">{{ matches }}</span>
          </span>

          <!-- Vote mismatches (student position not accepted) -->
          <span v-if="mismatches > 0" class="flex items-center ml-1">
            <IFluentDismiss12Regular class="h-3 w-3 text-status-warning" />
            <span class="text-[0.625rem] text-status-warning font-medium">{{ mismatches }}</span>
          </span>

          <!-- Incomplete vote data -->
          <span v-if="incomplete > 0" class="flex items-center ml-1">
            <IFluentSubtract12Regular class="h-3 w-3 text-muted-foreground" />
            <span class="text-[0.625rem] text-muted-foreground font-medium">{{ incomplete }}</span>
          </span>
        </TooltipTrigger>
        <TooltipContent>
          <div class="text-xs space-y-1">
            <p v-if="matches > 0">
              {{ $t('Studentų pozicija priimta') }}: {{ matches }}
            </p>
            <p v-if="mismatches > 0">
              {{ $t('Studentų pozicija nepriimta') }}: {{ mismatches }}
            </p>
            <p v-if="incomplete > 0">
              {{ $t('Nepilni duomenys') }}: {{ incomplete }}
            </p>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark-12-regular';
import IFluentDismiss12Regular from '~icons/fluent/dismiss-12-regular';
import IFluentSubtract12Regular from '~icons/fluent/subtract-12-regular';

const props = withDefaults(defineProps<{
  matches?: number;
  mismatches?: number;
  incomplete?: number;
}>(), {
  matches: 0,
  mismatches: 0,
  incomplete: 0,
});

const total = computed(() => props.matches + props.mismatches + props.incomplete);
</script>
