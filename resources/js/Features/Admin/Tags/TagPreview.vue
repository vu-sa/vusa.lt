<template>
  <PreviewPane entity-type="tag" :eyebrow="$t('Žyma')" :title :subtitle="tag.alias ?? undefined">
    <template v-if="$slots.actions" #actions>
      <slot name="actions" />
    </template>

    <p v-if="description" class="text-sm text-muted-foreground">
      {{ description }}
    </p>
  </PreviewPane>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { PreviewPane } from '@/Components/Patterns';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';

const props = defineProps<{
  tag: { name: unknown; alias?: string | null; description?: unknown };
}>();

const title = computed(() => getTranslatedValue(props.tag.name, undefined, '—'));
const description = computed(() => getTranslatedValue(props.tag.description));
</script>
