<template>
  <div class="flex items-center justify-start gap-0.5">
    <TooltipProvider v-if="inlineActions.length > 0">
      <Tooltip v-for="action in inlineActions" :key="action.key">
        <TooltipTrigger as-child>
          <Button
            variant="ghost"
            size="icon"
            class="size-8"
            :data-testid="`row-action-${action.key}`"
            @click="action.run()"
          >
            <component :is="action.icon" class="size-4" />
            <span class="sr-only">{{ action.label }}</span>
          </Button>
        </TooltipTrigger>
        <TooltipContent>{{ action.label }}</TooltipContent>
      </Tooltip>
    </TooltipProvider>

    <!--
      Only destructive actions live behind the overflow menu: a stray click on a
      row must never delete anything. Everything reversible stays inline above.
    -->
    <DropdownMenu v-if="hasOverflowContent">
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" size="icon" class="size-8" data-testid="row-actions-overflow">
          <MoreHorizontalIcon class="size-4" />
          <span class="sr-only">{{ $t('tables.open_menu') }}</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" class="w-64">
        <slot name="custom-actions" :model :handle-action="handleCustomAction" />
        <DropdownMenuSeparator v-if="$slots['custom-actions'] && hasDestructiveActions" />

        <DropdownMenuItem
          v-if="canDelete && !model.deleted_at"
          variant="destructive"
          data-testid="row-action-delete"
          @select="handleDeleteClick()"
        >
          <Trash2Icon />
          {{ $t('forms.delete') }}
        </DropdownMenuItem>

        <template v-if="canForceDelete && model.deleted_at">
          <!--
            When the server has told us why this record cannot be erased, show the
            reason in place of the action. A disabled menu item swallows pointer
            events, so a tooltip would never surface the explanation.
          -->
          <div
            v-if="forceDeleteBlockedReason"
            class="px-2 py-1.5 text-xs text-muted-foreground"
            data-testid="row-action-force-delete-blocked"
          >
            <span class="mb-1 flex items-center gap-2 font-medium text-foreground/70">
              <ShredderIcon class="size-4" />
              {{ $t('trash.permanently_delete') }}
            </span>
            {{ forceDeleteBlockedReason }}
          </div>
          <DropdownMenuItem
            v-else
            variant="destructive"
            data-testid="row-action-force-delete"
            @select="isForceDeleteDialogOpen = true"
          >
            <ShredderIcon />
            {{ $t('trash.permanently_delete') }}
          </DropdownMenuItem>
        </template>
      </DropdownMenuContent>
    </DropdownMenu>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="isConfirmDeleteDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ deleteConfirmTitle || $t('tables.delete_confirm_title') }}</DialogTitle>
          <DialogDescription>
            {{ deleteConfirmMessage || $t('tables.delete_confirm_description') }}
            <span v-if="deleteNote" class="mt-2 block">{{ deleteNote }}</span>
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose as-child>
            <Button variant="outline">
              {{ $t('forms.cancel') }}
            </Button>
          </DialogClose>
          <Button variant="destructive" @click="performDelete">
            {{ $t('forms.delete') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <ConfirmDangerousActionDialog
      v-model:open="isForceDeleteDialogOpen"
      :title="$t('trash.permanently_delete')"
      :description="forceDeleteDescription"
      :confirmation-text="forceDeleteConfirmationText"
      :confirm-label="$t('trash.permanently_delete')"
      @confirm="performForceDelete"
    />
  </div>
</template>

<script setup lang="ts" generic="TModel extends { id: string | number, deleted_at?: string | null, force_delete_blocked_reason?: string | null }">
import { ref, computed, useSlots, type Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { router, usePage } from '@inertiajs/vue3';
import {
  CopyIcon,
  EyeIcon,
  HistoryIcon,
  MoreHorizontalIcon,
  PencilIcon,
  ShredderIcon,
  Trash2Icon,
} from 'lucide-vue-next';

// UI Components
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogClose,
} from '@/Components/ui/dialog';
import ConfirmDangerousActionDialog from '@/Components/ui/data-table/ConfirmDangerousActionDialog.vue';

// Props
const props = defineProps<{
  model: TModel;
  modelName: string;

  // Routes
  viewRoute?: string;
  editRoute?: string;
  duplicateRoute?: string;
  deleteRoute?: string;
  restoreRoute?: string;
  forceDeleteRoute?: string;

  // Permissions
  canView?: boolean;
  canEdit?: boolean;
  canDuplicate?: boolean;
  canDelete?: boolean;
  canRestore?: boolean;
  canForceDelete?: boolean;

  // Confirmation settings
  confirmDelete?: boolean;
  deleteConfirmMessage?: string;
  deleteConfirmTitle?: string;
}>();

// Emit events
const emit = defineEmits<{
  (e: 'action', action: string, model: TModel): void;
  (e: 'view', model: TModel): void;
  (e: 'edit', model: TModel): void;
  (e: 'duplicate', model: TModel): void;
  (e: 'delete', model: TModel): void;
  (e: 'restore', model: TModel): void;
  (e: 'force-delete', model: TModel): void;
  (e: 'custom-action', action: string, model: TModel): void;
}>();

// Dialog state
const isConfirmDeleteDialogOpen = ref(false);
const isForceDeleteDialogOpen = ref(false);

const page = usePage();
const slots = useSlots();

const isTrashed = computed(() => !!props.model.deleted_at);

type InlineAction = {
  key: string;
  icon: Component;
  label: string;
  run: () => void;
};

/**
 * Reversible actions, rendered directly in the cell. A trashed row can only be
 * restored — viewing or editing it would resolve to a route that rejects
 * trashed models.
 */
const inlineActions = computed<InlineAction[]>(() => {
  if (isTrashed.value) {
    return props.canRestore
      ? [{ key: 'restore', icon: HistoryIcon, label: $t('trash.restore'), run: () => handleAction('restore') }]
      : [];
  }

  const actions: InlineAction[] = [];

  if (props.canView) {
    actions.push({ key: 'view', icon: EyeIcon, label: $t('tables.view'), run: () => handleAction('view') });
  }

  if (props.canEdit) {
    actions.push({ key: 'edit', icon: PencilIcon, label: $t('forms.edit'), run: () => handleAction('edit') });
  }

  if (props.canDuplicate) {
    actions.push({ key: 'duplicate', icon: CopyIcon, label: $t('tables.duplicate'), run: () => handleAction('duplicate') });
  }

  return actions;
});

const hasDestructiveActions = computed(() => {
  return isTrashed.value ? !!props.canForceDelete : !!props.canDelete;
});

const hasOverflowContent = computed(() => hasDestructiveActions.value || !!slots['custom-actions']);

/** Translated explanation of why this record cannot be permanently deleted, supplied by the server. */
const forceDeleteBlockedReason = computed(() => props.model.force_delete_blocked_reason ?? null);

/**
 * Optional per-model sentence spelling out what deletion actually costs — a duty is
 * not a banner. Resolved by convention from `modelName`; `trans()` echoes the key back
 * when there is no entry, which is the signal that this model needs no extra wording.
 */
const noteFor = (action: 'delete' | 'force_delete'): string | null => {
  const key = `trash.notes.${props.modelName}.${action}`;
  const translated = $t(key);

  return translated === key ? null : translated;
};

const deleteNote = computed(() => noteFor('delete'));

const forceDeleteDescription = computed(() => {
  const note = noteFor('force_delete');
  const base = $t('trash.permanently_delete_description');

  return note ? `${base} ${note}` : base;
});

// Action handler for standard actions
const handleAction = (action: string) => {
  // Emit generic action event
  emit('action', action, props.model);

  // Emit specific action event
  emit(action as any, props.model);

  // Handle based on action type
  switch (action) {
    case 'view':
      if (props.viewRoute) {
        router.visit(props.viewRoute);
      }
      break;

    case 'edit':
      if (props.editRoute) {
        router.visit(props.editRoute);
      }
      break;

    case 'duplicate':
      if (props.duplicateRoute) {
        router.post(props.duplicateRoute);
      }
      break;

    case 'restore':
      if (props.restoreRoute) {
        router.patch(props.restoreRoute, {}, {
          preserveScroll: true,
          preserveState: true,
        });
      }
      break;
  }
};

const getLocalizedValue = (value: unknown): string | null => {
  if (typeof value === 'string' || typeof value === 'number') {
    return String(value);
  }

  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    return null;
  }

  const translations = value as Record<string, unknown>;
  const locale = String((page.props as any)?.app?.locale ?? 'lt');
  const localizedValue = translations[locale] ?? translations.lt ?? Object.values(translations).find(item => typeof item === 'string' && item.trim() !== '');

  return typeof localizedValue === 'string' || typeof localizedValue === 'number'
    ? String(localizedValue)
    : null;
};

const forceDeleteConfirmationText = computed(() => {
  const modelRecord = props.model as Record<string, unknown>;

  return getLocalizedValue(modelRecord.name)
    ?? getLocalizedValue(modelRecord.title)
    ?? getLocalizedValue(modelRecord.short_name)
    ?? String(props.model.id);
});

// Handler for custom actions provided by parent
const handleCustomAction = (action: string) => {
  emit('custom-action', action, props.model);
};

// Function to perform the actual deletion
const performDelete = () => {
  if (props.deleteRoute) {
    router.delete(props.deleteRoute, {
      onFinish: () => isConfirmDeleteDialogOpen.value = false, // Close dialog on finish
      preserveState: true, // Preserve state to avoid unnecessary reloads
      preserveScroll: true, // Preserve scroll position
    });
  }
  emit('delete', props.model);
  // Ensure dialog is closed even if no route is provided but emit happens
  isConfirmDeleteDialogOpen.value = false;
};

const performForceDelete = () => {
  if (props.forceDeleteRoute) {
    router.delete(props.forceDeleteRoute, {
      preserveScroll: true,
    });
  }

  emit('force-delete', props.model);
};

// Handle delete button click: either show confirmation or delete directly
const handleDeleteClick = () => {
  if (props.confirmDelete !== false) {
    isConfirmDeleteDialogOpen.value = true;
  }
  else {
    // No confirmation needed
    performDelete();
  }
};
</script>
