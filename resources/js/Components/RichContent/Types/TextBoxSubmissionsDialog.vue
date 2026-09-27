<template>
  <Dialog v-model:open="open">
    <DialogTrigger as-child>
      <Button
        variant="outline"
        size="sm"
        class="w-full justify-between"
        @click="onOpen"
      >
        <span class="flex items-center gap-2">
          <MessageSquare class="size-4" />
          {{ $t('rich-content.text_box_view_answers') }}
          <span
            v-if="totalCount !== null"
            class="border border-border bg-secondary px-2 py-0.5 text-xs tabular-nums text-muted-foreground"
          >
            {{ totalCount }}
          </span>
        </span>
        <ChevronRight class="size-4 text-muted-foreground" />
      </Button>
    </DialogTrigger>

    <DialogContent
      class="top-auto bottom-0 left-0 flex h-[92dvh] w-full max-w-none translate-x-0 translate-y-0 flex-col gap-0 overflow-hidden p-0 md:top-1/2 md:bottom-auto md:left-1/2 md:h-[min(85vh,46rem)] md:w-[min(96vw,42rem)] md:-translate-x-1/2 md:-translate-y-1/2 sm:max-w-none"
      :show-close-button="false"
    >
      <!-- Modal header -->
      <div class="border-b border-border px-4 py-4 md:px-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center border border-border bg-secondary">
              <MessageSquare class="size-5 text-muted-foreground" />
            </div>
            <div>
              <DialogTitle class="text-base font-semibold text-foreground">
                {{ $t('rich-content.text_box_answers_title') }}
              </DialogTitle>
              <DialogDescription class="mt-0.5 text-xs text-muted-foreground">
                {{ $t('rich-content.text_box_answers_description') }}
                <span v-if="totalCount !== null" class="ml-1 font-semibold text-foreground">
                  {{ totalCount }} {{ totalCount === 1 ? $t('rich-content.text_box_answer_singular') : $t('rich-content.text_box_answer_plural') }}
                </span>
              </DialogDescription>
            </div>
          </div>

          <!-- Actions toolbar -->
          <Button variant="ghost" size="icon" class="ml-auto" :aria-label="$t('rich-content.close_answers')" @click="open = false">
            <X class="size-4" />
          </Button>
          <div class="flex w-full flex-wrap items-center gap-2">
            <a
              :href="exportUrl"
              target="_blank"
              :class="buttonVariants({ variant: 'outline', size: 'sm' })"
            >
              <Download class="size-4" />
              {{ $t('rich-content.text_box_export_excel') }}
            </a>

            <AlertDialog v-if="totalCount" v-model:open="deleteAllOpen">
              <AlertDialogTrigger as-child>
                <Button
                  variant="outline"
                  size="sm"
                  class="text-destructive hover:text-destructive"
                  :disabled="isDeletingAll"
                >
                  <Trash2 class="size-4" />
                  {{ $t('rich-content.text_box_delete_all') }}
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>{{ $t('rich-content.text_box_delete_all_confirm_title') }}</AlertDialogTitle>
                  <AlertDialogDescription>{{ $t('rich-content.text_box_delete_all_confirm_description') }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>{{ $t('rich-content.cancel') }}</AlertDialogCancel>
                  <AlertDialogAction
                    class="bg-destructive text-white hover:bg-destructive/90"
                    @click="handleDeleteAll"
                  >
                    {{ $t('rich-content.text_box_delete_all') }}
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 md:px-6">
        <!-- Loading skeleton -->
        <div v-if="isFetching" class="space-y-3">
          <div v-for="i in 4" :key="i" class="flex gap-3">
            <Skeleton class="size-8 shrink-0" />
            <div class="flex-1 space-y-2 pt-0.5">
              <div class="flex items-center gap-2">
                <Skeleton class="h-3 w-24" />
                <Skeleton class="h-3 w-16" />
              </div>
              <Skeleton class="h-14 w-full" />
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="!submissions?.length" class="flex flex-col items-center justify-center py-14 text-center">
          <div class="mb-4 flex size-16 items-center justify-center border border-border bg-secondary">
            <MessageSquareOff class="size-8 text-muted-foreground" />
          </div>
          <p class="text-sm font-medium text-foreground">
            {{ $t('rich-content.text_box_no_answers') }}
          </p>
          <p class="mt-1 text-xs text-muted-foreground">
            {{ $t('rich-content.text_box_no_answers_hint') }}
          </p>
        </div>

        <!-- Submissions list -->
        <div v-else class="divide-y divide-border border-y border-border">
          <div
            v-for="submission in submissions"
            :key="submission.id"
            class="group relative py-4"
          >
            <div class="flex gap-3">
              <!-- Avatar -->
              <div class="flex size-8 shrink-0 items-center justify-center border border-border bg-secondary text-xs font-bold uppercase text-muted-foreground">
                {{ submission.submitted_by.charAt(0) }}
              </div>

              <!-- Card -->
              <div class="min-w-0 flex-1">
                <!-- Meta row -->
                <div class="mb-2 flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-foreground">{{ submission.submitted_by }}</span>
                    <span class="text-muted-foreground">·</span>
                    <span class="text-xs text-muted-foreground">{{ formatDate(submission.created_at) }}</span>
                  </div>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="text-destructive hover:text-destructive"
                    :aria-label="$t('rich-content.text_box_delete')"
                    @click="confirmDeleteOne(submission.id)"
                  >
                    <Trash2 class="size-4" />
                  </Button>
                </div>

                <!-- Text -->
                <p class="whitespace-pre-wrap text-sm leading-relaxed text-foreground">
                  {{ submission.text }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="mt-5 border-t border-border pt-4">
          <Pagination
            :total="totalCount ?? 0"
            :items-per-page="perPage"
            :page="currentPage"
            :sibling-count="1"
            show-edges
            @update:page="goToPage"
          >
            <PaginationContent v-slot="{ items }" class="flex items-center justify-center gap-1">
              <PaginationFirst />
              <PaginationPrevious />
              <template v-for="item in items" :key="item.type === 'page' ? item.value : item.type">
                <PaginationItem v-if="item.type === 'page'" :value="item.value" as-child>
                  <button
                    class="inline-flex size-9 items-center justify-center text-xs font-medium transition-colors pointer-coarse:size-11"
                    :class="item.value === currentPage
                      ? 'bg-primary text-primary-foreground'
                      : 'text-muted-foreground hover:bg-accent'"
                    :aria-label="`${$t('rich-content.page')} ${item.value}`"
                    :aria-current="item.value === currentPage ? 'page' : undefined"
                  >
                    {{ item.value }}
                  </button>
                </PaginationItem>
                <PaginationEllipsis v-else :key="item.type" :index="item.index" />
              </template>
              <PaginationNext />
              <PaginationLast />
            </PaginationContent>
          </Pagination>
        </div>
      </div>
    </DialogContent>
  </Dialog>

  <!-- Single delete confirmation -->
  <AlertDialog v-model:open="showDeleteOneDialog">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>{{ $t('rich-content.text_box_delete_confirm_title') }}</AlertDialogTitle>
        <AlertDialogDescription>{{ $t('rich-content.text_box_delete_confirm_description') }}</AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>{{ $t('rich-content.cancel') }}</AlertDialogCancel>
        <AlertDialogAction
          class="bg-destructive text-white hover:bg-destructive/90"
          :disabled="isDeletingOne"
          @click="handleDeleteOne"
        >
          {{ $t('rich-content.text_box_delete') }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { ChevronRight, Download, MessageSquare, MessageSquareOff, Trash2, X } from 'lucide-vue-next';

import { useApi, useApiMutation } from '@/Composables/useApi';
import { useToasts } from '@/Composables/useToasts';
import type { ApiResponse } from '@/Types/api.d';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button, buttonVariants } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
  DialogTrigger,
} from '@/Components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationFirst,
  PaginationItem,
  PaginationLast,
  PaginationNext,
  PaginationPrevious,
} from '@/Components/ui/pagination';

interface Submission {
  id: string;
  text: string;
  submitted_by: string;
  created_at: string;
}

const PER_PAGE = 20;

const props = defineProps<{
  contentPartId: number;
}>();

const toasts = useToasts();

const open = ref(false);
const currentPage = ref(1);
const pendingDeleteId = ref<string | null>(null);
const showDeleteOneDialog = ref(false);
const deleteAllOpen = ref(false);

const apiUrl = computed(() =>
  route('api.v1.admin.text-box-submissions.index', {
    content_part_id: props.contentPartId,
    page: currentPage.value,
    per_page: PER_PAGE,
  }),
);

const exportUrl = computed(() =>
  route('api.v1.admin.text-box-submissions.export', { content_part_id: props.contentPartId }),
);

const deleteAllUrl = computed(() =>
  route('api.v1.admin.text-box-submissions.destroyAll', { content_part_id: props.contentPartId }),
);

const deleteOneUrl = computed(() => {
  if (!pendingDeleteId.value) return '';
  return route('api.v1.admin.text-box-submissions.destroy', { submission: pendingDeleteId.value });
});

const { data: submissions, response, isFetching, execute } = useApi<Submission[]>(apiUrl, {
  immediate: true,
  showErrorToast: true,
});

const { execute: executeDeleteAll, isFetching: isDeletingAll } = useApiMutation(
  deleteAllUrl,
  'DELETE',
  undefined,
  { showSuccessToast: true },
);

const { execute: executeDeleteOne, isFetching: isDeletingOne, response: deleteOneResponse } = useApiMutation(
  deleteOneUrl,
  'DELETE',
  undefined,
  { showSuccessToast: false, showErrorToast: false },
);

const pagination = computed(() => {
  const raw = response.value as (ApiResponse<Submission[]> & { meta?: { pagination?: { total: number; last_page: number; per_page: number } } }) | null;
  return raw?.success ? raw?.meta?.pagination ?? null : null;
});

const totalCount = computed(() => pagination.value?.total ?? null);
const lastPage = computed(() => pagination.value?.last_page ?? 1);
const perPage = computed(() => pagination.value?.per_page ?? PER_PAGE);

function onOpen(): void {
  currentPage.value = 1;
  execute();
}

function goToPage(p: number): void {
  currentPage.value = p;
}

watch(currentPage, () => {
  execute();
});

function formatDate(isoString: string): string {
  return new Date(isoString).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function confirmDeleteOne(id: string): void {
  pendingDeleteId.value = id;
  showDeleteOneDialog.value = true;
}

async function handleDeleteOne(): Promise<void> {
  const id = pendingDeleteId.value;
  if (!id) {
    return;
  }

  await executeDeleteOne();

  if (deleteOneResponse.value?.success) {
    toasts.success(deleteOneResponse.value.message || 'Submission deleted');
    pendingDeleteId.value = null;
    if (submissions.value?.length === 1 && currentPage.value > 1) {
      currentPage.value -= 1;
    }
    else {
      await execute();
    }
  }
  else {
    toasts.error(deleteOneResponse.value?.message || 'An error occurred');
  }
}

async function handleDeleteAll(): Promise<void> {
  await executeDeleteAll();

  deleteAllOpen.value = false;
  currentPage.value = 1;
  await execute();
}
</script>
