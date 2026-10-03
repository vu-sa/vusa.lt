<template>
  <div class="space-y-1.5">
    <Label :for="inputId" class="text-sm font-medium">
      {{ $t('Narys') }}
    </Label>

    <div
      v-if="modelValue"
      class="flex items-center justify-between gap-3 border border-border bg-card p-2.5"
      data-testid="member-search-selected"
    >
      <div class="flex min-w-0 items-center gap-2.5">
        <UserAvatar :user="modelValue" :size="32" class="shrink-0" />
        <div class="min-w-0">
          <p class="truncate text-sm font-medium text-foreground">
            {{ modelValue.name }}
          </p>
          <p v-if="modelValue.email" class="truncate text-xs text-muted-foreground">
            {{ modelValue.email }}
          </p>
        </div>
      </div>
      <Button type="button" variant="ghost" size="sm" class="u-touch shrink-0" @click="clear">
        <X class="size-4" />
        <span class="sr-only">{{ $t('Pakeisti narį') }}</span>
      </Button>
    </div>

    <template v-else>
      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          :id="inputId"
          v-model="query"
          role="combobox"
          class="pr-9 pl-9"
          autocomplete="off"
          aria-autocomplete="list"
          :aria-expanded="showList"
          :aria-controls="listId"
          :aria-activedescendant="activeOptionId"
          :placeholder="$t('Ieškok pagal vardą ar el. paštą…')"
          @keydown.down.prevent="move(1)"
          @keydown.up.prevent="move(-1)"
          @keydown.enter.prevent="chooseActive"
        />
        <Loader2
          v-if="isFetching"
          class="absolute right-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
        />
      </div>

      <!-- In the flow, not floating: the sheet body scrolls, so an absolute list would clip. -->
      <ul
        v-if="showList"
        :id="listId"
        role="listbox"
        :aria-label="$t('Paieškos rezultatai')"
        class="max-h-64 divide-y divide-border overflow-y-auto border border-border bg-card"
      >
        <li
          v-for="(hit, index) in results"
          :id="optionId(index)"
          :key="hit.id"
          role="option"
          :aria-selected="index === activeIndex"
          :aria-disabled="isTaken(hit)"
          :class="[
            'flex min-h-11 cursor-pointer items-center justify-between gap-3 px-3 py-2',
            index === activeIndex && 'bg-accent',
            isTaken(hit) ? 'cursor-not-allowed opacity-60' : 'hover:bg-accent',
          ]"
          tabindex="-1"
          @mousedown.prevent
          @click="choose(hit)"
          @keydown.enter.prevent="choose(hit)"
        >
          <div class="flex min-w-0 items-center gap-3">
            <UserAvatar :user="hit" :size="32" class="shrink-0" />
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-foreground">
                {{ hit.name }}
              </p>
              <p class="truncate text-xs text-muted-foreground">
                {{ isTaken(hit) ? $t('Jau eina šias pareigas') : hit.email }}
              </p>
            </div>
          </div>
          <span
            v-if="hit.tenants?.length"
            class="shrink-0 border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground"
          >
            {{ hit.tenants.join(', ') }}
          </span>
        </li>
        <li v-if="results.length === 0" class="px-3 py-4 text-center text-sm text-muted-foreground">
          {{ isFetching ? $t('Ieškoma…') : $t('Narių nerasta') }}
        </li>
      </ul>
    </template>

    <p v-if="error" class="text-xs text-destructive">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2, Search, X } from 'lucide-vue-next';
import { computed, ref, useId, watch } from 'vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useApi } from '@/Composables/useApi';

/** What `api.v1.admin.users.search` returns — not a full User entity. */
export interface MemberHit {
  id: string;
  name: string;
  /** Masked for people outside the acting admin's units. */
  email?: string;
  profile_photo_path?: string | null;
  tenants?: string[];
}

const props = withDefaults(defineProps<{
  modelValue: MemberHit | null;
  /** Members who already hold the duty; offered but not selectable. */
  takenIds?: string[];
  error?: string;
}>(), {
  takenIds: () => [],
  error: undefined,
});

const emit = defineEmits<(e: 'update:modelValue', value: MemberHit | null) => void>();

const uid = useId();
const inputId = `member-search-${uid}`;
const listId = `member-search-list-${uid}`;
const optionId = (index: number) => `member-search-option-${uid}-${index}`;

const query = ref('');
const activeIndex = ref(-1);
const searchUrl = ref('');

const { data, isFetching, execute } = useApi<MemberHit[]>(searchUrl, {
  immediate: false,
  showErrorToast: false,
});

const runSearch = useDebounceFn(() => {
  const term = query.value.trim();

  if (term.length < 2) {
    searchUrl.value = '';
    return;
  }

  // `permission` is required by the endpoint; `scope: all` because assigning a member from
  // another unit is how they join a new one (same choice as the duty wizard).
  const params = new URLSearchParams({ search: term, permission: 'duties.update.padalinys', scope: 'all' });
  searchUrl.value = `${route('api.v1.admin.users.search')}?${params.toString()}`;
  execute();
}, 250);

watch(query, () => {
  activeIndex.value = -1;
  runSearch();
});

const results = computed<MemberHit[]>(() => (query.value.trim().length >= 2 ? (data.value ?? []) : []));
const showList = computed(() => query.value.trim().length >= 2);
const activeOptionId = computed(() => (activeIndex.value >= 0 ? optionId(activeIndex.value) : undefined));

const isTaken = (hit: MemberHit) => props.takenIds.includes(hit.id);

const move = (step: 1 | -1) => {
  if (results.value.length === 0) {
    return;
  }

  activeIndex.value = (activeIndex.value + step + results.value.length) % results.value.length;
};

const choose = (hit: MemberHit) => {
  if (isTaken(hit)) {
    return;
  }

  emit('update:modelValue', hit);
  query.value = '';
};

const chooseActive = () => {
  const hit = results.value[activeIndex.value];

  if (hit) {
    choose(hit);
  }
};

const clear = () => emit('update:modelValue', null);
</script>
