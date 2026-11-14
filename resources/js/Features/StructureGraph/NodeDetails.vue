<template>
  <NodeToolbar
    :is-visible
    :position="Position.Top"
    class="w-max max-w-[min(20rem,calc(100vw-3rem))] border border-border bg-popover p-3 text-xs text-popover-foreground shadow-sm"
    data-slot="structure-node-details"
  >
    <strong v-if="typeof data.label === 'string'" class="block">{{ data.label }}</strong>
    <p v-if="data.description" class="mt-1">
      {{ data.description }}
    </p>
    <Link v-if="data.article" :href="data.article.url" class="mt-1 block font-medium text-brand underline" data-testid="structure-node-article" @click.stop>
      {{ $t('learning.wiki.structure_read', { title: data.article.title }) }}
    </Link>
    <a
      v-if="data.url"
      rel="noopener noreferrer"
      :href="data.url"
      target="_blank"
      class="mt-1 inline-block text-brand underline"
      @click.stop
    >{{ $t('Nuoroda') }}</a>
  </NodeToolbar>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Position } from '@vue-flow/core';
import { NodeToolbar } from '@vue-flow/node-toolbar';

import type { StructureNodeData } from './handles';

defineProps<{
  isVisible: boolean;
  data: StructureNodeData;
}>();
</script>
