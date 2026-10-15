import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

import type { CollectionStatusOption } from '@/Components/Collection/CollectionStatusMenu.vue';
import { contentStatuses } from '@/Constants/statuses';
import type { CollectionSource } from '@/Composables/useCollectionSource';

interface PublishingOptions<T> {
  /** `pages` → `pages.bulkStatus` / `pages.bulkDestroy`. */
  routePrefix: 'pages' | 'news';
  source: CollectionSource<T>;
  isPublished: (item: T) => boolean;
  /** The row fields `published` stands for, so the row changes before the index catches up. */
  patchFor: (published: boolean) => Partial<T>;
  /** Status menu, selection and bulk actions are offered only when this holds. */
}

const statusOptions: CollectionStatusOption[] = [
  { value: 'published', status: contentStatuses.published },
  { value: 'draft', status: contentStatuses.draft },
];

/** Offer only the change that would do something: all published → only "to drafts". */
export function publishChoices<T>(selected: readonly T[], isPublished: (item: T) => boolean): { publish: boolean; draft: boolean } {
  return {
    publish: selected.some(item => !isPublished(item)),
    draft: selected.some(item => isPublished(item)),
  };
}

/**
 * Draft ⇄ published from a collection — one row through the status menu, many through the
 * selection bar — and bulk soft delete. Rows change optimistically and roll back on error.
 */
export function useCollectionPublishing<T extends { id: string | number }>(options: PublishingOptions<T>) {
  const idsOf = (items: readonly T[]) => items.map(item => String(item.id));
  const statusValue = (item: T) => (options.isPublished(item) ? 'published' : 'draft');

  function setPublished(ids: string[], published: boolean, onDone?: () => void): void {
    const undo = options.source.patchItems(ids, options.patchFor(published));

    router.patch(route(`${options.routePrefix}.bulkStatus`), { ids: ids.map(Number), published }, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => onDone?.(),
      onError: undo,
    });
  }

  const bulkChoices = (selected: readonly T[]) => publishChoices(selected, options.isPublished);

  const pendingDelete = ref<{ ids: string[]; clear: () => void } | null>(null);

  const deleteDialog = computed(() => pendingDelete.value && {
    title: $t('Ištrinti pažymėtus įrašus (:count)?', { count: String(pendingDelete.value.ids.length) }),
    description: $t('Įrašai bus perkelti į ištrintus — iš ten juos galėsi atkurti.'),
    confirmLabel: $t('Ištrinti'),
    destructive: true,
  });

  function confirmDelete(): void {
    const pending = pendingDelete.value;
    pendingDelete.value = null;

    if (!pending) {
      return;
    }

    const undo = options.source.hideItems(pending.ids);
    router.delete(route(`${options.routePrefix}.bulkDestroy`), {
      data: { ids: pending.ids.map(Number) },
      preserveScroll: true,
      preserveState: true,
      onSuccess: pending.clear,
      onError: undo,
    });
  }

  return {
    statusOptions,
    statusValue,
    idsOf,
    setPublished,
    bulkChoices,
    pendingDelete,
    deleteDialog,
    confirmDelete,
  };
}
