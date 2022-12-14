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
        <NDropdown :options="options" @select="handleSelect">
          <NButton size="small" quaternary circle
            ><template #icon
              ><NIcon :component="MoreHorizontal32Filled"></NIcon></template
          ></NButton>
        </NDropdown>
      </div>
    </div>
    <div class="mt-2 text-sm" v-html="comment.comment"></div>
  </NCard>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { MoreHorizontal32Filled } from "@vicons/fluent";
import { NButton, NCard, NDropdown, NIcon } from "naive-ui";
import route from "ziggy-js";

import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import getRelativeTime from "@/Composables/getRelativeTime";

const props = defineProps<{
  comment: Record<string, any>;
}>();

const options = [
  {
    label: "Redaguoti",
    key: "edit",
  },
  {
    label: "Ištrinti",
    key: "delete",
  },
];

const handleSelect = (option: string) => {
  switch (option) {
    case "edit":
      Inertia.get(
        route("users.comments.edit", {
          user: props.comment.user.id,
          comment: props.comment.id,
        })
      );
      break;
    case "delete":
      Inertia.delete(
        route("users.comments.destroy", {
          user: props.comment.user.id,
          comment: props.comment.id,
        }),
        {
          preserveScroll: true,
        }
      );
      break;
  }
};
</script>
