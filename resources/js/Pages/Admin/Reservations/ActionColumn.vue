<template>
  <DataTableActions
    :model="model"
    :model-name="'reservations'"
    :view-route="route('reservations.show', model.id)"
    :edit-route="canEdit ? route('reservations.edit', model.id) : undefined"
    :delete-route="canDelete ? route('reservations.destroy', model.id) : undefined"
    :can-view="true"
    :can-edit="canEdit"
    :can-delete="canDelete"
  >
    <!-- Custom actions specific to reservations -->
    <template #custom-actions="{ model, handleAction }">
      <!-- Approve action - only show for pending reservations -->
      <Button 
        v-if="model.status === 'pending' && canApprove" 
        variant="ghost" 
        size="sm" 
        class="w-full justify-start"
        @click="handleCustomAction('approve')"
      >
        <CheckCircleIcon class="mr-2 h-4 w-4 text-green-500" />
        {{ $t('Approve') }}
      </Button>
      
      <!-- Reject action - only show for pending reservations -->
      <Button 
        v-if="model.status === 'pending' && canReject" 
        variant="ghost" 
        size="sm" 
        class="w-full justify-start"
        @click="handleCustomAction('reject')"
      >
        <XCircleIcon class="mr-2 h-4 w-4 text-red-500" />
        {{ $t('Reject') }}
      </Button>
      
      <!-- Complete action - only show for approved reservations -->
      <Button 
        v-if="model.status === 'approved' && canComplete" 
        variant="ghost" 
        size="sm" 
        class="w-full justify-start"
        @click="handleCustomAction('complete')"
      >
        <CheckSquare class="mr-2 h-4 w-4 text-blue-500" />
        {{ $t('Mark Complete') }}
      </Button>
    </template>
  </DataTableActions>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';
import { route } from '@/Utils/Routes';
import { Button } from '@/Components/ui/button';
import { 
  CheckCircleIcon,
  XCircleIcon,
  CheckSquare 
} from 'lucide-vue-next';
import DataTableActions from '@/Components/ui/data-table/DataTableActions.vue';

const props = defineProps<{
  model: App.Entities.Reservation;
  
  // Permissions
  canEdit?: boolean;
  canDelete?: boolean;
  canApprove?: boolean;
  canReject?: boolean;
  canComplete?: boolean;
}>();

const emit = defineEmits<{
  (e: 'approve', model: App.Entities.Reservation): void;
  (e: 'reject', model: App.Entities.Reservation): void;
  (e: 'complete', model: App.Entities.Reservation): void;
}>();

// Handler for custom actions
const handleCustomAction = (action: string) => {
  switch (action) {
    case 'approve':
      router.post(route('reservations.approve', props.model.id), {
        preserveScroll: true,
      });
      emit('approve', props.model);
      break;
      
    case 'reject':
      router.post(route('reservations.reject', props.model.id), {
        preserveScroll: true,
      });
      emit('reject', props.model);
      break;
      
    case 'complete':
      router.post(route('reservations.complete', props.model.id), {
        preserveScroll: true,
      });
      emit('complete', props.model);
      break;
  }
};
</script>