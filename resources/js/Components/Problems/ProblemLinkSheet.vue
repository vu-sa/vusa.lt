<template>
  <Sheet v-model:open="open">
    <SheetContent side="right" class="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
      <SheetHeader class="border-b border-border px-6 py-4">
        <SheetTitle>{{ $t('Susieti problemą') }}</SheetTitle>
        <SheetDescription>{{ $t('Pasirink problemą, kurią šiame klausime kėlė atstovai.') }}</SheetDescription>
      </SheetHeader>

      <div class="border-b border-border px-6 py-3">
        <Input
          v-model="term"
          type="search"
          :placeholder="$t('Ieškoti problemų…')"
          :aria-label="$t('Ieškoti problemų…')"
          data-testid="problem-link-search"
        />
      </div>

      <div class="min-w-0 flex-1 overflow-y-auto">
        <p v-if="isFetching && !candidates.length" class="px-6 py-4 text-sm text-muted-foreground">
          {{ $t('Ieškoma…') }}
        </p>
        <p v-else-if="!candidates.length" class="px-6 py-4 text-sm text-muted-foreground">
          {{ $t('Nerasta atvirų ar vykdomų problemų.') }}
        </p>
        <ul v-else class="divide-y divide-border">
          <li v-for="problem in candidates" :key="problem.id">
            <button
              type="button"
              class="flex min-h-11 w-full items-center gap-3 px-6 py-3 text-left transition-colors hover:bg-accent disabled:opacity-50"
              :disabled="linking"
              data-testid="problem-link-option"
              @click="link(problem)"
            >
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium">{{ titleOf(problem) }}</span>
                <span v-if="problem.tenant?.shortname" class="block text-xs text-muted-foreground">{{ problem.tenant.shortname }}</span>
              </span>
              <StatusBadge v-if="statusOf(problem)" :status="statusOf(problem)!" class="shrink-0" />
            </button>
          </li>
        </ul>
      </div>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import { StatusBadge } from '@/Components/Patterns';
import { Input } from '@/Components/ui/input';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { useApi } from '@/Composables/useApi';
import { problemStatuses, type ProblemStatus } from '@/Constants/statuses';

interface Candidate {
  id: string;
  title: string | Record<string, string>;
  status: string;
  tenant?: { shortname: string } | null;
}

const props = defineProps<{
  open: boolean;
  agendaItemId: string;
  /** Already linked; left out of the list. */
  linkedIds: string[];
}>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const open = computed({ get: () => props.open, set: (value: boolean) => emit('update:open', value) });
const term = ref('');
const linking = ref(false);

// Unresolved problems only: a resolved one is rarely what a meeting raises.
const url = computed(() => {
  const params = new URLSearchParams({
    per_page: '20',
    filters: JSON.stringify({ status: ['open', 'in_progress'] }),
  });

  if (term.value.trim()) {
    params.set('search', term.value.trim());
  }

  return `${route('api.v1.admin.problems.index')}?${params.toString()}`;
});

const { data, isFetching, execute } = useApi<{ items: Candidate[] }>(url, { immediate: false, showErrorToast: false });
const search = useDebounceFn(() => execute(), 250);

watch(term, () => search());
watch(open, (isOpen) => {
  if (isOpen) {
    execute();
  }
}, { immediate: true });

const candidates = computed(() => (data.value?.items ?? []).filter(problem => !props.linkedIds.includes(problem.id)));

const titleOf = (problem: Candidate): string => typeof problem.title === 'string'
  ? problem.title
  : problem.title[getActiveLanguage()] || problem.title.lt || problem.title.en || '';
const statusOf = (problem: Candidate) => problemStatuses[problem.status as ProblemStatus];

function link(problem: Candidate): void {
  linking.value = true;

  router.post(route('agendaItems.problems.store', props.agendaItemId), { problem_id: problem.id }, {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false;
      term.value = '';
    },
    onFinish: () => {
      linking.value = false;
    },
  });
}
</script>
