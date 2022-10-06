<template>
  <!-- Layout for a single comment -->
  <NCard class="mb-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <UserAvatar show-name :user="comment.user" />
      </div>
      <div class="flex items-center">
        <span :title="comment.created_at" class="mr-2 text-sm text-gray-500">
          {{ getRelativeTime(comment.created_at) }}
        </span>
      </div>
    </div>
    <div class="mt-2 text-sm" v-html="comment.comment"></div>
  </NCard>
</template>

<script setup lang="ts">
import { NCard } from "naive-ui";
import getRelativeTime from "@/Composables/getRelativeTime";

import UserAvatar from "@/Components/Admin/UserAvatar.vue";

defineProps<{
  comment: Record<string, any>;
}>();
</script>
