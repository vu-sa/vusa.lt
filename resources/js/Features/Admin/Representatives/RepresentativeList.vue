<template>
  <div class="flex flex-col gap-4" data-slot="representative-list">
    <div class="grid gap-3 border-y border-border py-4 sm:grid-cols-3">
      <label class="space-y-2">
        <span class="text-xs font-bold uppercase tracking-wide">{{ $t('representatives.list.search') }}</span>
        <Input v-model="search" type="search" :placeholder="$t('representatives.list.search_placeholder')" data-testid="rep-search" />
      </label>
      <label class="space-y-2">
        <span class="text-xs font-bold uppercase tracking-wide">{{ $t('representatives.list.institution') }}</span>
        <NativeSelect v-model="institution" :options="institutionOptions" :placeholder="$t('representatives.list.all_institutions')" placeholder-selectable data-testid="rep-institution" />
      </label>
      <label class="space-y-2">
        <span class="text-xs font-bold uppercase tracking-wide">{{ $t('representatives.list.activity') }}</span>
        <NativeSelect v-model="filter" :options="filterOptions" data-testid="rep-filter" />
      </label>
    </div>

    <p v-if="reps.length" class="text-sm text-muted-foreground" data-slot="representative-list-count">
      {{ $t('representatives.list.found', { count: String(filtered.length) }) }}
    </p>

    <ul v-if="filtered.length" class="divide-y divide-border">
      <li v-for="rep in shown" :key="rep.id" class="py-3" :data-rep="rep.id">
        <CollectionRow>
          <template #lead>
            <UserAvatar :user="{ name: rep.name, profile_photo_path: rep.photo }" :size="36" />
          </template>
          <Link :href="route('representatives.show', rep.id)" class="flex min-h-11 flex-col gap-2 focus-visible:outline-2 focus-visible:outline-ring md:flex-row md:items-center md:gap-6">
            <span class="min-w-0 flex-1">
              <span class="block break-words text-sm font-semibold">{{ rep.name }}</span>
              <span class="mt-0.5 block text-xs text-muted-foreground">{{ institutionNames(rep) }}</span>
              <span v-if="showsReason(rep)" class="mt-0.5 block text-xs text-status-attention" data-slot="rep-attention">{{ attentionText(rep) }}</span>
            </span>
            <span class="flex flex-col gap-1.5 text-xs text-muted-foreground md:w-64">
              <span v-if="rep.incoming">{{ startsText(rep) }}</span>
              <span v-else :class="rep.activity === 'never' ? 'text-status-danger' : undefined" data-slot="rep-login">{{ lastLoginText(rep) }}</span>
              <span v-if="rep.meetings.held > 0" data-slot="rep-meetings">
                {{ $t('representatives.list.recorded', { recorded: String(rep.meetings.recorded), held: String(rep.meetings.held) }) }}
              </span>
              <span v-if="rep.learning && rep.learning.essentialTotal > 0" class="flex items-center gap-2" data-slot="rep-learning">
                <span class="shrink-0 tabular-nums">{{ $t('representatives.list.essentials', { done: String(rep.learning.essentialDone), total: String(rep.learning.essentialTotal) }) }}</span>
                <span
                  role="progressbar"
                  :aria-label="$t('representatives.rep.essentials')"
                  :aria-valuenow="rep.learning.essentialDone"
                  :aria-valuemax="rep.learning.essentialTotal"
                  aria-valuemin="0"
                  class="h-1 flex-1 bg-muted"
                >
                  <span class="block h-full bg-muted-foreground" :style="{ width: `${(rep.learning.essentialDone / rep.learning.essentialTotal) * 100}%` }" />
                </span>
              </span>
            </span>
          </Link>
        </CollectionRow>
      </li>
    </ul>
    <EmptyState v-else :title="$t(reps.length ? 'representatives.list.no_results' : 'representatives.list.empty')">
      <template v-if="reps.length" #action>
        <Button variant="outline" voice="sentence" @click="clear">
          {{ $t('representatives.list.clear') }}
        </Button>
      </template>
    </EmptyState>

    <Button v-if="shown.length < filtered.length" variant="outline" voice="sentence" class="self-start" data-slot="representative-list-more" @click="pages++">
      {{ $t('representatives.list.more') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { NativeSelect } from '@/Components/ui/native-select';

import { attentionText, institutionNames, lastLoginText, startsText } from './representativeText';
import type { RepListFilter, RepRow } from './types';

const PAGE_SIZE = 50;

const props = defineProps<{ reps: RepRow[] }>();

const filter = defineModel<RepListFilter>('filter', { default: 'all' });
const search = ref('');
const institution = ref<string | number | null>('');
const pages = ref(1);

const institutionOptions = computed(() => [...new Map(props.reps.flatMap(rep => rep.institutions).map(item => [item.id, item])).values()]
  .sort((a, b) => a.name.localeCompare(b.name))
  .map(item => ({ value: item.id, label: item.tenant ? `${item.name} (${item.tenant})` : item.name })));

const filterOptions = computed(() => [
  { value: 'all', label: $t('representatives.list.any_activity') },
  { value: 'attention', label: $t('representatives.list.attention') },
  { value: 'week', label: $t('representatives.activity.week') },
  { value: 'month', label: $t('representatives.activity.month') },
  { value: 'stale', label: $t('representatives.activity.stale') },
  { value: 'never', label: $t('representatives.activity.never') },
  { value: 'incoming', label: $t('representatives.list.incoming') },
]);

// Login reasons already show in the activity column; only reasons it cannot say get a line.
function showsReason(rep: RepRow): boolean {
  return rep.attention?.reason === 'institution_overdue' || rep.attention?.reason === 'essentials_not_started';
}

function matchesFilter(rep: RepRow): boolean {
  switch (filter.value) {
    case 'all': return true;
    case 'attention': return rep.attention !== null;
    case 'incoming': return rep.incoming;
    // Activity buckets count reps already in office, as the bar above does.
    default: return !rep.incoming && rep.activity === filter.value;
  }
}

const filtered = computed(() => {
  const query = search.value.trim().toLocaleLowerCase();

  return props.reps.filter(rep => matchesFilter(rep)
    && (!institution.value || rep.institutions.some(item => item.id === institution.value))
    && `${rep.name} ${institutionNames(rep)}`.toLocaleLowerCase().includes(query));
});

const shown = computed(() => filtered.value.slice(0, pages.value * PAGE_SIZE));

watch([filter, search, institution], () => {
  pages.value = 1;
});

function clear(): void {
  search.value = '';
  institution.value = '';
  filter.value = 'all';
}
</script>
