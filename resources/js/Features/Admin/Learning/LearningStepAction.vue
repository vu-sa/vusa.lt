<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';

import { rowActionLabelledClass, rowActionHoverClass } from '@/Components/Collection/rowActionClasses';
import type { LearningStepData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

const props = defineProps<{ step: LearningStepData }>();
const external = computed(() => props.step.kind === 'knowledge');
const href = computed(() => safeHref(external.value ? props.step.source?.url : props.step.actionUrl));
const label = computed(() => external.value ? 'learning.path.read_document'
  : props.step.isSystem && props.step.key ? `learning.system_steps.${props.step.key}.action` : 'learning.path.open');
</script>

<template>
  <component
    :is="external ? 'a' : Link"
    v-if="href"
    :href="href"
    :target="external ? '_blank' : undefined"
    :rel="external ? 'noopener noreferrer' : undefined"
    :class="[rowActionLabelledClass, rowActionHoverClass, 'border-border text-foreground']"
    data-slot="learning-step-action"
  >
    {{ $t(label) }}<ArrowUpRight class="size-4 shrink-0" aria-hidden="true" />
  </component>
</template>
