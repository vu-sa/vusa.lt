<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, ArrowUpRight } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import { buttonVariants } from '@/Components/ui/button';
import { LearningImportance } from '@/Types/enums';
import type { LearningStepData, KnowledgeItemData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

const props = defineProps<{ step: LearningStepData | KnowledgeItemData }>();
const isDocument = computed(() => 'source' in props.step);
const sourceHref = computed(() => 'source' in props.step ? safeHref(props.step.source?.url) : null);
// An article without a document is read on its own page.
const href = computed(() => isDocument.value
  ? sourceHref.value ?? route('knowledgeItems.show', props.step.id)
  : safeHref('actionUrl' in props.step ? props.step.actionUrl : null));
const label = computed(() => {
  if (isDocument.value) return $t('learning.path.read_document');
  return ('actionLabel' in props.step ? props.step.actionLabel : null) || $t('learning.path.open');
});
// Documents and other sites open in a new tab (↗); platform pages stay in the app (→).
const opensElsewhere = computed(() => {
  if (isDocument.value) return Boolean(sourceHref.value);
  if (!href.value || typeof window === 'undefined') return false;
  return new URL(href.value, window.location.origin).origin !== window.location.origin;
});

// Importance sets the weight: essential is the brand fill, recommended an uppercase outline, optional plain text.
const buttonClass = computed(() => {
  if (props.step.importance === LearningImportance.Essential) return buttonVariants({ variant: 'brand', size: 'sm' });
  if (props.step.importance === LearningImportance.Optional) return buttonVariants({ variant: 'ghost', voice: 'sentence', size: 'sm' });
  return buttonVariants({ variant: 'outline', voice: 'brand', size: 'sm' });
});
</script>

<template>
  <component
    :is="opensElsewhere ? 'a' : Link"
    v-if="href"
    :href="href"
    :target="opensElsewhere ? '_blank' : undefined"
    :rel="opensElsewhere ? 'noopener noreferrer' : undefined"
    :class="[buttonClass, 'min-w-0']"
    :data-importance="step.importance"
    data-slot="learning-step-action"
  >
    <span class="min-w-0 truncate">{{ label }}</span>
    <component :is="opensElsewhere ? ArrowUpRight : ArrowRight" class="size-4 shrink-0" aria-hidden="true" />
  </component>
</template>
