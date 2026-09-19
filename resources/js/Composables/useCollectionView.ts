import { computed, ref, type Ref } from 'vue';
import { useLocalStorage, useMediaQuery } from '@vueuse/core';
import { usePage } from '@inertiajs/vue3';

export type CollectionViewMode = 'rows' | 'table' | 'preview';

const VIEW_MODES: readonly CollectionViewMode[] = ['rows', 'table', 'preview'];

interface UseCollectionViewOptions {
  /** Stable name of the collection; scopes the remembered choice (O3: per user per collection). */
  collection: string;
  defaultView: CollectionViewMode;
}

function isViewMode(value: unknown): value is CollectionViewMode {
  return typeof value === 'string' && (VIEW_MODES as readonly string[]).includes(value);
}

/**
 * The view a collection shows: the URL wins (U1), then the user's last choice, then the
 * entity's default. What a viewport can actually show narrows it: rows only below `md`,
 * the preview pane only from `xl` (O1, O2).
 */
export function useCollectionView(options: UseCollectionViewOptions) {
  const userId = usePage().props.auth?.user?.id ?? 'guest';

  // Only what the person chose is stored. Writing the default would freeze it for everyone who
  // ever visited, so an entity could never change its default view afterwards.
  const remembered = useLocalStorage<CollectionViewMode>(
    `admin-collection-view:${userId}:${options.collection}`,
    options.defaultView,
    { writeDefaults: false },
  );
  const filtersOpen = useLocalStorage(`admin-collection-filters:${userId}:${options.collection}`, false, { writeDefaults: false });

  const fromUrl = new URLSearchParams(window.location.search).get('view');
  const chosen: Ref<CollectionViewMode> = ref(isViewMode(fromUrl) ? fromUrl : remembered.value);

  const isAtLeastMd = useMediaQuery('(min-width: 768px)');
  const isAtLeastXl = useMediaQuery('(min-width: 1280px)');

  const availableViews = computed<CollectionViewMode[]>(() => {
    if (!isAtLeastMd.value) {
      return ['rows'];
    }

    return isAtLeastXl.value ? ['rows', 'table', 'preview'] : ['rows', 'table'];
  });

  const view = computed<CollectionViewMode>(() =>
    availableViews.value.includes(chosen.value) ? chosen.value : 'rows',
  );

  function setView(next: CollectionViewMode): void {
    chosen.value = next;
    remembered.value = next;

    const url = new URL(window.location.href);
    if (next === options.defaultView) {
      url.searchParams.delete('view');
    }
    else {
      url.searchParams.set('view', next);
    }
    window.history.replaceState(window.history.state, '', url.toString());
  }

  return { view, availableViews, setView, filtersOpen };
}
