<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { NativeSelect } from '@/Components/ui/native-select';
import type { LearningRepresentative } from '@/Types/learning';
import { formatNearDate } from '@/Utils/dateTime';

const props = defineProps<{ reps: LearningRepresentative[] }>();
const search = ref('');
const institution = ref<string | number | null>('');
const institutions = computed(() => [...new Map(props.reps.flatMap(rep => rep.institutions).map(item => [item.id, item])).values()]
  .sort((a, b) => a.title.localeCompare(b.title))
  .map(item => ({ value: item.id, label: item.title })));
const progress = (rep: LearningRepresentative) => rep.totals.essentialTotal ? rep.totals.essentialDone / rep.totals.essentialTotal : 0;
const filtered = computed(() => props.reps.filter(rep => {
  const query = search.value.trim().toLocaleLowerCase();
  return (!institution.value || rep.institutions.some(item => item.id === institution.value))
    && `${rep.name} ${rep.institutions.map(item => item.title).join(' ')}`.toLocaleLowerCase().includes(query);
}).sort((a, b) => Number(b.incoming) - Number(a.incoming) || progress(a) - progress(b) || a.name.localeCompare(b.name)));
function clear() {
  search.value = '';
  institution.value = '';
}
</script>

<template>
  <div class="space-y-4" data-slot="learning-reps">
    <div class="grid gap-3 border-y border-border py-4 sm:grid-cols-2">
      <label class="space-y-2">
        <span class="text-xs font-bold uppercase tracking-wide">{{ $t('learning_people.search') }}</span>
        <Input v-model="search" type="search" :placeholder="$t('learning_people.search_placeholder')" data-testid="rep-search" />
      </label>
      <label class="space-y-2">
        <span class="text-xs font-bold uppercase tracking-wide">{{ $t('learning_people.institution') }}</span>
        <NativeSelect v-model="institution" :options="institutions" :placeholder="$t('learning_people.all_institutions')" placeholder-selectable data-testid="rep-institution" />
      </label>
    </div>
    <ul v-if="filtered.length" class="divide-y divide-border">
      <li v-for="rep in filtered" :key="rep.id" class="py-4" :data-rep="rep.id">
        <CollectionRow>
          <Link :href="route('learningPath.rep', rep.id)" class="flex min-h-11 flex-col gap-3 focus-visible:outline-2 focus-visible:outline-ring md:flex-row md:items-center md:gap-6">
            <div class="min-w-0 flex-1">
              <p class="break-words text-sm font-semibold">{{ rep.name }}</p>
              <p class="mt-1 text-xs text-muted-foreground">{{ rep.institutions.map(item => item.title).join(' · ') }}</p>
              <p v-if="rep.incoming" class="mt-1 text-xs text-muted-foreground">{{ $t('learning_people.incoming') }}</p>
            </div>
            <div class="space-y-2 md:w-60">
              <p class="flex flex-wrap justify-between gap-x-3 text-xs tabular-nums">
                <span>{{ $t('learning_people.essentials') }} {{ rep.totals.essentialDone }}/{{ rep.totals.essentialTotal }}</span>
                <span class="text-muted-foreground">{{ $t('learning_people.total') }} {{ rep.totals.allDone }}/{{ rep.totals.allTotal }}</span>
              </p>
              <div role="progressbar" :aria-label="$t('learning_people.essentials')" :aria-valuenow="rep.totals.essentialDone" :aria-valuemax="rep.totals.essentialTotal || 1" aria-valuemin="0" class="h-1 bg-muted">
                <div class="h-full bg-muted-foreground" :style="{ width: `${progress(rep) * 100}%` }" />
              </div>
              <p class="text-xs text-muted-foreground">{{ $t('learning_people.last_activity') }}: {{ rep.lastActivity ? formatNearDate(rep.lastActivity) : $t('learning_people.no_activity') }}</p>
            </div>
          </Link>
        </CollectionRow>
      </li>
    </ul>
    <EmptyState v-else :title="$t(reps.length ? 'learning_people.no_results' : 'learning_people.no_reps')">
      <template v-if="reps.length" #action>
        <Button variant="outline" voice="sentence" @click="clear">{{ $t('learning_people.clear_filters') }}</Button>
      </template>
    </EmptyState>
  </div>
</template>
