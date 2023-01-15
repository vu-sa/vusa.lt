<template>
  <!-- Layout for a single comment -->
  <NCard class="mb-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <UserAvatar show-name :user="comment.user" />
      </div>
      <div class="flex items-center">
        <span :title="comment.created_at" class="mr-2 text-sm text-gray-500">
          {{ formatRelativeTime(new Date(comment.created_at)) }}
        </span>
        <MoreOptionsButton
          small
          delete
          @delete-click="handleDelete"
        ></MoreOptionsButton>
      </div>
    </div>
    <div class="mt-2 text-sm" v-html="comment.comment"></div>
  </NCard>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { NCard } from "naive-ui";

import { formatRelativeTime } from "@/Utils/IntlTime";
import MoreOptionsButton from "../../../Components/Buttons/MoreOptionsButton.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const props = defineProps<{
  comment: Record<string, any>;
}>();

const handleDelete = () => {
  router.delete(
    route("users.comments.destroy", {
      user: props.comment.user.id,
      comment: props.comment.id,
    }),
    {
      preserveScroll: true,
    }
  );
};
</script>
