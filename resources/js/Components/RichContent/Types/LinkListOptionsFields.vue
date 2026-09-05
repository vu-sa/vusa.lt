<template>
  <div class="flex flex-col gap-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.link_list_source') }}</FieldLabel>
        <ToggleGroup :model-value="options.source" type="single" class="justify-start flex-wrap"
          @update:model-value="(v) => v && patchOptions({ source: v as LinkList['options']['source'] })">
          <ToggleGroupItem value="news">{{ $t('rich-content.link_list_source_news') }}</ToggleGroupItem>
          <ToggleGroupItem value="pages">{{ $t('rich-content.link_list_source_pages') }}</ToggleGroupItem>
          <ToggleGroupItem value="manual">{{ $t('rich-content.link_list_source_manual') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.style') }}</FieldLabel>
        <ToggleGroup :model-value="options.style" type="single" class="justify-start"
          @update:model-value="(v) => v && patchOptions({ style: v as LinkList['options']['style'] })">
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
          <!-- Only rendered by the 'photo' style — compact never shows an image, so this
               field would be dead input for that style. -->
          <Field v-if="options.style === 'photo'" class="sm:col-span-2">
            <FieldLabel>{{ $t('rich-content.link_image') }}</FieldLabel>
            <TiptapImageButton
              v-if="!item.imageUrl"
              @submit:object="(img) => update({ ...item, imageUrl: img.src })">
              {{ $t('rich-content.select_image') }}
            </TiptapImageButton>
            <div v-else class="flex items-center gap-3">
              <img :src="item.imageUrl" class="aspect-video h-16 rounded-lg object-cover">
              <Button variant="destructive" size="sm" @click="update({ ...item, imageUrl: '' })">
                {{ $t('rich-content.delete_image') }}
              </Button>
            </div>
          </Field>
        </div>
      </template>
    </DynamicListInput>

    <!-- News/pages sourcing -->
    <template v-else>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field>
          <FieldLabel>{{ $t('rich-content.link_list_mode') }}</FieldLabel>
          <ToggleGroup :model-value="options.mode" type="single" class="justify-start"
            @update:model-value="(v) => v && patchOptions({ mode: v as LinkList['options']['mode'] })">
            <ToggleGroupItem value="latest">{{ $t('rich-content.link_list_mode_latest') }}</ToggleGroupItem>
            <ToggleGroupItem value="specific">{{ $t('rich-content.link_list_mode_specific') }}</ToggleGroupItem>
          </ToggleGroup>
        </Field>
        <Field>
          <FieldLabel>{{ $t('rich-content.limit') }}</FieldLabel>
          <NumberField :model-value="options.limit ?? 3" :min="1" :max="12"
            @update:model-value="patchOptions({ limit: $event })" />
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
        <RCCategoryAliasSelect :model-value="options.categoryAlias" @update:model-value="(v) => patchOptions({ categoryAlias: v })" />
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
 * `link-list`'s fetch-configuration fields (source/mode/limit/pinned items/category/tenant
 * scope) — everything beyond the shared title/subtitle/eyebrow header (`RCSectionOptions`).
 * Shared between `LinkListEditor.vue` (the regular side form) and `LinkListBlockToolbar.vue`
 * (the full-screen editor's options popover), so the two surfaces can't drift apart — see
 * the "HTML text fields" rule in RICH_CONTENT_EDITOR.md for why one implementation matters.
 *
 * `source: 'manual'` stores author-typed links directly; `'news'`/`'pages'` are resolved
 * server-side (see `LinkListResolver`), so only the *selector* (pinned ids, category, tenant
 * scope, limit) is stored — never the resolved titles/hrefs, which would go stale.
 *
 * `pinnedNews`/`pinnedPages` in json_content are editor-only bookkeeping: without a title to
 * render, `CollectionSelectDialog` can't show a previously-pinned item as checked when the
 * block is reopened (see the type's doc comment).
 */
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { LinkList } from '@/Types/contentParts';
import RCCategoryAliasSelect from '../Editor/RCCategoryAliasSelect.vue';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { NumberField } from '@/Components/ui/number-field';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';

const content = defineModel<LinkList['json_content']>({ required: true });
const options = defineModel<LinkList['options']>('options', { required: true });

const pickerOpen = ref(false);

// Immutable updates throughout this file — never `options.value.field = x` /
// `content.value.field = x` directly. `LinkListBlockToolbar.vue`'s popover only
// observes changes through defineModel's setter (it re-emits `update:content`), and a
// direct nested mutation there silently does nothing since `options`/`content` are
// derived from an immutable `content` prop, not shared reactive state the way the
// regular side-form editor's are.
function patchOptions(patch: Partial<LinkList['options']>): void {
  options.value = { ...options.value, ...patch };
}

function patchContent(patch: Partial<LinkList['json_content']>): void {
  content.value = { ...content.value, ...patch };
}

const manualLinks = computed({
  get: () => content.value.links ?? [],
  set: value => patchContent({ links: value }),
});

function createManualLink(): LinkList['json_content']['links'][number] {
  return { title: '', url: '', imageUrl: '' };
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
    patchOptions({ pageIds: ids });
    patchContent({ pinnedPages: snapshot });
  }
  else {
    patchOptions({ newsIds: ids });
    patchContent({ pinnedNews: snapshot });
  }
}

const tenantScopeToggle = computed(() => (options.value.tenantScope === 'all' ? 'all' : 'current'));

function onTenantScopeChange(value: unknown) {
  patchOptions({ tenantScope: value === 'all' ? 'all' : 'current' });
}
</script>
