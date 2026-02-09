<template>
  <div class="space-y-2">
    <template v-if="languages.length">
      <label
        v-for="lang in languages"
        :key="lang.value"
        :class="[
          'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200 hover:bg-accent/50 hover:border-accent-foreground/20',
          selectedLanguages.includes(lang.value) ? 'bg-accent border-accent-foreground/20 shadow-sm' : 'hover:shadow-sm'
        ]"
      >
        <Checkbox
          :model-value="selectedLanguages.includes(lang.value)"
          class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
          @update:model-value="emit('toggle', lang.value)"
        />
        <div class="flex items-center justify-between flex-1">
          <div class="flex items-center gap-2">
            <img
              v-if="getLanguageFlag(lang.value)"
              :src="getLanguageFlag(lang.value)"
              :alt="`${getLanguageDisplay(lang.value)} flag`"
              width="16"
              class="rounded-full flex-shrink-0"
            >
            <span class="font-medium text-sm text-foreground">
              {{ getLanguageDisplay(lang.value) }}
            </span>
          </div>
          <Badge variant="outline" class="text-xs font-medium">
            {{ formatCount(lang.count) }}
          </Badge>
        </div>
      </label>
    </template>
    <div v-else class="text-sm text-muted-foreground p-3 text-center italic">
      {{ $t('search.language_filters_after_search') }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { Checkbox } from '@/Components/ui/checkbox';
import { Badge } from '@/Components/ui/badge';

interface LanguageValue {
  value: string;
  count: number;
}

interface Props {
  languages: LanguageValue[];
  selectedLanguages: string[];
}

type Emits = (e: 'toggle', language: string) => void;

defineProps<Props>();
const emit = defineEmits<Emits>();

// Utility functions
const formatCount = (count: number): string => {
  if (count >= 1000000) {
    return `${(count / 1000000).toFixed(1)}M`;
  }
  if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}K`;
  }
  return count.toString();
};

const getLanguageFlag = (languageValue: string): string => {
  if (languageValue === 'Lietuvių' || languageValue === 'Lithuanian') {
    return 'https://hatscripts.github.io/circle-flags/flags/lt.svg';
  }
  if (languageValue === 'Anglų' || languageValue === 'English') {
    return 'https://hatscripts.github.io/circle-flags/flags/gb.svg';
  }
  return ''; // For Unknown or other languages - no flag
};

const getLanguageDisplay = (languageValue: string): string => {
  if (languageValue === 'Lietuvių' || languageValue === 'Lithuanian') return 'LT';
  if (languageValue === 'Anglų' || languageValue === 'English') return 'EN';
  if (languageValue === 'Unknown') return $t('search.language_unknown');
  return languageValue; // For any other language values, show as-is
};
</script>
