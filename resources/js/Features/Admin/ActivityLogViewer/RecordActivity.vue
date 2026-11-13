<template>
  <DiscussionPanel :commentable-type :commentable-id :mentionables-key />
</template>

<script setup lang="ts">
import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';

defineProps<{
  commentableType: string;
  commentableId: string;
  /** Changes when the parent's people change, so the @mention list is refetched without a reload. */
  mentionablesKey?: string;
}>();
</script>
