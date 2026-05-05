<template>
  <div>
    <Popover v-if="hasAvailableActions">
      <PopoverTrigger as-child>
        <Button variant="ghost" size="icon" class="h-8 w-8 p-0">
          <MoreHorizontalIcon class="h-4 w-4" />
          <span class="sr-only">{{ $t('Open menu') }}</span>
        </Button>
      </PopoverTrigger>
      <PopoverContent align="end" class="w-56 p-0">
        <div class="space-y-1 p-1">
          <!-- Standard actions -->
          <Button
            v-if="canView"
            variant="ghost"
            size="sm"
            class="w-full justify-start"
            :disabled="!!model.deleted_at"
            :class="{ 'opacity-50 cursor-not-allowed': model.deleted_at }"
            @click="!model.deleted_at && handleAction('view')"
          >
            <EyeIcon class="mr-2 h-4 w-4" />
            {{ $t('View') }}
          </Button>

          <Button
            v-if="canEdit"
            variant="ghost"
            size="sm"
            class="w-full justify-start"
            :disabled="!!model.deleted_at"
            :class="{ 'opacity-50 cursor-not-allowed': model.deleted_at }"
            @click="!model.deleted_at && handleAction('edit')"
          >
            <PencilIcon class="mr-2 h-4 w-4" />
            {{ $t('Edit') }}
          </Button>

          <Button
            v-if="canDuplicate && !model.deleted_at"
            variant="ghost"
            size="sm"
            class="w-full justify-start"
            @click="handleAction('duplicate')"
          >
            <CopyIcon class="mr-2 h-4 w-4" />
            {{ $t('Duplicate') }}
          </Button>

          <!-- Custom actions slot -->
          <slot name="custom-actions" :model :handle-action="handleCustomAction" />

          <!-- Danger zone with separator -->
          <Separator v-if="(canDelete && !model.deleted_at) || (canRestore && model.deleted_at)" class="data-[orientation=horizontal]:my-0.5" />

          <Button
            v-if="canDelete && !model.deleted_at"
            variant="ghost"
            size="sm"
            class="w-full justify-start text-destructive hover:text-destructive"
            @click="handleDeleteClick()"
          >
            <Trash2Icon class="mr-2 h-4 w-4" />
            {{ $t('Delete') }}
          </Button>

          <Button
            v-if="canRestore && model.deleted_at"
            variant="ghost"
            size="sm"
            class="w-full justify-start"
            @click="handleAction('restore')"
          >
            <HistoryIcon class="mr-2 h-4 w-4" />
            {{ $t('Restore') }}
          </Button>
        </div>
      </PopoverContent>
    </Popover>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="isConfirmDeleteDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ deleteConfirmTitle || $t('Delete Item') }}</DialogTitle>
          <DialogDescription>
            {{ deleteConfirmMessage || $t('Are you sure you want to delete this item? This action cannot be undone.') }}
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose as-child>
            <Button variant="outline">
              {{ $t('Cancel') }}
            </Button>
          </DialogClose>
          <Button variant="destructive" @click="performDelete">
            {{ $t('Delete') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts" generic="TModel extends { id: string | number, deleted_at?: string | null }">
import { ref, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';
import {
  CopyIcon,
  EyeIcon,
  HistoryIcon,
  MoreHorizontalIcon,
  PencilIcon,
  Trash2Icon,
} from 'lucide-vue-next';

// UI Components
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Separator } from '@/Components/ui/separator';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogClose,
} from '@/Components/ui/dialog';

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

  // Permissions
  canView?: boolean;
  canEdit?: boolean;
  canDuplicate?: boolean;
  canDelete?: boolean;
  canRestore?: boolean;

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
  (e: 'custom-action', action: string, model: TModel): void;
}>();

// Dialog state
const isConfirmDeleteDialogOpen = ref(false);

// Computed property to check if there are any available actions
const hasAvailableActions = computed(() => {
  // If model is soft-deleted, only restore action is available
  if (props.model.deleted_at) {
    return !!(props.canRestore);
  }

  // If model is not soft-deleted, check for other actions
  return !!(props.canView || props.canEdit || props.canDuplicate || props.canDelete);
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
        router.patch(props.restoreRoute);
      }
      break;
  }
};

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
