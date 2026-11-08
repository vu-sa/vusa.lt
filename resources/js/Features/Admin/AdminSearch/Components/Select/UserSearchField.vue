<template>
  <InlineSearchField
    v-model:query="query"
    :label
    :input-id
    :aria-label
    :placeholder="placeholder ?? $t('Ieškok pagal vardą ar el. paštą…')"
    :hits="rows"
    :selected
    selected-test-id="member-search-selected"
    :clear-label="$t('Pakeisti narį')"
    :empty-message="$t('Narių nerasta')"
    :hide-empty="allowCreate"
    :searching="isFetching"
    :is-disabled
    :error
    @choose="choose"
    @clear="emit('update:modelValue', null)"
  >
    <template v-if="modelValue" #selected>
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
    </template>
    <template v-if="creating" #body>
      <div
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
    </template>
    <template #after-hits>
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
    </template>
  </InlineSearchField>
</template>

<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Check, UserPlus } from '@lucide/vue';
import { computed, reactive, ref, useId, watch } from 'vue';

import DuplicateUserWarning from '@/Components/AdminForms/DuplicateUserWarning.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useApi } from '@/Composables/useApi';
import { useDuplicateUserCheck } from '@/Composables/useDuplicateUserCheck';

import { normalizeUserApiHit, type NormalizedSearchHit } from '../../Utils/searchHitMappers';

import InlineSearchField from './InlineSearchField.vue';

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
  permission: string;
  scope?: 'all';
  label?: string;
  placeholder?: string;
  inputId?: string;
  ariaLabel?: string;
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
const inputId = computed(() => props.inputId ?? `member-search-${uid}`);
const query = ref('');
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

  const params = new URLSearchParams({ search: term, permission: props.permission });
  if (props.scope) {
    params.set('scope', props.scope);
  }
  searchUrl.value = `${route('api.v1.admin.users.search')}?${params.toString()}`;
  execute();
}, 250);

watch(query, runSearch);

const results = computed<MemberHit[]>(() => (query.value.trim().length >= 2 ? (data.value ?? []) : []));
const isTaken = (hit: MemberHit) => props.takenIds.includes(hit.id);
const rows = computed(() => results.value.map(hit => normalizeUserApiHit(hit, { taken: isTaken(hit) })));
const selected = computed(() => props.modelValue ? normalizeUserApiHit(props.modelValue) : null);
const isDisabled = (hit: NormalizedSearchHit) => props.takenIds.includes(hit.recordId);
const choose = (hit: NormalizedSearchHit) => {
  const user = results.value.find(user => user.id === hit.recordId);
  if (!user || isTaken(user)) {
    return;
  }
  emit('update:modelValue', user);
  query.value = '';
};

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
