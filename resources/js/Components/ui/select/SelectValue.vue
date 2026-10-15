<template>
  <SelectValue
    data-slot="select-value"
    v-bind="forwardedProps"
  >
    <template v-if="$slots.default || icon" #default="slotProps">
      <component :is="icon" v-if="icon" class="size-4 shrink-0" aria-hidden="true" />
      <slot v-bind="slotProps">
        <span class="truncate">{{ slotProps.selectedLabel.length ? slotProps.selectedLabel.join(', ') : placeholder }}</span>
      </slot>
    </template>
  </SelectValue>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { SelectValue, type SelectValueProps } from 'reka-ui';

/** `icon` shows the chosen option's icon in the trigger: reka copies only the item's text. */
const props = defineProps<SelectValueProps & { icon?: Component }>();

const forwardedProps = reactiveOmit(props, 'icon');
</script>
