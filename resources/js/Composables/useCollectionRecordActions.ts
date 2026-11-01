import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, Copy, Pencil, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionSource } from '@/Composables/useCollectionSource';

interface RecordActionOptions {
  /** Route name prefix: `pages` → `pages.edit`, `pages.destroy`, `pages.restore`, `pages.forceDelete`. */
  routePrefix: string;
  source: Pick<CollectionSource<unknown>, 'refresh'>;
  canEdit?: () => boolean;
  /** Offers `<prefix>.duplicate`; off unless given. */
  canDuplicate?: () => boolean;
  canDelete?: () => boolean;
  canRestore?: () => boolean;
  restoreOpensRecord?: boolean;
  canForceDelete?: () => boolean;
  onMutated?: (id: string) => void;
  deletePermanently?: boolean;
  /** Set when the record has its own page: Atidaryti then leads and Redaguoti moves to ⋯. */
  openHref?: (id: string) => string;
  editHref?: (id: string) => string;
}

export interface PendingRecordAction {
  kind: 'delete' | 'forceDelete';
  id: string;
  name: string;
  /** Why permanent deletion is refused; the dialog explains instead of confirming. */
  blockedReason?: string | null;
}

/** Inertia preserves collection state, so mutations must also refresh the source. */
export function useCollectionRecordActions(options: RecordActionOptions) {
  const pending = ref<PendingRecordAction | null>(null);
  const allowed = (check?: () => boolean) => check?.() ?? true;

  function rowActions(item: { id: string | number }, name: string, isTrash: boolean): CollectionRowAction[] {
    const id = String(item.id);

    if (isTrash) {
      return [
        ...(allowed(options.canRestore) ? [{ key: `restore:${id}`, label: $t('Atkurti'), icon: RotateCcw, primary: !allowed(options.canForceDelete) }] : []),
        ...(allowed(options.canForceDelete) ? [{ key: `forceDelete:${id}:${name}`, label: $t('Ištrinti visam laikui'), icon: Trash2, destructive: true }] : []),
      ];
    }

    return [
      ...(options.openHref ? [{ key: `open:${id}`, label: $t('Atidaryti'), icon: ArrowUpRight, role: 'open' as const, primary: true, href: options.openHref(id) }] : []),
      ...(allowed(options.canEdit)
        ? [{ key: `edit:${id}`, label: $t('Redaguoti'), icon: Pencil, role: 'edit' as const, menuOnly: Boolean(options.openHref), href: options.editHref?.(id) ?? route(`${options.routePrefix}.edit`, id) }]
        : []),
      ...(options.canDuplicate?.() ? [{ key: `duplicate:${id}`, label: $t('Dubliuoti'), icon: Copy }] : []),
      ...(allowed(options.canDelete) ? [{ key: `delete:${id}:${name}`, label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
    ];
  }

  function select(key: string, blockedReason?: string | null): void {
    const [kind, id, ...rest] = key.split(':');
    const name = rest.join(':');

    if (kind === 'restore') {
      router.patch(route(`${options.routePrefix}.restore`, id), {}, {
        preserveScroll: !options.restoreOpensRecord,
        onSuccess: options.restoreOpensRecord ? undefined : () => options.source.refresh(),
      });
    }
    else if (kind === 'duplicate') {
      router.post(route(`${options.routePrefix}.duplicate`, id));
    }
    else if (kind === 'delete' || kind === 'forceDelete') {
      pending.value = { kind, id, name, blockedReason };
    }
  }

  function confirm(): void {
    const action = pending.value;
    pending.value = null;

    if (!action || action.blockedReason) {
      return;
    }

    const routeName = action.kind === 'delete' ? `${options.routePrefix}.destroy` : `${options.routePrefix}.forceDelete`;
    router.delete(route(routeName, action.id), {
      preserveScroll: true,
      onSuccess: () => {
        options.onMutated?.(action.id);
        options.source.refresh();
      },
    });
  }

  const dialog = computed(() => {
    const action = pending.value;

    if (!action) {
      return null;
    }
    if (action.kind === 'forceDelete' && action.blockedReason) {
      return { title: $t('Negalima ištrinti visam laikui'), description: action.blockedReason, confirmLabel: $t('Supratau'), destructive: false };
    }
    if (action.kind === 'forceDelete') {
      return {
        title: $t('Ištrinti „:name“ visam laikui?', { name: action.name }),
        description: $t('Šis veiksmas negrįžtamas.'),
        confirmLabel: $t('Ištrinti visam laikui'),
        destructive: true,
      };
    }

    return {
      title: $t('Ištrinti „:name“?', { name: action.name }),
      description: options.deletePermanently ? $t('Šis veiksmas negrįžtamas.') : $t('Įrašas bus perkeltas į ištrintus — iš ten jį galėsi atkurti.'),
      confirmLabel: $t('Ištrinti'),
      destructive: true,
    };
  });

  return { pending, rowActions, select, confirm, dialog };
}
