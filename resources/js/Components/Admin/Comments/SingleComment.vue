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
        <NPopover trigger="click">
          <template #trigger>
            <NButton text icon>
              <NIcon>
                <MoreVerticalIcon />
              </NIcon>
            </NButton>
          </template>
          <!-- <template #default>
            <NDropdown :options="options" />
          </template> -->
        </NPopover>
      </div>
    </div>
    <div class="mt-2 text-sm" v-html="comment.comment"></div>
  </NCard>
</template>

<script setup lang="ts">
import { NButton, NCard, NIcon, NPopover } from "naive-ui";
import getRelativeTime from "@/Composables/getRelativeTime";

import UserAvatar from "@/Components/Admin/UserAvatar.vue";

defineProps<{
  comment: Record<string, any>;
}>();
</script>
