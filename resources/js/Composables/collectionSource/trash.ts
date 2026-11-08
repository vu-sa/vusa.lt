import { trans as $t } from 'laravel-vue-i18n';

import type { CollectionSource } from './types';
import { PAGE_SIZE } from './shared';
import { useDatabaseCollectionSource } from './database';

/** The trash of a Typesense-backed collection, read from `api.v1.admin.trash.index`. */
export function useTrashCollectionSource<T>(collection: 'institutions' | 'meetings' | 'news' | 'pages'): CollectionSource<T> {
  return useDatabaseCollectionSource<T>({
    endpoint: route('api.v1.admin.trash.index', { collection }),
    initial: { items: [], total: 0, perPage: PAGE_SIZE, currentPage: 0, lastPage: 1 },
    sortOptions: () => [
      { value: 'deleted_at:desc', label: $t('Neseniai ištrinti') },
      { value: 'deleted_at:asc', label: $t('Seniausiai ištrinti') },
    ],
    defaultSort: 'deleted_at:desc',
    preserveUrlKeys: ['showDeleted'],
    fetchOnMount: true,
  });
}

/** The page is showing soft-deleted records (`?showDeleted=true`). */
export function isTrashView(): boolean {
  return new URLSearchParams(window.location.search).get('showDeleted') === 'true';
}

/**
 * Trash is a filter over the same collection, but the search index never holds deleted rows, so
 * the trash view always reads the database. Factories keep the unused source from ever starting
 * (a Typesense source searches as soon as it exists). Switching is an Inertia visit, which
 * remounts the page, so the choice is made once.
 */
export function useTrashAwareSource<T>(
  live: () => CollectionSource<T>,
  trash: () => CollectionSource<T>,
): CollectionSource<T> {
  return isTrashView() ? trash() : live();
}
