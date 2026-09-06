<template>
  <div class="flex flex-col gap-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.link_list_source') }}</FieldLabel>
        <ToggleGroup :model-value="options.source" type="single" class="justify-start flex-wrap"
          @update:model-value="(v) => v && patchOptions({ source: v as LinkList['options']['source'] })">
          <ToggleGroupItem value="news">
            {{ $t('rich-content.link_list_source_news') }}
          </ToggleGroupItem>
          <ToggleGroupItem value="pages">
            {{ $t('rich-content.link_list_source_pages') }}
          </ToggleGroupItem>
          <ToggleGroupItem value="manual">
            {{ $t('rich-content.link_list_source_manual') }}
          </ToggleGroupItem>
        </ToggleGroup>
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.style') }}</FieldLabel>
        <ToggleGroup :model-value="options.style" type="single" class="justify-start"
          @update:model-value="(v) => v && patchOptions({ style: v as LinkList['options']['style'] })">
          <ToggleGroupItem value="photo">
            {{ $t('rich-content.link_list_style_photo') }}
          </ToggleGroupItem>
          <ToggleGroupItem value="compact">
            {{ $t('rich-content.link_list_style_compact') }}
          </ToggleGroupItem>
        </ToggleGroup>
      </Field>
    </div>

    <!-- Manual links: toolbar mode (scrollable list) -->
    <div v-if="options.source === 'manual' && toolbar" class="flex flex-col gap-2">
      <div class="flex items-center justify-between border-b border-border pb-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ $t('rich-content.links') }} ({{ manualLinks.length }})
        </span>
        <Button variant="outline" size="sm" data-rc-toolbar-add-link @click="addManualLink">
          <IFluentAdd12Regular class="mr-1 size-3.5" />
          {{ $t('rich-content.add_link') }}
        </Button>
      </div>

      <div v-if="manualLinks.length > 0" class="flex flex-col gap-1.5 max-h-48 overflow-y-auto pr-0.5">
        <div
          v-for="(item, index) in manualLinks"
          :key="index"
          class="flex items-center justify-between gap-2 rounded-md border border-border bg-muted/40 p-1.5 text-xs"
        >
          <div class="flex items-center gap-2 min-w-0 flex-1">
            <button
              v-if="options.style === 'photo'"
              type="button"
              class="relative size-8 shrink-0 overflow-hidden rounded border border-border bg-muted flex items-center justify-center hover:opacity-80 transition-opacity"
              :title="$t('rich-content.link_image')"
              @click="activeLinkIndexForImage = index"
            >
              <img
                v-if="item.imageUrl"
                :src="item.imageUrl"
                alt=""
                class="size-full object-cover"
              >
              <IFluentImage24Regular v-else class="size-4 text-muted-foreground" />
            </button>
            <div v-else class="flex size-7 shrink-0 items-center justify-center rounded border border-border bg-muted text-muted-foreground">
              <IFluentLink24Regular class="size-3.5" />
            </div>

            <div class="min-w-0 flex-1">
              <p class="truncate font-medium text-foreground">
                {{ item.title || `${$t('rich-content.links')} ${index + 1}` }}
              </p>
              <p v-if="item.url" class="truncate text-xs text-muted-foreground">
                {{ item.url }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1 shrink-0">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :disabled="index === 0"
              :title="$t('rich-content.move_up')"
              @click="moveManualLink(index, index - 1)"
            >
              <IFluentArrowUp24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :disabled="index === manualLinks.length - 1"
              :title="$t('rich-content.move_down')"
              @click="moveManualLink(index, index + 1)"
            >
              <IFluentArrowDown24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :title="$t('rich-content.edit_link')"
              @click="editingLinkIndex = index"
            >
              <IFluentEdit24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7 text-muted-foreground hover:text-destructive"
              :title="$t('rich-content.remove_link')"
              @click="removeManualLink(index)"
            >
              <IFluentDelete24Regular class="size-3.5" />
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Manual links: form mode (DynamicListInput) -->
    <DynamicListInput
      v-else-if="options.source === 'manual'"
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
              <img :src="item.imageUrl" alt="" class="aspect-video h-16 rounded-lg object-cover">
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
            <ToggleGroupItem value="latest">
              {{ $t('rich-content.link_list_mode_latest') }}
            </ToggleGroupItem>
            <ToggleGroupItem value="specific">
              {{ $t('rich-content.link_list_mode_specific') }}
            </ToggleGroupItem>
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
          :initial-hits
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
          <ToggleGroupItem value="current">
            {{ $t('rich-content.tenant_scope_current') }}
          </ToggleGroupItem>
          <ToggleGroupItem value="all">
            {{ $t('rich-content.tenant_scope_all') }}
          </ToggleGroupItem>
        </ToggleGroup>
      </Field>
    </template>

    <!-- Dialog for editing link in toolbar mode -->
    <Dialog :open="editingLinkIndex !== null" @update:open="(val) => { if (!val) editingLinkIndex = null; }">
      <DialogContent v-if="editingLinkIndex !== null && manualLinks[editingLinkIndex]">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.edit_link') }}</DialogTitle>
        </DialogHeader>
        <div class="flex flex-col gap-4 py-2">
          <Field>
            <FieldLabel>{{ $t('rich-content.link_title') }}</FieldLabel>
            <Input
              :model-value="manualLinks[editingLinkIndex].title"
              type="text"
              :placeholder="$t('rich-content.enter_link_title')"
              @update:model-value="updateEditingLink({ title: $event })"
            />
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.link_url') }}</FieldLabel>
            <Input
              :model-value="manualLinks[editingLinkIndex].url"
              type="url"
              placeholder="https://…"
              @update:model-value="updateEditingLink({ url: $event })"
            />
          </Field>
          <Field v-if="options.style === 'photo'">
            <FieldLabel>{{ $t('rich-content.link_image') }}</FieldLabel>
            <div v-if="manualLinks[editingLinkIndex].imageUrl" class="flex items-center gap-3">
              <img :src="manualLinks[editingLinkIndex].imageUrl" alt="" class="aspect-video h-16 rounded-lg object-cover">
              <Button variant="destructive" size="sm" @click="updateEditingLink({ imageUrl: '' })">
                {{ $t('rich-content.delete_image') }}
              </Button>
            </div>
            <Button
              v-else
              type="button"
              variant="outline"
              size="sm"
              @click="activeLinkIndexForImage = editingLinkIndex"
            >
              {{ $t('rich-content.select_image') }}
            </Button>
          </Field>
        </div>
      </DialogContent>
    </Dialog>

    <ImageSelector
      :show-modal="activeLinkIndexForImage !== null"
      selection-type="image"
      @update:show-modal="(open) => { if (!open) activeLinkIndexForImage = null; }"
      @submit="onLinkImageSubmit"
    />
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

import RCCategoryAliasSelect from '../Editor/RCCategoryAliasSelect.vue';

import type { LinkList } from '@/Types/contentParts';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { NumberField } from '@/Components/ui/number-field';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import ImageSelector from '@/Components/TipTap/ImageSelector.vue';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentEdit24Regular from '~icons/fluent/edit24-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';
import IFluentLink24Regular from '~icons/fluent/link24-regular';

defineProps<{ toolbar?: boolean }>();

const content = defineModel<LinkList['json_content']>({ required: true });
const options = defineModel<LinkList['options']>('options', { required: true });

const pickerOpen = ref(false);
const editingLinkIndex = ref<number | null>(null);
const activeLinkIndexForImage = ref<number | null>(null);

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

function addManualLink(): void {
  const current = [...(content.value.links ?? [])];
  current.push(createManualLink());
  patchContent({ links: current });
  editingLinkIndex.value = current.length - 1;
}

function removeManualLink(index: number): void {
  const current = [...(content.value.links ?? [])];
  current.splice(index, 1);
  patchContent({ links: current });
  if (editingLinkIndex.value === index) {
    editingLinkIndex.value = null;
  }
  else if (editingLinkIndex.value !== null && editingLinkIndex.value > index) {
    editingLinkIndex.value -= 1;
  }
}

function moveManualLink(from: number, to: number): void {
  const current = [...(content.value.links ?? [])];
  if (to < 0 || to >= current.length) {
    return;
  }
  const [moved] = current.splice(from, 1);
  if (!moved) {
    return;
  }
  current.splice(to, 0, moved);
  patchContent({ links: current });
  if (editingLinkIndex.value === from) {
    editingLinkIndex.value = to;
  }
  else if (editingLinkIndex.value === to) {
    editingLinkIndex.value = from;
  }
}

function updateEditingLink(patch: Partial<LinkList['json_content']['links'][number]>): void {
  if (editingLinkIndex.value === null) {
    return;
  }
  const current = [...(content.value.links ?? [])];
  if (!current[editingLinkIndex.value]) {
    return;
  }
  current[editingLinkIndex.value] = { ...current[editingLinkIndex.value], ...patch };
  patchContent({ links: current });
}

function onLinkImageSubmit(img: { src: string }): void {
  if (activeLinkIndexForImage.value === null) {
    return;
  }
  const current = [...(content.value.links ?? [])];
  if (current[activeLinkIndexForImage.value]) {
    current[activeLinkIndexForImage.value] = {
      ...current[activeLinkIndexForImage.value],
      imageUrl: img.src,
    };
    patchContent({ links: current });
  }
  activeLinkIndexForImage.value = null;
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
