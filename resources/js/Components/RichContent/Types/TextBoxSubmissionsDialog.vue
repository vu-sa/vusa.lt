<template>
  <Dialog v-model:open="open">
    <DialogTrigger as-child>
      <button
        class="flex h-6 items-center gap-1 rounded px-1.5 text-[10px] font-medium text-zinc-500 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700 dark:hover:text-zinc-300 transition-colors"
        :title="$t('rich-content.text_box_view_answers')"
        @click="onOpen"
      >
        <IFluentCommentIcon class="h-3 w-3" />
        {{ $t('rich-content.text_box_view_answers') }}
      </button>
    </DialogTrigger>

    <DialogScrollContent class="sm:max-w-2xl">
      <DialogHeader>
        <div class="flex items-start justify-between gap-4">
          <div>
            <DialogTitle>{{ $t('rich-content.text_box_answers_title') }}</DialogTitle>
            <DialogDescription>
              {{ $t('rich-content.text_box_answers_description') }}
              <span v-if="totalCount !== null" class="ml-1 font-medium text-zinc-600 dark:text-zinc-300">
                ({{ totalCount }})
              </span>
            </DialogDescription>
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <a
              :href="exportUrl"
              target="_blank"
              class="flex items-center gap-1.5 rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700/80"
            >
              <IFluentArrowDownloadIcon class="h-3.5 w-3.5" />
              {{ $t('rich-content.text_box_export_excel') }}
            </a>

            <!-- Delete All -->
            <AlertDialog v-if="totalCount" v-model:open="deleteAllOpen">
              <AlertDialogTrigger as-child>
                <button
                  class="flex items-center gap-1.5 rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 shadow-sm transition-colors hover:bg-red-50 dark:border-red-800 dark:bg-zinc-800 dark:text-red-400 dark:hover:bg-red-950/30"
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
      </DialogHeader>

      <!-- Loading skeleton -->
      <div v-if="isFetching" class="space-y-3 py-4">
        <div v-for="i in 5" :key="i" class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-12 w-full" />
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="!submissions?.length" class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        {{ $t('rich-content.text_box_no_answers') }}
      </div>

      <!-- Submissions list -->
      <div v-else class="space-y-3 py-4">
        <div
          v-for="submission in submissions"
          :key="submission.id"
          class="group rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"
        >
          <div class="mb-2 flex items-center justify-between gap-2">
            <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ submission.submitted_by }}</span>
            <div class="flex items-center gap-2">
              <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ formatDate(submission.created_at) }}</span>
              <button
                class="rounded p-0.5 text-zinc-300 opacity-0 transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-zinc-600 dark:hover:bg-red-950/30 dark:hover:text-red-400"
                :title="$t('rich-content.text_box_delete')"
                @click="confirmDeleteOne(submission.id)"
              >
                <IFluentDeleteIcon class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
          <p class="whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ submission.text }}</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
        <Pagination
          :total="totalCount ?? 0"
          :items-per-page="perPage"
          :page="currentPage"
          :sibling-count="1"
          show-edges
          @update:page="goToPage"
        >
          <PaginationContent v-slot="{ items }" class="flex items-center gap-1">
            <PaginationFirst />
            <PaginationPrevious />
            <template v-for="item in items" :key="item.type === 'page' ? item.value : item.type">
              <PaginationItem v-if="item.type === 'page'" :value="item.value" as-child>
                <button
                  class="inline-flex h-8 w-8 items-center justify-center rounded-md text-xs transition-colors"
                  :class="item.value === currentPage
                    ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                    : 'hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300'"
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
import IFluentArrowDownloadIcon from '~icons/fluent/arrow-download24-regular';
import IFluentDeleteIcon from '~icons/fluent/delete-24-regular';

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
  })
);

const exportUrl = computed(() =>
  route('api.v1.admin.text-box-submissions.export', { content_part_id: props.contentPartId })
);

const deleteAllUrl = computed(() =>
  route('api.v1.admin.text-box-submissions.destroyAll', { content_part_id: props.contentPartId })
);

const { data: submissions, response, isFetching, execute } = useApi<Submission[]>(apiUrl, {
  immediate: false,
  showErrorToast: true,
});

const { execute: executeDeleteAll, isFetching: isDeletingAll } = useApiMutation(
  deleteAllUrl,
  'DELETE',
  undefined,
  { showSuccessToast: true }
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

function goToPage(page: number): void {
  currentPage.value = page;
}

watch(currentPage, () => {
  execute();
});

function formatDate(isoString: string): string {
  return new Date(isoString).toLocaleString();
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
        Accept: 'application/json',
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
      } else {
        await execute();
      }
    } else {
      toasts.error(data.message || 'An error occurred');
    }
  } catch {
    toasts.error('An error occurred');
  } finally {
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
