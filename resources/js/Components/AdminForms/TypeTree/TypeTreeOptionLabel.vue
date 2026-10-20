<template>
  <span class="flex min-w-0 flex-col" :style="{ paddingLeft: `${node.depth * 1}rem` }">
    <span class="truncate">{{ node.label }}</span>
    <span v-if="node.ancestors.length" class="truncate text-xs text-muted-foreground">{{ node.ancestors.join(' › ') }}</span>
  </span>
</template>

<script setup lang="ts">
import type { TreeType, TypeTreeNode } from '@/Utils/typeTree';

defineProps<{ node: TypeTreeNode<TreeType> }>();
</script>
