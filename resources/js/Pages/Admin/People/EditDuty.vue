<template>
  <div>
    <DutyForm
      :duty
      :duty-types
      :assignable-institutions
      :roles
      :assignable-tenants
      :assignable-duties
      :assignable-tenant-users
      :ex-officio-members
      :acting-assignable-tenant-ids
      :can-edit-duty
      @submit:form="handleSubmit"
      @delete="handleDelete"
    />
    <AccessChangeWarningDialog
      :open
      :report
      @update:open="open = $event"
      @confirm="confirm"
      @cancel="cancel"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router, type InertiaForm } from '@inertiajs/vue3';

import DutyForm from '@/Components/AdminForms/DutyForm.vue';
import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';

const props = defineProps<{
  duty: App.Entities.Duty;
  canEditDuty: boolean;
  actingAssignableTenantIds?: number[];
  assignableTenantUsers?: Record<number, string[]>;
  exOfficioMembers?: Array<{
    dutiable_id: string;
    user_id: string;
    name: string;
    profile_photo_path?: string | null;
    tenant_id: number | null;
    source_duty_name?: string | null;
  }>;
  roles: App.Entities.Role[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers?: App.Entities.User[];
  dutyTypes: App.Entities.Type[];
  assignableTenants: { id: number; shortname: string; type?: string }[];
  assignableDuties: Array<{ id: string; name: string; institution?: App.Entities.Institution | Record<string, unknown> }>;
}>();

const { report, open, guardedSubmit, confirm, cancel } = useAccessChangeGuard();

const dutyTitle = computed(() => {
  if (typeof props.duty?.name === 'string') return props.duty.name;
  return props.duty?.name?.lt || props.duty?.name?.en || '';
});

const handleSubmit = (form: InertiaForm<Record<string, unknown>>) => {
  if (props.canEditDuty) {
    guardedSubmit((acknowledge) => {
      form
        .transform((data: Record<string, unknown>) => ({ ...data, acknowledge_access_change: acknowledge }))
        .patch(route('duties.update', props.duty.id), { preserveScroll: true });
    });
  }
};

const handleDelete = () => {
  guardedSubmit((acknowledge) => {
    router.delete(route('duties.destroy', props.duty.id), {
      data: { acknowledge_access_change: acknowledge },
    });
  });
};
</script>
