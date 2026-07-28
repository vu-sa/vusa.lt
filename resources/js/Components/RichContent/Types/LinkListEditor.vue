<template>
  <div class="flex flex-col gap-5">
    <RCSectionOptions v-model="options" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.link_list_source') }}</FieldLabel>
        <ToggleGroup v-model="options.source" type="single" class="justify-start flex-wrap">
          <ToggleGroupItem value="news">{{ $t('rich-content.link_list_source_news') }}</ToggleGroupItem>
          <ToggleGroupItem value="pages">{{ $t('rich-content.link_list_source_pages') }}</ToggleGroupItem>
          <ToggleGroupItem value="manual">{{ $t('rich-content.link_list_source_manual') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.style') }}</FieldLabel>
        <ToggleGroup v-model="options.style" type="single" class="justify-start">
          <ToggleGroupItem value="photo">{{ $t('rich-content.link_list_style_photo') }}</ToggleGroupItem>
          <ToggleGroupItem value="compact">{{ $t('rich-content.link_list_style_compact') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
    </div>

    <!-- Manual links -->
    <DynamicListInput
      v-if="options.source === 'manual'"
      v-model="manualLinks"
      :create-item="createManualLink"
      :empty-text="$t('rich-content.no_links')"
      :add-first-text="$t('rich-content.add_first_link')"
      :add-text="$t('rich-content.add_link')"
      compact
      allow-empty>
      <template #item="{ item, update }">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Field>
            <FieldLabel>{{ $t('rich-content.link_title') }}</FieldLabel>
            <Input :model-value="item.title" type="text" :placeholder="$t('rich-content.enter_link_title')"
              @update:model-value="update({ ...item, title: $event })" />
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.link_url') }}</FieldLabel>
            <Input :model-value="item.url" type="url" placeholder="https://…"
              @update:model-value="update({ ...item, url: $event })" />
          </Field>
        </div>
      </template>
    </DynamicListInput>

    <!-- News/pages sourcing -->
    <template v-else>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field>
          <FieldLabel>{{ $t('rich-content.link_list_mode') }}</FieldLabel>
          <ToggleGroup v-model="options.mode" type="single" class="justify-start">
            <ToggleGroupItem value="latest">{{ $t('rich-content.link_list_mode_latest') }}</ToggleGroupItem>
            <ToggleGroupItem value="specific">{{ $t('rich-content.link_list_mode_specific') }}</ToggleGroupItem>
          </ToggleGroup>
        </Field>
        <Field>
          <FieldLabel>{{ $t('rich-content.limit') }}</FieldLabel>
          <NumberField :model-value="options.limit ?? 3" :min="1" :max="12"
            @update:model-value="options.limit = $event" />
        </Field>
      </div>

      <Field v-if="options.mode === 'specific'">
        <FieldLabel>{{ pinnedFieldLabel }}</FieldLabel>
        <CollectionSelectDialog
          v-model:open="pickerOpen"
          :collection="pinnedCollection"
          multiple
          allow-empty
          :initial-hits="initialHits"
          :title="pinnedFieldLabel"
          :confirm-label="$t('rich-content.confirm_selection')"
          @confirm="onConfirm">
          <template #trigger>
            <Button type="button" variant="outline" class="w-full justify-between font-normal">
              <span class="truncate" :class="{ 'text-muted-foreground': pinnedSnapshot.length === 0 }">
                {{ pinnedSummary }}
              </span>
              <IFluentChevronDown24Regular class="size-4 opacity-50" />
            </Button>
          </template>
        </CollectionSelectDialog>
      </Field>
      <Field v-else>
        <FieldLabel>{{ $t('rich-content.category_alias') }}</FieldLabel>
        <Input v-model="options.categoryAlias" type="text" placeholder="freshmen-camps" />
      </Field>

      <Field>
        <FieldLabel>{{ $t('rich-content.tenant_scope') }}</FieldLabel>
        <ToggleGroup :model-value="tenantScopeToggle" type="single" class="justify-start" @update:model-value="onTenantScopeChange">
          <ToggleGroupItem value="current">{{ $t('rich-content.tenant_scope_current') }}</ToggleGroupItem>
          <ToggleGroupItem value="all">{{ $t('rich-content.tenant_scope_all') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
    </template>
  </div>
</template>

<script setup lang="ts">
/**
 * `link-list` editor. `source: 'manual'` stores author-typed links directly;
 * `'news'`/`'pages'` are resolved server-side (see `LinkListResolver`), so only the
 * *selector* (pinned ids, category, tenant scope, limit) is stored — never the
 * resolved titles/hrefs, which would go stale.
 *
 * `pinnedNews`/`pinnedPages` in json_content are editor-only bookkeeping: without a
 * title to render, `CollectionSelectDialog` can't show a previously-pinned item as
 * checked when the block is reopened (see the type's doc comment).
 */
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { LinkList } from '@/Types/contentParts';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { NumberField } from '@/Components/ui/number-field';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';

const content = defineModel<LinkList['json_content']>({ required: true });
const options = defineModel<LinkList['options']>('options', { required: true });

const pickerOpen = ref(false);

const manualLinks = computed({
  get: () => content.value.links ?? [],
  set: (value) => { content.value.links = value; },
});

function createManualLink(): LinkList['json_content']['links'][number] {
  return { title: '', url: '' };
}

const pinnedCollection = computed<'news' | 'pages'>(() => (options.value.source === 'pages' ? 'pages' : 'news'));

const pinnedFieldLabel = computed(() => (pinnedCollection.value === 'pages'
  ? $t('rich-content.pinned_pages')
  : $t('rich-content.pinned_news')));

const pinnedSnapshot = computed(() => (pinnedCollection.value === 'pages'
  ? content.value.pinnedPages ?? []
  : content.value.pinnedNews ?? []));

const initialHits = computed<NormalizedSearchHit[]>(() => pinnedSnapshot.value.map(
  item => normalizeHit(pinnedCollection.value, { id: item.id, title: item.title }),
));

const pinnedSummary = computed(() => (pinnedSnapshot.value.length > 0
  ? pinnedSnapshot.value.map(item => item.title).join(', ')
  : $t('rich-content.select_items')));

function onConfirm(hits: NormalizedSearchHit[]) {
  const ids = hits.map(hit => Number(hit.recordId));
  const snapshot = hits.map(hit => ({ id: Number(hit.recordId), title: hit.title }));

  if (pinnedCollection.value === 'pages') {
    options.value.pageIds = ids;
    content.value.pinnedPages = snapshot;
  }
  else {
    options.value.newsIds = ids;
    content.value.pinnedNews = snapshot;
  }
}

const tenantScopeToggle = computed(() => (options.value.tenantScope === 'all' ? 'all' : 'current'));

function onTenantScopeChange(value: unknown) {
  options.value.tenantScope = value === 'all' ? 'all' : 'current';
}
</script>
