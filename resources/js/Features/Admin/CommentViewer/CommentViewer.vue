<template>
  <div class="grid max-w-3xl grid-rows-[minmax(150px,min-content)_auto]">
    <div
      class="relative flex rounded-t-md border border-b-0 border-zinc-300 px-2 pt-2 dark:border-zinc-700"
    >
      <ScrollArea
        v-if="comments && comments.length > 0"
        class="max-h-96 px-4"
      >
        <div ref="commentContainer">
          <div v-for="comment in comments" :key="comment.id" :comment="comment">
            <div
              class="mb-4 grid grid-cols-[40px_1fr] gap-x-4 gap-y-2 first:pt-4 last:pb-4"
            >
              <UserPopover bold :size="32" :user="comment.user" />
              <div class="inline-flex flex-col">
                <div>
                  <strong class="text-md">{{ comment.user.name }}</strong>
                </div>
                <span
                  :title="comment.created_at"
                  class="mr-2 text-xs text-gray-500"
                >
                  <span>{{
                    formatRelativeTime(
                      new Date(comment.created_at),
                      { numeric: "auto" },
                      $page.props.app.locale
                    )
                  }}</span>
                </span>
              </div>
              <div></div>
              <div class="mt-2 text-sm" v-html="comment.comment"></div>
            </div>
            <hr class="my-4 last:invisible dark:border-zinc-600" />
          </div>
        </div>
      </ScrollArea>
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
import { computed, nextTick, onUpdated, ref } from "vue";
import { formatRelativeTime } from "@/Utils/IntlTime";
import { router, usePage } from "@inertiajs/vue3";
import CommentTipTap from "./CommentTipTap.vue";
import UserPopover from "@/Components/Avatars/UserPopover.vue";
import { ScrollArea } from "@/Components/ui/scroll-area";

defineEmits<{
  (e: "passText", text: string): void;
}>();

const props = defineProps<{
  model: Record<string, any> | null;
  commentable_type: string;
  comments?: App.Entities.Comment[];
}>();

const loading = ref(false);
const text = ref<string | null>(null);
const commentContainer = ref<HTMLElement | null>(null);

const comments = computed(() => {
  if (props.comments) return props.comments;
  if (props.model?.comments) return props.model.comments;
  return [];
});

const submitComment = () => {
  loading.value = true;
  router.post(
    route("users.comments.store", usePage().props.auth?.user.id),
    {
      commentable_type: props.commentable_type,
      commentable_id: props.model?.id,
      comment: text.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false;
      },
      onError: () => {
        loading.value = false;
      },
    }
  );
};

onUpdated(() => {
  if (!commentContainer.value) return;

  nextTick(() => {
    const viewport = commentContainer.value?.closest('[data-slot="scroll-area-viewport"]');
    if (viewport) {
      viewport.scrollTo({
        top: viewport.scrollHeight,
        behavior: "smooth",
      });
    }
  });
});
</script>

<style scoped>
ul {
  list-style-position: inside;
}
</style>
