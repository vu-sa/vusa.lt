<template>
  <div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Label class="text-sm text-muted-foreground">{{ $t('navigation.builder.lang_switch') }}</Label>
        <ToggleGroup type="single" size="sm" :model-value="lang" @update:model-value="val => val && $emit('update:lang', val as 'lt' | 'en')">
          <ToggleGroupItem value="lt" class="gap-1.5">
            <img src="https://hatscripts.github.io/circle-flags/flags/lt.svg" class="h-3.5 w-3.5" alt="">
            LT
          </ToggleGroupItem>
          <ToggleGroupItem value="en" class="gap-1.5">
            <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" class="h-3.5 w-3.5" alt="">
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

    <Alert v-if="driftMessage" class="mb-4 border-status-info/20 bg-status-info/10 text-foreground">
      <AlertDescription class="flex flex-wrap items-center justify-between gap-2 text-sm">
        {{ driftMessage }}
        <Button variant="outline" size="sm" class="bg-background hover:bg-muted" @click="$emit('update:lang', otherLang)">
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
          :pending="pendingRoots.has(root.id) || orderSaving || reconciling"
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
import { computed, ref, watch, toRaw, reactive, onBeforeUnmount } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, Pencil, Plus } from '@lucide/vue';

import { withOptimisticRollback } from '@/Composables/useOptimisticVisit';
import { useMotionAwareSortable } from '@/Composables/useMotionAwareSortable';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { Alert, AlertDescription } from '@/Components/ui/alert';

import type { AdminNavigationLink, AdminNavigationRoot, TranslationSummary } from './types';
import NavigationPreview from './NavigationPreview.vue';
import NavigationRootItem from './NavigationRootItem.vue';

const props = defineProps<{
  roots: AdminNavigationRoot[];
  lang: 'lt' | 'en';
  translationSummary?: TranslationSummary;
}>();

defineEmits<(event: 'update:lang', lang: 'lt' | 'en') => void>();

const mode = ref<'edit' | 'preview'>('edit');
const rootsEl = ref<HTMLElement | null>(null);
const saveState = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');

// The editable buffer while dragging: saves return with `preserveState`, so only a different
// visit (the language switch, below) resets it from `props.roots`.
const contents = ref<AdminNavigationRoot[]>(structuredClone(toRaw(props.roots)));

watch(() => props.lang, () => {
  contents.value = structuredClone(toRaw(props.roots));
});

// Preview remounts the roots list; without rebinding, root drag silently stops working.
useMotionAwareSortable(rootsEl, contents, {
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

// Toggles and deletes apply optimistically and touch `contents`; the order watcher consumes this
// flag so they do not also send an order save.
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

const pendingRoots = reactive(new Set<number>());
const orderSaving = ref(false);
let orderQueued = false;
let needsReconcile = false;
const reconciling = ref(false);
let disposed = false;

function beginMutation(id: number): boolean {
  if (pendingRoots.has(id) || orderSaving.value || reconciling.value) return false;
  pendingRoots.add(id);
  return true;
}

function mutationOptions(id: number, rollback: () => void) {
  return withOptimisticRollback(rollback, {
    preserveScroll: true,
    onFinish: () => {
      pendingRoots.delete(id);
      settleMutations();
    },
  }, () => { needsReconcile = true; });
}

function settleMutations() {
  if (disposed || pendingRoots.size || orderSaving.value || reconciling.value) return;
  if (needsReconcile) {
    needsReconcile = false;
    reconciling.value = true;
    router.reload({
      only: ['navigation'],
      onFinish: () => {
        reconciling.value = false;
        if (!disposed && orderQueued) persistOrder();
      },
      onSuccess: (page) => {
        if (disposed) return;
        const local = contents.value;
        const fresh = structuredClone(page.props.navigation as AdminNavigationRoot[]);
        const byId = new Map(fresh.map(root => [root.id, root]));
        suppressNextOrderSave = true;
        contents.value = orderQueued
          ? local.flatMap((root) => {
              const server = byId.get(root.id);
              if (!server) return [];
              byId.delete(root.id);
              const links = new Map(server.links.flat().map(link => [link.id, link]));
              server.links = root.links.map(column => column.flatMap((link) => {
                const found = links.get(link.id);
                links.delete(link.id);
                return found ? [found] : [];
              }));
              server.links[0]?.push(...links.values());
              return [server];
            }).concat([...byId.values()])
          : fresh;
      },
    });
  }
  else if (orderQueued) persistOrder();
}

const persistOrder = useDebounceFn(() => {
  orderQueued = true;
  if (disposed || pendingRoots.size || orderSaving.value || reconciling.value) return;
  orderQueued = false;
  orderSaving.value = true;
  saveState.value = 'saving';

  router.post(route('navigation.updateOrder'), {
    navigation: contents.value.map(root => ({
      id: root.id,
      links: root.links.map(column => column.map(link => ({ id: link.id }))),
    })),
  }, withOptimisticRollback(() => { saveState.value = 'error'; }, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      orderSaving.value = false;
      settleMutations();
    },
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
  }, () => { needsReconcile = true; }));
}, 800);

onBeforeUnmount(() => {
  disposed = true;
  persistOrder.cancel();
});

watch(contents, () => {
  if (suppressNextOrderSave) {
    suppressNextOrderSave = false;
    return;
  }
  orderQueued = true;
  persistOrder();
}, { deep: true });

const patchLink = (link: AdminNavigationLink, changes: Partial<AdminNavigationLink>, rollback: () => void) => {
  router.patch(route('navigation.update', { navigation: link.id }), {
    name: link.name,
    url: link.url,
    parent_id: link.parent_id,
    lang: link.lang,
    is_active: link.is_active,
    extra_attributes: link.extra_attributes ?? {},
    ...changes,
  }, mutationOptions(link.parent_id || link.id, rollback));
};

const handleToggleLinkActive = (link: AdminNavigationLink, value: boolean) => {
  const location = findLinkLocation(link.id);
  const target = location?.column[location.index];
  if (!target) return;
  if (!beginMutation(link.parent_id)) return;
  const previous = target.is_active;
  suppressNextOrderSave = true;
  target.is_active = value;
  patchLink(link, { is_active: value }, () => {
    const current = findLinkLocation(link.id);
    const reverted = current?.column[current.index];
    if (reverted) {
      suppressNextOrderSave = true;
      reverted.is_active = previous;
    }
  });
};

const handleToggleRootActive = (root: AdminNavigationRoot, value: boolean) => {
  const current = contents.value.find(item => item.id === root.id);
  if (!current) return;
  if (!beginMutation(root.id)) return;
  const previous = current.is_active;
  suppressNextOrderSave = true;
  current.is_active = value;
  patchLink(root, { is_active: value }, () => {
    const item = contents.value.find(item => item.id === root.id);
    if (item) {
      suppressNextOrderSave = true;
      item.is_active = previous;
    }
  });
};

const handleDeleteLink = (link: AdminNavigationLink) => {
  const location = findLinkLocation(link.id);
  if (!location || !beginMutation(link.parent_id)) return;
  suppressNextOrderSave = true;
  const [removed] = location.column.splice(location.index, 1);
  if (!removed) return;
  router.delete(route('navigation.destroy', { navigation: link.id }), mutationOptions(link.parent_id, () => {
    suppressNextOrderSave = true;
    location.column.splice(Math.min(location.index, location.column.length), 0, removed);
  }));
};

const handleDeleteRoot = (root: AdminNavigationRoot) => {
  const index = contents.value.findIndex(r => r.id === root.id);
  if (index === -1 || !beginMutation(root.id)) return;
  suppressNextOrderSave = true;
  const [removed] = contents.value.splice(index, 1);
  if (!removed) return;
  router.delete(route('navigation.destroy', { navigation: root.id }), mutationOptions(root.id, () => {
    suppressNextOrderSave = true;
    contents.value.splice(Math.min(index, contents.value.length), 0, removed);
  }));
};
</script>
