<template>
  <div class="af-step">
    <Handle type="target" :position="Position.Top" />
    {{ data.label }}
    <div class="af-tip nodrag nopan" :class="[`af-tip--${data.placement}`, { 'af-tip--open': selected }]" role="tooltip">
      <p>{{ data.detail }}</p>
      <a v-if="data.file" :href="fileUrl(data.file)" target="_blank" rel="noopener">
        <code>{{ data.file }}</code> ↗
      </a>
    </div>
    <Handle type="source" :position="Position.Bottom" />
  </div>
</template>

<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'
import { fileUrl } from './flows'

export interface StepData {
  label: string
  detail: string
  file?: string
  placement: 'above' | 'below'
}

defineProps<{
  data: StepData
  selected?: boolean
}>()
</script>
