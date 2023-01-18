<template>
  <div class="grid w-full grid-rows-[minmax(300px,_500px)_155px]">
    <div
      v-if="model?.comments"
      class="rounded-sm border border-zinc-300 px-2 pt-2 dark:border-zinc-700"
      style="max-height: 500px"
    >
      <NScrollbar ref="commentScroll" style="height: 100%" class="px-4">
        <FadeTransitionGroup>
          <div
            v-for="comment in model?.comments"
            :key="comment.id"
            :comment="comment"
          >
            <div
              class="mb-4 grid grid-cols-[40px_1fr] gap-x-4 gap-y-2 first:pt-4 last:pb-4"
            >
              <UserPopover bold :size="32" :user="comment.user" />
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
    </div>
    <div v-else class="flex h-full w-full items-center justify-center">
      <p class="h-fit w-fit text-zinc-400">Komentarų nėra</p>
    </div>

    <div class="relative">
      <CommentTipTap
        v-model:text="text"
        :disabled="!model"
        :loading="loading"
        class="absolute bottom-0 w-full"
        @submit="submitComment"
      ></CommentTipTap>
    </div>
  </div>
</template>

<script setup lang="tsx">
import { NScrollbar, type ScrollbarInst } from "naive-ui";
import { formatRelativeTime } from "@/Utils/IntlTime";
// intellisense hates 'ref' in this file
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import CommentTipTap from "./CommentTipTap.vue";
import FadeTransitionGroup from "@/Components/Transitions/FadeTransitionGroup.vue";
import UserPopover from "@/Components/Avatars/UserPopover.vue";

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

console.log(!props.model);

watch(
  () => props.model?.comments,
  (comments) => {
    commentScroll.value?.scrollTo(0, commentScroll.value.scrollHeight);
  }
);

const submitComment = () => {
  loading.value = true;
  router.post(
    route("users.comments.store", usePage().props.auth?.user.id),
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
