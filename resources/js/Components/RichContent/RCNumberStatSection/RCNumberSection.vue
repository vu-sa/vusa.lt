<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :background="element.options?.background ?? 'none'" :padding="element.options?.padding ?? 'md'"
    :rounded="element.options?.rounded ?? 'none'" :align="element.options?.align ?? 'center'"
    :heading-level="element.options?.headingLevel" :show-separator="element.options?.showSeparator"
    inner="wide" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <div class="flex flex-wrap mx-auto font-bold text-xl leading-tight justify-center gap-6 md:gap-8">
      <NumberStatistic v-for="numberStat in element.json_content" :key="numberStat.label"
        :end-number="numberStat.endNumber" :show-plus="numberStat.showPlus">
        {{ numberStat.label }}
      </NumberStatistic>
    </div>
  </RCSection>
</template>

<script setup lang="ts">
import NumberStatistic from './RCNumberStatistic.vue';
import RCSection from '../RCSection.vue';

import type { NumberStatSection } from '@/Types/contentParts';

defineProps<{
  element: NumberStatSection;
  anchorId?: number | null;
}>();
</script>
