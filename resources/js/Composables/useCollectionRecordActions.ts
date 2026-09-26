import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Copy, Pencil, RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';

interface RecordActionOptions {
  /** Route name prefix: `pages` → `pages.edit`, `pages.destroy`, `pages.restore`, `pages.forceDelete`. */
  routePrefix: string;
  canEdit?: () => boolean;
  /** Offers `<prefix>.duplicate`; off unless given. */
  canDuplicate?: () => boolean;
  canDelete?: () => boolean;
  canRestore?: () => boolean;
  canForceDelete?: () => boolean;
  /** Set when the record opens somewhere other than `<prefix>.edit`. */
  editHref?: (id: string) => string;
}

export interface PendingRecordAction {
  kind: 'delete' | 'forceDelete';
  id: string;
  name: string;
  /** Why permanent deletion is refused; the dialog explains instead of confirming. */
  blockedReason?: string | null;
}

/**
 * The row actions every collection shares — edit, soft delete, and in the trash view restore
 * and permanent delete — with the confirmation state the destructive ones need. Each mutation
 * is an Inertia visit, so the page reloads its own source afterwards.
 */
export function useCollectionRecordActions(options: RecordActionOptions) {
  const pending = ref<PendingRecordAction | null>(null);
  const allowed = (check?: () => boolean) => check?.() ?? true;

  function rowActions(item: { id: string | number }, name: string, isTrash: boolean): CollectionRowAction[] {
    const id = String(item.id);

    if (isTrash) {
      return [
        ...(allowed(options.canRestore) ? [{ key: `restore:${id}`, label: $t('Atkurti'), icon: RotateCcw, labelled: true }] : []),
        ...(allowed(options.canForceDelete) ? [{ key: `forceDelete:${id}:${name}`, label: $t('Ištrinti visam laikui'), icon: Trash2, destructive: true }] : []),
      ];
    }

    return [
      ...(allowed(options.canEdit)
        ? [{ key: `edit:${id}`, label: $t('Redaguoti'), icon: Pencil, labelled: true, href: options.editHref?.(id) ?? route(`${options.routePrefix}.edit`, id) }]
        : []),
      ...(options.canDuplicate?.() ? [{ key: `duplicate:${id}`, label: $t('Dubliuoti'), icon: Copy }] : []),
      ...(allowed(options.canDelete) ? [{ key: `delete:${id}:${name}`, label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
    ];
  }

  function select(key: string, blockedReason?: string | null): void {
    const [kind, id, ...rest] = key.split(':');
    const name = rest.join(':');

    if (kind === 'restore') {
      router.patch(route(`${options.routePrefix}.restore`, id), {}, { preserveScroll: true });
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
    router.delete(route(routeName, action.id), { preserveScroll: true });
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
      description: $t('Įrašas bus perkeltas į ištrintus — iš ten jį galėsi atkurti.'),
      confirmLabel: $t('Ištrinti'),
      destructive: true,
    };
  });

  return { pending, rowActions, select, confirm, dialog };
}
