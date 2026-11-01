<template>
  <MemberTermsSummary
    v-if="total > 0"
    :duties="terms"
    :current-count="currentCount"
    :total-count="total"
    :loading="isFetching"
    :empty-text="$t('Narių nėra')"
    @open="load"
  />
  <span v-else class="text-muted-foreground">—</span>
</template>

<script setup lang="ts">
import { computed, shallowRef } from 'vue';

import { useApi } from '@/Composables/useApi';
import { MemberTermsSummary } from '@/Features/Admin/Occupancy';

interface MemberTerm {
  name: string;
  start_date: string | null;
  end_date: string | null;
}

const props = defineProps<{
  institutionId: string;
  currentCount: number;
  totalCount: number;
}>();

// A list row loads no terms of its own: search holds the counts, the people come on opening.
const { data, isFetching, execute } = useApi<MemberTerm[]>(route('api.v1.admin.institutions.memberTerms', props.institutionId), {
  immediate: false,
  showErrorToast: false,
});
const loaded = shallowRef(false);

function load(): void {
  if (!loaded.value) {
    loaded.value = true;
    void execute();
  }
}

const total = computed(() => Math.max(props.totalCount, props.currentCount));
const terms = computed(() => (data.value ?? []).map(term => ({
  label: term.name,
  pivot: { start_date: term.start_date, end_date: term.end_date },
})));
</script>
