<template>
  <div class="grid max-h-full w-full grid-rows-[1fr_155px]">
    <NScrollbar ref="commentScroll" class="px-4" style="max-height: 700px">
      <FadeTransitionGroup>
        <div
          v-for="comment in model?.comments"
          :key="comment.id"
          :comment="comment"
        >
          <div
            class="mb-4 grid grid-cols-[40px_1fr] gap-x-4 gap-y-2 first:pt-4 last:pb-4"
          >
            <UserAvatar bold :size="32" :user="comment.user" />
            <div class="inline-flex flex-col">
              <strong class="text-md">{{ comment.user.name }}</strong>
              <span
                :title="comment.created_at"
                class="mr-2 text-xs text-gray-500"
              >
                <span>{{
                  formatRelativeTime(new Date(comment.created_at))
                }}</span>
              </span>
            </div>
            <div></div>
            <div class="mt-2 text-sm" v-html="comment.comment"></div>
          </div>
          <hr class="my-4 last:invisible dark:border-zinc-600" />
        </div>
      </FadeTransitionGroup>
    </NScrollbar>
    <div class="relative">
      <CommentTipTap
        v-model:text="text"
        :loading="loading"
        class="absolute bottom-0 w-full"
        @submit="submitComment"
      ></CommentTipTap>
    </div>
  </div>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { NScrollbar, type ScrollbarInst } from "naive-ui";
import { ref, watch } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";

import { formatRelativeTime } from "@/Utils/IntlTime";
import CommentTipTap from "../CommentViewer/CommentTipTap.vue";
import FadeTransitionGroup from "@/Components/Transitions/FadeTransitionGroup.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

defineEmits<{
  (e: "passText", text: string): void;
}>();

const props = defineProps<{
  model?: Record<string, any>;
  commentable_type: string;
  text: string;
}>();

const loading = ref(false);
const text = ref(null);
const commentScroll = ref<ScrollbarInst | null>(null);

watch(
  () => props.model?.comments,
  (comments) => {
    commentScroll.value?.scrollTo(0, commentScroll.value.scrollHeight);
  }
);

const submitComment = () => {
  loading.value = true;
  Inertia.post(
    route("users.comments.store", usePage().props.value.auth?.user.id),
    {
      commentable_type: props.commentable_type,
      commentable_id: props.model.id,
      comment: text.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        // editor.value?.chain().focus().setContent("").run();
        loading.value = false;
      },
      onError: () => {
        loading.value = false;
      },
    }
  );
};
</script>
