<template>
  <div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Label class="text-sm text-muted-foreground">{{ $t('navigation.builder.lang_switch') }}</Label>
        <ToggleGroup type="single" size="sm" :model-value="lang" @update:model-value="val => val && $emit('update:lang', val as 'lt' | 'en')">
          <ToggleGroupItem value="lt" class="gap-1.5">
            <img src="https://hatscripts.github.io/circle-flags/flags/lt.svg" class="h-3.5 w-3.5 rounded-full" alt="">
            LT
          </ToggleGroupItem>
          <ToggleGroupItem value="en" class="gap-1.5">
            <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" class="h-3.5 w-3.5 rounded-full" alt="">
            EN
          </ToggleGroupItem>
        </ToggleGroup>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-xs text-muted-foreground" data-testid="save-state" :class="[saveState === 'error' && 'text-destructive']">
          <template v-if="saveState === 'saving'">{{ $t('navigation.builder.saving') }}</template>
          <template v-else-if="saveState === 'saved'">{{ $t('navigation.builder.saved') }}</template>
          <template v-else-if="saveState === 'error'">{{ $t('navigation.builder.save_failed') }}</template>
        </span>

        <ToggleGroup type="single" size="sm" :model-value="mode" @update:model-value="val => val && (mode = val as 'edit' | 'preview')">
          <ToggleGroupItem value="edit" class="gap-1.5">
            <Pencil class="size-3.5" />
            {{ $t('navigation.builder.edit_mode') }}
          </ToggleGroupItem>
          <ToggleGroupItem value="preview" class="gap-1.5">
            <Eye class="size-3.5" />
            {{ $t('navigation.builder.preview_mode') }}
          </ToggleGroupItem>
        </ToggleGroup>
      </div>
    </div>

    <Alert v-if="driftMessage" class="mb-4 border-sky-200 bg-sky-50 text-sky-950 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-100">
      <AlertDescription class="flex flex-wrap items-center justify-between gap-2 text-sm text-sky-900 dark:text-sky-100">
        {{ driftMessage }}
        <Button variant="outline" size="sm" class="border-sky-300 bg-white text-sky-950 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-100 dark:hover:bg-sky-900/40" @click="$emit('update:lang', otherLang)">
          {{ $t('navigation.builder.drift_jump', { lang: otherLang.toUpperCase() }) }}
        </Button>
      </AlertDescription>
    </Alert>

    <NavigationPreview v-if="mode === 'preview'" :roots="contents" />

    <template v-else>
      <TransitionGroup ref="rootsEl" tag="div" class="flex flex-col gap-3">
        <NavigationRootItem
          v-for="root in contents"
          :key="root.id"
          :root
          @toggle-link-active="handleToggleLinkActive"
          @toggle-active="handleToggleRootActive"
          @delete-link="handleDeleteLink"
          @delete="handleDeleteRoot"
        />
      </TransitionGroup>

      <div class="mt-4">
        <Button :as="Link" variant="outline" class="gap-1.5" :href="route('navigation.create', { parent_id: 0, lang })">
          <Plus class="size-4" />
          {{ $t('navigation.builder.add_root') }}
        </Button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { useSortable } from '@vueuse/integrations/useSortable';
import { cloneDeep } from 'lodash-es';
import { Eye, Pencil, Plus } from 'lucide-vue-next';

import NavigationRootItem from './NavigationRootItem.vue';
import NavigationPreview from './NavigationPreview.vue';

import type { AdminNavigationLink, AdminNavigationRoot, TranslationSummary } from './types';

import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { Alert, AlertDescription } from '@/Components/ui/alert';

const props = defineProps<{
  roots: AdminNavigationRoot[];
  lang: 'lt' | 'en';
  translationSummary?: TranslationSummary;
}>();

defineEmits<{
  (event: 'update:lang', lang: 'lt' | 'en'): void;
}>();

const mode = ref<'edit' | 'preview'>('edit');
const rootsEl = ref<HTMLElement | null>(null);
const saveState = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');

// Local editable buffer — the source of truth while dragging. Deliberately not kept in
// sync with `props.roots` after mount: a debounced save round-trip with
// `preserveState: true` (see below) never overwrites it, so a fresh reference from a
// *different* Inertia visit (e.g. the language switch) is the only thing that should
// reset it, which the `lang` watcher below does explicitly.
const contents = ref<AdminNavigationRoot[]>(cloneDeep(props.roots));

watch(() => props.lang, () => {
  contents.value = cloneDeep(props.roots);
});

// `watchElement: true` rebinds Sortable to the new element whenever the roots list
// is remounted — e.g. toggling `mode` to 'preview' and back unmounts and recreates
// the `<TransitionGroup>` this is attached to, and without the watcher Sortable
// stays bound to the discarded element, silently killing root drag afterward.
useSortable(rootsEl, contents, {
  watchElement: true,
  handle: '.nav-root-handle',
  animation: 150,
});

const otherLang = computed(() => (props.lang === 'lt' ? 'en' : 'lt'));

const driftMessage = computed(() => {
  const counts = props.translationSummary?.counts;
  if (!counts) {
    return null;
  }

  const current = counts[props.lang];
  const other = counts[otherLang.value];
  const diff = other - current;

  if (diff <= 0) {
    return null;
  }

  return $t('navigation.builder.drift_banner', {
    lang: props.lang.toUpperCase(),
    diff: String(diff),
    otherLang: otherLang.value.toUpperCase(),
  });
});

// `router.patch`/`router.delete` default `preserveState: true` (see Inertia's own
// `visit()` wrapper), so a successful is_active toggle or delete does NOT remount
// this component or re-sync `contents` from the fresh `props.roots` — the request
// lands in the database, but the local buffer never reflects it. These mutations are
// simple enough (flip a boolean, remove an id) to apply optimistically instead of
// waiting on a round-trip.
//
// Each optimistic mutation also touches `contents`, which the deep watcher below
// would otherwise read as "the order changed" and fire an unrelated updateOrder
// request for. `suppressNextOrderSave` is consumed (reset to false) by the very next
// watcher invocation, so it survives Vue's async watcher flush without racing a
// nextTick callback.
let suppressNextOrderSave = false;

function findLinkLocation(id: number): { column: AdminNavigationLink[]; index: number } | null {
  for (const root of contents.value) {
    for (const column of root.links) {
      const index = column.findIndex(l => l.id === id);
      if (index !== -1) {
        return { column, index };
      }
    }
  }
  return null;
}

const persistOrder = useDebounceFn(() => {
  saveState.value = 'saving';

  router.post(route('navigation.updateOrder'), {
    navigation: contents.value.map(root => ({
      id: root.id,
      links: root.links.map(column => column.map(link => ({ id: link.id }))),
    })),
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      saveState.value = 'saved';
      setTimeout(() => {
        if (saveState.value === 'saved') {
          saveState.value = 'idle';
        }
      }, 2000);
    },
    onError: () => {
      saveState.value = 'error';
    },
  });
}, 800);

watch(contents, () => {
  if (suppressNextOrderSave) {
    suppressNextOrderSave = false;
    return;
  }
  persistOrder();
}, { deep: true });

const patchLink = (link: AdminNavigationLink, changes: Partial<AdminNavigationLink>) => {
  router.patch(route('navigation.update', { navigation: link.id }), {
    name: link.name,
    url: link.url,
    parent_id: link.parent_id,
    lang: link.lang,
    is_active: link.is_active,
    extra_attributes: link.extra_attributes ?? {},
    ...changes,
  }, {
    preserveScroll: true,
    onError: () => {
      // Give up and resync from the server rather than leaving an optimistic change
      // the backend actually rejected sitting in the UI.
      suppressNextOrderSave = true;
      contents.value = cloneDeep(props.roots);
    },
  });
};

const handleToggleLinkActive = (link: AdminNavigationLink, value: boolean) => {
  const location = findLinkLocation(link.id);
  if (location) {
    suppressNextOrderSave = true;
    location.column[location.index].is_active = value;
  }
  patchLink(link, { is_active: value });
};

const handleToggleRootActive = (root: AdminNavigationRoot, value: boolean) => {
  const index = contents.value.findIndex(r => r.id === root.id);
  if (index !== -1) {
    suppressNextOrderSave = true;
    contents.value[index].is_active = value;
  }
  patchLink(root, { is_active: value });
};

const handleDeleteLink = (link: AdminNavigationLink) => {
  const location = findLinkLocation(link.id);
  if (location) {
    suppressNextOrderSave = true;
    location.column.splice(location.index, 1);
  }
  router.delete(route('navigation.destroy', { navigation: link.id }), { preserveScroll: true });
};

const handleDeleteRoot = (root: AdminNavigationRoot) => {
  const index = contents.value.findIndex(r => r.id === root.id);
  if (index !== -1) {
    suppressNextOrderSave = true;
    contents.value.splice(index, 1);
  }
  router.delete(route('navigation.destroy', { navigation: root.id }), { preserveScroll: true });
};
</script>
