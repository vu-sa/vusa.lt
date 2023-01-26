<template>
  <div class="grid w-full grid-rows-[minmax(150px,min-content)_280px]">
    <div
      class="relative flex rounded-sm border border-zinc-300 px-2 pt-2 dark:border-zinc-700"
    >
      <NScrollbar
        v-if="model?.comments && model.comments.length > 0"
        ref="scrollContainer"
        style="max-height: 24rem"
        class="px-4"
      >
        <div ref="commentContainer">
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
                <div>
                  <strong class="text-md">{{ comment.user.name }}</strong>
                  <NTag
                    v-if="comment.decision"
                    size="small"
                    :bordered="false"
                    round
                    :type="decisionType(comment.decision)"
                    class="ml-4"
                    >{{ comment.decision }}</NTag
                  >
                </div>
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
        </div>
      </NScrollbar>
      <p v-else class="m-auto w-fit text-zinc-400">Komentarų nėra</p>
    </div>
    <CommentTipTap
      v-model:text="text"
      :disabled="!model"
      :loading="loading"
      @submit:comment="submitComment"
    ></CommentTipTap>
  </div>
</template>

<script setup lang="tsx">
import { NScrollbar, NTag, type ScrollbarInst } from "naive-ui";
import { formatRelativeTime } from "@/Utils/IntlTime";
// intellisense hates 'ref' in this file
import { onUpdated, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import CommentTipTap from "./CommentTipTap.vue";
import UserPopover from "@/Components/Avatars/UserPopover.vue";

defineEmits<{
  (e: "passText", text: string): void;
}>();

const props = defineProps<{
  model?: Record<string, any> | null;
  commentable_type: string;
}>();

const loading = ref(false);
const text = ref<string | null>(null);
const commentContainer = ref<HTMLElement | null>(null);
const scrollContainer = ref<ScrollbarInst | null>(null);

const submitComment = (decision?: "approve" | "reject") => {
  loading.value = true;
  router.post(
    route("users.comments.store", usePage().props.auth?.user.id),
    {
      commentable_type: props.commentable_type,
      commentable_id: props.model?.id,
      comment: text.value,
      decision: decision ?? undefined,
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

const decisionType = (decision: string) => {
  switch (decision) {
    case "approve":
      return "success";
    case "reject":
      return "warning";
    default:
      return "info";
  }
};

onUpdated(() => {
  if (!commentContainer.value || !scrollContainer.value) return;

  scrollContainer.value?.scrollTo({
    top: commentContainer.value?.scrollHeight,
    behavior: "smooth",
  });
});
</script>

<style scoped>
ul {
  list-style-position: inside;
}
</style>
