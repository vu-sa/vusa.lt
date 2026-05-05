<template>
  <Dialog v-model:open="open">
    <DialogTrigger as-child>
      <button
        class="flex items-center gap-2 rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm font-semibold text-green-700 shadow-sm transition-colors hover:border-green-400 hover:bg-green-100 dark:border-green-700 dark:bg-green-950/40 dark:text-green-300 dark:hover:border-green-600 dark:hover:bg-green-950/60"
        @click="onOpen"
      >
        <span class="flex items-center gap-2">
          <IFluentCommentIcon class="h-4 w-4 text-green-500 dark:text-green-400" />
          {{ $t('rich-content.text_box_view_answers') }}
          <span
            v-if="totalCount !== null"
            class="rounded-full bg-green-200 px-2 py-0.5 text-xs font-semibold tabular-nums text-green-700 dark:bg-green-900 dark:text-green-200"
          >
            {{ totalCount }}
          </span>
        </span>
        <IFluentChevronRightIcon class="h-4 w-4 text-green-400 dark:text-green-500" />
      </button>
    </DialogTrigger>

    <DialogScrollContent class="flex max-h-[85vh] flex-col gap-0 overflow-hidden p-0 sm:max-w-2xl">
      <!-- Modal header -->
      <div class="border-b border-zinc-100 px-6 py-5 pr-14 dark:border-zinc-800">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-950/60">
              <IFluentCommentIcon class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <DialogTitle class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $t('rich-content.text_box_answers_title') }}
              </DialogTitle>
              <DialogDescription class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $t('rich-content.text_box_answers_description') }}
                <span v-if="totalCount !== null" class="ml-1 font-semibold text-zinc-700 dark:text-zinc-200">
                  {{ totalCount }} {{ totalCount === 1 ? $t('rich-content.text_box_answer_singular') : $t('rich-content.text_box_answer_plural') }}
                </span>
              </DialogDescription>
            </div>
          </div>

          <!-- Actions toolbar -->
          <div class="flex shrink-0 items-center gap-2">
            <a
              :href="exportUrl"
              target="_blank"
              class="flex items-center gap-1.5 rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-600 shadow-sm transition-colors hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700"
            >
              <IFluentArrowDownloadIcon class="h-3.5 w-3.5" />
              {{ $t('rich-content.text_box_export_excel') }}
            </a>

            <AlertDialog v-if="totalCount" v-model:open="deleteAllOpen">
              <AlertDialogTrigger as-child>
                <button
                  class="flex items-center gap-1.5 rounded-md border border-red-200 bg-white px-2.5 py-1.5 text-xs font-medium text-red-500 shadow-sm transition-colors hover:border-red-300 hover:bg-red-50 dark:border-red-900 dark:bg-zinc-800 dark:text-red-400 dark:hover:bg-red-950/20"
                  :disabled="isDeletingAll"
                >
                  <IFluentDeleteIcon class="h-3.5 w-3.5" />
                  {{ $t('rich-content.text_box_delete_all') }}
                </button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>{{ $t('rich-content.text_box_delete_all_confirm_title') }}</AlertDialogTitle>
                  <AlertDialogDescription>{{ $t('rich-content.text_box_delete_all_confirm_description') }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>{{ $t('rich-content.cancel') }}</AlertDialogCancel>
                  <AlertDialogAction
                    class="bg-red-600 text-white hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600"
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
      <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
        <!-- Loading skeleton -->
        <div v-if="isFetching" class="space-y-3">
          <div v-for="i in 4" :key="i" class="flex gap-3">
            <Skeleton class="h-8 w-8 shrink-0 rounded-full" />
            <div class="flex-1 space-y-2 pt-0.5">
              <div class="flex items-center gap-2">
                <Skeleton class="h-3 w-24" />
                <Skeleton class="h-3 w-16" />
              </div>
              <Skeleton class="h-14 w-full rounded-lg" />
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="!submissions?.length" class="flex flex-col items-center justify-center py-14 text-center">
          <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <IFluentCommentOffIcon class="h-8 w-8 text-zinc-400 dark:text-zinc-500" />
          </div>
          <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
            {{ $t('rich-content.text_box_no_answers') }}
          </p>
          <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-600">
            {{ $t('rich-content.text_box_no_answers_hint') }}
          </p>
        </div>

        <!-- Submissions list -->
        <div v-else class="space-y-3">
          <div
            v-for="(submission, i) in submissions"
            :key="submission.id"
            class="group relative"
          >
            <div class="flex gap-3">
              <!-- Avatar -->
              <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold uppercase"
                :class="avatarClass(i)"
              >
                {{ submission.submitted_by.charAt(0) }}
              </div>

              <!-- Card -->
              <div class="flex-1 rounded-xl border border-zinc-200 bg-white p-3.5 shadow-xs dark:border-zinc-700 dark:bg-zinc-800/60">
                <!-- Meta row -->
                <div class="mb-2 flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ submission.submitted_by }}</span>
                    <span class="text-zinc-300 dark:text-zinc-600">·</span>
                    <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ formatDate(submission.created_at) }}</span>
                  </div>
                  <button
                    class="rounded-md p-1 text-zinc-300 opacity-0 transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-zinc-600 dark:hover:bg-red-950/30 dark:hover:text-red-400"
                    :title="$t('rich-content.text_box_delete')"
                    @click="confirmDeleteOne(submission.id)"
                  >
                    <IFluentDeleteIcon class="h-3.5 w-3.5" />
                  </button>
                </div>

                <!-- Text -->
                <p class="whitespace-pre-wrap text-sm leading-relaxed text-zinc-800 dark:text-zinc-200">
                  {{ submission.text }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="mt-5 border-t border-zinc-100 pt-4 dark:border-zinc-800">
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
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-xs font-medium transition-colors"
                    :class="item.value === currentPage
                      ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                      : 'hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-400'"
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
    </DialogScrollContent>
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
          class="bg-red-600 text-white hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600"
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
import { usePage } from '@inertiajs/vue3';

import { useApi, useApiMutation } from '@/Composables/useApi';
import { useToasts } from '@/Composables/useToasts';
import type { ApiResponse } from '@/Types/api.d';
import { Skeleton } from '@/Components/ui/skeleton';
import {
  Dialog,
  DialogScrollContent,
  DialogHeader,
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
import IFluentCommentIcon from '~icons/fluent/comment-24-regular';
import IFluentCommentOffIcon from '~icons/fluent/comment-off-24-regular';
import IFluentArrowDownloadIcon from '~icons/fluent/arrow-download24-regular';
import IFluentDeleteIcon from '~icons/fluent/delete-24-regular';
import IFluentChevronRightIcon from '~icons/fluent/chevron-right-24-regular';

interface Submission {
  id: string;
  text: string;
  submitted_by: string;
  created_at: string;
}

const AVATAR_COLORS = [
  'bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
  'bg-violet-100 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400',
  'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
  'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
  'bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400',
  'bg-cyan-100 text-cyan-600 dark:bg-cyan-950/60 dark:text-cyan-400',
];

const PER_PAGE = 20;

const props = defineProps<{
  contentPartId: number;
}>();

const page = usePage();
const toasts = useToasts();

const open = ref(false);
const currentPage = ref(1);
const pendingDeleteId = ref<string | null>(null);
const showDeleteOneDialog = ref(false);
const deleteAllOpen = ref(false);
const isDeletingOne = ref(false);

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

const pagination = computed(() => {
  const raw = response.value as (ApiResponse<Submission[]> & { meta?: { pagination?: { total: number; last_page: number; per_page: number } } }) | null;
  return raw?.success ? raw?.meta?.pagination ?? null : null;
});

const totalCount = computed(() => pagination.value?.total ?? null);
const lastPage = computed(() => pagination.value?.last_page ?? 1);
const perPage = computed(() => pagination.value?.per_page ?? PER_PAGE);

function avatarClass(index: number): string {
  return AVATAR_COLORS[index % AVATAR_COLORS.length];
}

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
  if (!id) { return; }

  isDeletingOne.value = true;

  try {
    const csrfToken = (page.props.csrf_token as string) || '';
    const url = route('api.v1.admin.text-box-submissions.destroy', { submission: id });

    const res = await fetch(url, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
    });

    const data = await res.json();

    if (data.success) {
      toasts.success(data.message || 'Submission deleted');
      pendingDeleteId.value = null;
      if (submissions.value?.length === 1 && currentPage.value > 1) {
        currentPage.value -= 1;
      }
      else {
        await execute();
      }
    }
    else {
      toasts.error(data.message || 'An error occurred');
    }
  }
  catch {
    toasts.error('An error occurred');
  }
  finally {
    isDeletingOne.value = false;
  }
}

async function handleDeleteAll(): Promise<void> {
  await executeDeleteAll();

  deleteAllOpen.value = false;
  currentPage.value = 1;
  await execute();
}
</script>
