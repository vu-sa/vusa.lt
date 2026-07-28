<template>
  <div class="flex flex-col gap-5">
    <RCSectionOptions v-model="options" />

    <Field>
      <FieldLabel>{{ $t('rich-content.person_quote_person') }}</FieldLabel>
      <CollectionSelectDialog
        v-model:open="pickerOpen"
        collection="users"
        :multiple="false"
        allow-empty
        :initial-hits="initialHits"
        :title="$t('rich-content.person_quote_person')"
        :confirm-label="$t('rich-content.confirm_selection')"
        @confirm="onConfirm">
        <template #trigger>
          <Button type="button" variant="outline" class="w-full justify-between font-normal">
            <span class="truncate" :class="{ 'text-muted-foreground': !content.snapshot.name }">
              {{ content.snapshot.name || $t('rich-content.select_person') }}
            </span>
            <IFluentChevronDown24Regular class="size-4 opacity-50" />
          </Button>
        </template>
      </CollectionSelectDialog>
    </Field>

    <div v-if="content.snapshot.photoUrl" class="flex items-center gap-3">
      <img :src="content.snapshot.photoUrl" :alt="content.snapshot.name"
        class="size-12 rounded-full object-cover ring-2 ring-white shadow dark:ring-zinc-800">
      <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('rich-content.person_quote_photo_hint') }}</span>
    </div>

    <Field>
      <FieldLabel>{{ $t('rich-content.person_quote_attribution') }}</FieldLabel>
      <Input v-model="content.snapshot.attribution" type="text" :placeholder="$t('rich-content.enter_attribution')" />
      <div v-if="attributionSuggestions.length > 0" class="mt-1.5 flex flex-wrap gap-1.5">
        <button
          v-for="suggestion in attributionSuggestions"
          :key="suggestion"
          type="button"
          class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-600 transition-colors hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
          @click="content.snapshot.attribution = suggestion">
          {{ suggestion }}
        </button>
      </div>
    </Field>

    <Field>
      <FieldLabel>{{ $t('rich-content.person_quote_quote') }}</FieldLabel>
      <TiptapEditor v-model="quote" preset="minimal" prose-style />
    </Field>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.person_quote_align') }}</FieldLabel>
        <ToggleGroup v-model="options.align" type="single" class="justify-start">
          <ToggleGroupItem value="start">{{ $t('rich-content.person_quote_align_start') }}</ToggleGroupItem>
          <ToggleGroupItem value="center">{{ $t('rich-content.person_quote_align_center') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
      <label class="flex items-center gap-2 pt-6 text-sm">
        <Checkbox v-model="options.showAvatar" />
        <span class="text-zinc-700 dark:text-zinc-300">{{ $t('rich-content.person_quote_show_avatar') }}</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * `person-quote` editor. Static — no resolver (see `ContentPartResolver`'s docblock):
 * picking a user snapshots their name/photo/attribution into `json_content.snapshot`
 * rather than storing a live reference, so the quote never re-renders a departed
 * person's current photo or duty. The attribution field is always freely editable;
 * `/users/{user}/attributions` only *seeds* it with duty-derived suggestions.
 */
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useApi } from '@/Composables/useApi';
import type { PersonQuote } from '@/Types/contentParts';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';

const content = defineModel<PersonQuote['json_content']>({ required: true });
const options = defineModel<PersonQuote['options']>('options', { required: true });

const pickerOpen = ref(false);

const quote = computed({
  get: () => content.value.quote,
  set: (value) => { content.value.quote = value; },
});

const initialHits = computed<NormalizedSearchHit[]>(() => {
  if (!content.value.snapshot.userId) return [];
  return [normalizeHit('users', { id: content.value.snapshot.userId, name: content.value.snapshot.name })];
});

interface AttributionResponse {
  name: string;
  photoUrl: string | null;
  attributions: string[];
}

const attributionUserId = ref<number | null>(null);
const attributionUrl = computed(() => (attributionUserId.value
  ? route('api.v1.admin.users.attributions', attributionUserId.value)
  : ''));

const { data: attributionData, execute: fetchAttributions } = useApi<AttributionResponse>(attributionUrl, {
  immediate: false,
  showErrorToast: false,
});

const attributionSuggestions = computed(() => attributionData.value?.attributions ?? []);

function onConfirm(hits: NormalizedSearchHit[]) {
  const hit = hits[0];
  if (!hit) {
    content.value.snapshot = { name: '' };
    return;
  }

  const userId = Number(hit.recordId);
  content.value.snapshot = { ...content.value.snapshot, userId, name: hit.title };
  attributionUserId.value = userId;
}

watch(attributionUserId, async (id) => {
  if (!id) return;
  await fetchAttributions();
}, { flush: 'post' });

watch(attributionData, (data) => {
  if (!data) return;
  content.value.snapshot.photoUrl = data.photoUrl ?? undefined;
  // Only seed the attribution if the author hasn't already typed one — this fires
  // every time a new person is picked, and must not clobber a manual edit.
  if (!content.value.snapshot.attribution && data.attributions[0]) {
    content.value.snapshot.attribution = data.attributions[0];
  }
});
</script>
