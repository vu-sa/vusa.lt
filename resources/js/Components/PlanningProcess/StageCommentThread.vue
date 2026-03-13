<template>
  <Card>
    <Collapsible v-model:open="isOpen">
      <!-- Collapsible trigger header -->
      <CollapsibleTrigger as-child>
        <CardHeader class="cursor-pointer select-none transition-colors hover:bg-muted/50">
          <div class="flex items-center gap-3">
            <div
              class="shrink-0 h-9 w-9 rounded-lg flex items-center justify-center transition-colors"
              :class="comments.length > 0
                ? 'bg-primary/10 dark:bg-primary/20'
                : 'bg-muted'"
            >
              <MessageCircleIcon
                class="h-4.5 w-4.5 transition-colors"
                :class="comments.length > 0
                  ? 'text-primary'
                  : 'text-muted-foreground'"
              />
            </div>
            <div class="flex-1 min-w-0">
              <CardTitle class="text-base">{{ $t("Diskusija") }}</CardTitle>
              <CardDescription v-if="!isOpen && comments.length === 0">
                {{ $t("Pradėkite diskusiją apie šį etapą") }}
              </CardDescription>
              <CardDescription v-else-if="!isOpen && comments.length > 0">
                {{ latestCommentPreview }}
              </CardDescription>
            </div>
            <Badge v-if="comments.length > 0" variant="secondary" class="shrink-0 tabular-nums">
              {{ comments.length }}
            </Badge>
            <ChevronDownIcon
              class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200"
              :class="{ 'rotate-180': isOpen }"
            />
          </div>
        </CardHeader>
      </CollapsibleTrigger>

      <!-- Thread content -->
      <CollapsibleContent>
        <CardContent class="pt-0">
          <!-- Comments list -->
          <div v-if="comments.length > 0" class="mb-4">
            <ScrollArea class="max-h-[28rem]">
              <div ref="commentContainer" class="flex flex-col gap-1 pr-3">
                <TransitionGroup name="comment">
                  <div
                    v-for="(comment, index) in comments"
                    :key="comment.id"
                    class="group"
                  >
                    <!-- Date separator -->
                    <div
                      v-if="shouldShowDateSeparator(index)"
                      class="flex items-center gap-3 py-3"
                    >
                      <Separator class="flex-1" />
                      <span class="text-[11px] font-medium text-muted-foreground/70 uppercase tracking-wider shrink-0">
                        {{ formatDateLabel(comment.created_at) }}
                      </span>
                      <Separator class="flex-1" />
                    </div>

                    <!-- Comment bubble -->
                    <div class="flex gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-muted/40">
                      <UserPopover :size="28" :user="comment.user" class="shrink-0 mt-0.5" />
                      <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2">
                          <span class="text-sm font-semibold truncate">{{ comment.user.name }}</span>
                          <span
                            :title="comment.created_at"
                            class="text-[11px] text-muted-foreground/70 shrink-0"
                          >
                            {{ formatTime(comment.created_at) }}
                          </span>
                        </div>
                        <div
                          class="mt-0.5 text-sm text-foreground/90 leading-relaxed prose-sm prose-p:my-0.5"
                          v-html="comment.comment"
                        />
                      </div>
                    </div>
                  </div>
                </TransitionGroup>
              </div>
            </ScrollArea>
          </div>

          <!-- Empty state -->
          <div v-else class="flex flex-col items-center py-8 text-center">
            <div class="h-12 w-12 rounded-full bg-muted/60 flex items-center justify-center mb-3">
              <MessageCircleIcon class="h-6 w-6 text-muted-foreground/40" />
            </div>
            <p class="text-sm font-medium text-muted-foreground">{{ $t("Dar nėra komentarų") }}</p>
            <p class="text-xs text-muted-foreground/60 mt-1 max-w-[260px]">
              {{ $t("Palikite komentarą, kad pradėtumėte diskusiją su moderatoriumi ar koordinatoriumi.") }}
            </p>
          </div>

          <!-- Composer -->
          <div
            class="flex gap-3 rounded-lg border bg-muted/20 p-3 transition-colors"
            :class="{ 'border-primary/30 bg-primary/5': isFocused }"
          >
            <UserAvatar :size="28" class="shrink-0 mt-1" :user="currentUser" />
            <div class="flex-1 min-w-0 flex flex-col gap-2">
              <TiptapEditor
                v-model="text"
                preset="minimal"
                :html="true"
                :placeholder="$t('Rašykite komentarą...')"
                class="comment-composer"
                @focus="isFocused = true"
                @blur="isFocused = false"
              />
              <div class="flex justify-end">
                <Button
                  size="sm"
                  class="gap-1.5 h-8"
                  :disabled="!text || loading"
                  @click="submitComment"
                >
                  <Spinner v-if="loading" class="h-3.5 w-3.5" />
                  <SendIcon v-else class="h-3.5 w-3.5" />
                  {{ $t("Komentuoti") }}
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </CollapsibleContent>
    </Collapsible>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { router, usePage } from "@inertiajs/vue3";
import { formatRelativeTime } from "@/Utils/IntlTime";
import {
  ChevronDown as ChevronDownIcon,
  MessageCircle as MessageCircleIcon,
  Send as SendIcon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/collapsible";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Separator } from "@/Components/ui/separator";
import { Spinner } from "@/Components/ui/spinner";
import TiptapEditor from "@/Components/TipTap/TiptapEditor.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import UserPopover from "@/Components/Avatars/UserPopover.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  stage: number;
  comments: App.Entities.Comment[];
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const isOpen = ref(false);
const isFocused = ref(false);
const loading = ref(false);
const text = ref<string | null>(null);
const commentContainer = ref<HTMLElement | null>(null);

const latestCommentPreview = computed(() => {
  if (props.comments.length === 0) return "";
  const latest = props.comments[props.comments.length - 1];
  const name = latest.user?.name ?? "";
  // Strip HTML tags for preview
  const plainText = latest.comment?.replace(/<[^>]*>/g, "") ?? "";
  const truncated = plainText.length > 60 ? plainText.slice(0, 60) + "..." : plainText;
  return `${name}: ${truncated}`;
});

const isSameDay = (a: string, b: string): boolean => {
  return new Date(a).toDateString() === new Date(b).toDateString();
};

const shouldShowDateSeparator = (index: number): boolean => {
  if (index === 0) return true;
  return !isSameDay(props.comments[index - 1].created_at, props.comments[index].created_at);
};

const formatDateLabel = (dateStr: string): string => {
  const date = new Date(dateStr);
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 1);

  if (date.toDateString() === today.toDateString()) {
    return $t("Šiandien");
  }
  if (date.toDateString() === yesterday.toDateString()) {
    return $t("Vakar");
  }
  return date.toLocaleDateString(page.props.app?.locale ?? "lt-LT", {
    month: "long",
    day: "numeric",
  });
};

const formatTime = (dateStr: string): string => {
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffHours = diffMs / (1000 * 60 * 60);

  // Show relative time for recent comments (< 24h)
  if (diffHours < 24) {
    return formatRelativeTime(date, { numeric: "auto" }, page.props.app?.locale ?? "lt");
  }

  // Show time for older comments
  return date.toLocaleTimeString(page.props.app?.locale ?? "lt-LT", {
    hour: "2-digit",
    minute: "2-digit",
  });
};

const scrollToBottom = () => {
  nextTick(() => {
    const viewport = commentContainer.value?.closest('[data-slot="scroll-area-viewport"]');
    if (viewport) {
      viewport.scrollTo({ top: viewport.scrollHeight, behavior: "smooth" });
    }
  });
};

// Auto-scroll when new comments arrive
watch(() => props.comments.length, () => {
  scrollToBottom();
});

// Scroll when opened
watch(isOpen, (open) => {
  if (open && props.comments.length > 0) {
    scrollToBottom();
  }
});

const submitComment = () => {
  if (!text.value) return;

  loading.value = true;
  router.post(
    route("users.comments.store", page.props.auth?.user.id),
    {
      commentable_type: "planning_process",
      commentable_id: props.planningProcess.id,
      comment: text.value,
      stage: props.stage,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        text.value = null;
        loading.value = false;
      },
      onError: () => {
        loading.value = false;
      },
    }
  );
};
</script>

<style scoped>
.comment-composer :deep(.tiptap-toolbar) {
  display: none;
}

.comment-composer :deep(.tiptap-content) {
  border: none;
  background: transparent;
  min-height: 40px;
  padding: 0;
}

.comment-composer :deep(.tiptap-content .ProseMirror) {
  padding: 0;
  min-height: 40px;
  font-size: 0.875rem;
}

/* Transition for new comments */
.comment-enter-active {
  transition: all 0.3s ease-out;
}

.comment-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
</style>
