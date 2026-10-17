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
          <p class="flex items-center gap-1.5 truncate text-sm font-medium text-foreground">
            {{ modelValue.name }}
            <span v-if="modelValue.isNew" :class="chipClass" data-testid="member-search-new">{{ $t('Naujas narys') }}</span>
          </p>
          <p v-if="modelValue.email" class="truncate text-xs text-muted-foreground">
            {{ modelValue.email }}
          </p>
          <p v-else-if="modelValue.isNew" class="truncate text-xs text-muted-foreground">
            {{ $t('Be el. pašto') }}
          </p>
        </div>
      </div>
      <Button type="button" variant="ghost" size="sm" class="u-touch shrink-0" @click="clear">
        <X class="size-4" />
        <span class="sr-only">{{ $t('Pakeisti narį') }}</span>
      </Button>
    </div>

    <div
      v-else-if="creating"
      class="space-y-3 border border-border bg-secondary/40 p-3"
      data-testid="member-search-create"
    >
      <div class="space-y-1.5">
        <Label :for="`${inputId}-new-name`" class="text-xs font-medium">{{ $t('Vardas ir pavardė') }}</Label>
        <Input :id="`${inputId}-new-name`" v-model="draft.name" autocomplete="off" />
      </div>
      <div class="space-y-1.5">
        <div class="flex items-center justify-between">
          <Label :for="`${inputId}-new-email`" class="text-xs font-medium">{{ $t('El. paštas') }}</Label>
          <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
        </div>
        <Input :id="`${inputId}-new-email`" v-model="draft.email" type="email" autocomplete="off" />
        <p class="text-xs text-muted-foreground">
          {{ $t('Buvusiems nariams el. pašto galima nenurodyti — jie tiesiog negaus laiškų.') }}
        </p>
      </div>
      <DuplicateUserWarning :matches="duplicateMatches" show-use-action @use="useExisting" />
      <div class="flex flex-wrap gap-2">
        <Button type="button" size="sm" voice="sentence" variant="secondary" class="u-touch" :disabled="draft.name.trim().length < 3" @click="confirmNew">
          <Check class="size-4" />
          {{ $t('Naudoti šį narį') }}
        </Button>
        <Button type="button" size="sm" voice="sentence" variant="ghost" class="u-touch" @click="creating = false">
          {{ $t('Atšaukti') }}
        </Button>
      </div>
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
                <template v-if="isTaken(hit)">
                  {{ $t('Jau eina šias pareigas') }}
                </template>
                <template v-else>
                  {{ hit.email }}<template v-if="hit.is_current === false && hit.last_term_year">
                    <template v-if="hit.email">
                      ·
                    </template>
                    {{ $t('Buvęs narys') }} · {{ hit.last_term_year }}
                  </template>
                </template>
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
        <li v-if="results.length === 0 && !allowCreate" class="px-3 py-4 text-center text-sm text-muted-foreground">
          {{ isFetching ? $t('Ieškoma…') : $t('Narių nerasta') }}
        </li>
        <li
          v-if="allowCreate && !isFetching"
          role="option"
          :aria-selected="false"
          class="flex min-h-11 cursor-pointer items-center gap-3 px-3 py-2 text-sm hover:bg-accent"
          data-testid="member-search-create-option"
          @mousedown.prevent
          @click="startCreate"
        >
          <UserPlus class="size-4 shrink-0 text-muted-foreground" />
          <span class="min-w-0 truncate">{{ $t('Sukurti naują narį „:name“', { name: query.trim() }) }}</span>
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
import { Check, Loader2, Search, UserPlus, X } from '@lucide/vue';
import { computed, reactive, ref, useId, watch } from 'vue';

import DuplicateUserWarning from '@/Components/AdminForms/DuplicateUserWarning.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useApi } from '@/Composables/useApi';
import { useDuplicateUserCheck } from '@/Composables/useDuplicateUserCheck';

/** What `api.v1.admin.users.search` returns — not a full User entity. */
export interface MemberHit {
  id: string;
  name: string;
  /** Masked for people outside the acting admin's units. */
  email?: string;
  profile_photo_path?: string | null;
  tenants?: string[];
  /** False for someone whose every term has ended. */
  is_current?: boolean;
  last_term_year?: number | null;
  /** Not saved yet: created together with the term. */
  isNew?: boolean;
}

const props = withDefaults(defineProps<{
  modelValue: MemberHit | null;
  /** Members who already hold the duty; offered but not selectable. */
  takenIds?: string[];
  error?: string;
  /** Offer creating the person when they are not in the system yet. */
  allowCreate?: boolean;
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

const chipClass = 'border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground';

const creating = ref(false);
const draft = reactive({ name: '', email: '' });
const { matches: duplicateMatches } = useDuplicateUserCheck(() => (creating.value ? draft.name : ''), () => (creating.value ? draft.email : ''));

const startCreate = () => {
  draft.name = query.value.trim();
  draft.email = '';
  creating.value = true;
  query.value = '';
};

const confirmNew = () => {
  emit('update:modelValue', { id: '', name: draft.name.trim(), email: draft.email.trim() || undefined, isNew: true });
  creating.value = false;
};

const useExisting = (match: { id: string; name: string }) => {
  emit('update:modelValue', { id: String(match.id), name: match.name });
  creating.value = false;
};
</script>
