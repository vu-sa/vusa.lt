<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <button 
          type="button"
          class="flex items-center gap-1 px-1.5 py-0.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
          @click="$emit('click')"
        >
          <!-- Decision indicator -->
          <span 
            :class="getIndicatorClass(decision)" 
            class="w-2 h-2 rounded-full transition-colors"
            :title="$t('voting_field_decision_label')"
          />
          
          <!-- Student vote indicator -->
          <span 
            :class="getIndicatorClass(studentVote)" 
            class="w-2 h-2 rounded-full transition-colors"
            :title="$t('voting_field_student_vote_label')"
          />
          
          <!-- Student benefit indicator -->
          <span 
            :class="getIndicatorClass(studentBenefit)" 
            class="w-2 h-2 rounded-full transition-colors"
            :title="$t('voting_field_student_benefit_label')"
          />
          
          <ChevronDown class="h-3 w-3 text-zinc-400 ml-0.5" />
        </button>
      </TooltipTrigger>
      <TooltipContent side="bottom" class="max-w-xs">
        <div class="text-xs space-y-1">
          <div class="flex items-center gap-2">
            <span :class="getIndicatorClass(decision)" class="w-2 h-2 rounded-full" />
            <span class="text-zinc-500">{{ $t('voting_field_decision_label') }}:</span>
            <span class="font-medium">{{ getStatusText(decision, 'decision') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span :class="getIndicatorClass(studentVote)" class="w-2 h-2 rounded-full" />
            <span class="text-zinc-500">{{ $t('voting_field_student_vote_label') }}:</span>
            <span class="font-medium">{{ getStatusText(studentVote, 'student_vote') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span :class="getIndicatorClass(studentBenefit)" class="w-2 h-2 rounded-full" />
            <span class="text-zinc-500">{{ $t('voting_field_student_benefit_label') }}:</span>
            <span class="font-medium">{{ getStatusText(studentBenefit, 'student_benefit') }}</span>
          </div>
          <div class="text-zinc-400 pt-1 border-t border-zinc-200 dark:border-zinc-700 mt-1">
            {{ $t('Spustelėkite, kad redaguotumėte') }}
          </div>
        </div>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/Components/ui/tooltip';

defineProps<{
  decision?: string | null;
  studentVote?: string | null;
  studentBenefit?: string | null;
}>();

defineEmits<{
  click: [];
}>();

const getIndicatorClass = (state: string | null | undefined): string => {
  switch (state) {
    case 'positive':
      return 'bg-green-500';
    case 'negative':
      return 'bg-red-500';
    case 'neutral':
      return 'bg-zinc-400';
    default:
      return 'bg-zinc-200 dark:bg-zinc-600 ring-1 ring-zinc-300 dark:ring-zinc-500';
  }
};

const getStatusText = (state: string | null | undefined, type: string): string => {
  if (!state) return $t('Nenustatyta');
  
  if (type === 'decision') {
    switch (state) {
      case 'positive': return $t('Priimtas');
      case 'negative': return $t('Nepriimtas');
      case 'neutral': return $t('Susilaikyta');
      default: return $t('Nenustatyta');
    }
  }
  
  if (type === 'student_vote') {
    switch (state) {
      case 'positive': return $t('Pritarė');
      case 'negative': return $t('Nepritarė');
      case 'neutral': return $t('Susilaikė');
      default: return $t('Nenustatyta');
    }
  }
  
  if (type === 'student_benefit') {
    switch (state) {
      case 'positive': return $t('Palanku');
      case 'negative': return $t('Nepalanku');
      case 'neutral': return $t('Neutralu');
      default: return $t('Nenustatyta');
    }
  }
  
  return $t('Nenustatyta');
};
</script>
