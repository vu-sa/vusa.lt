<template>
  <PageContent :title="`${duty.name.lt} (${duty.institution?.short_name ??
    duty.institution?.name ??
    'Neturi institucijos'
  })`" :back-url="route('duties.index')" :heading-icon="DutyIcon">
    <UpsertModelLayout>
      <DutyForm
        :duty
        :roles
        :duty-types
        :assignable-institutions
        :assignable-users
        :assignable-tenants
        :assignable-duties
        :assignable-tenant-users
        :acting-assignable-tenant-ids
        :can-edit-duty
        @submit:form="handleSubmit"
        @delete="() => router.delete(route('duties.destroy', duty.id))"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import DutyForm from '@/Components/AdminForms/DutyForm.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { DutyIcon } from '@/Components/icons';

const props = defineProps<{
  duty: App.Entities.Duty;
  canEditDuty: boolean;
  actingAssignableTenantIds: number[];
  assignableTenantUsers: Record<number, string[]>;
  roles: App.Entities.Role[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers: App.Entities.User[];
  dutyTypes: App.Entities.Type[];
  assignableTenants: { id: number; shortname: string; type?: string }[];
  assignableDuties: Array<{ id: string; name: string; institution?: { id: string; name: string; short_name?: string | null; tenant?: { id: number; shortname: string } | null } | null }>;
}>();

const handleSubmit = (form: any) => {
  if (props.canEditDuty) {
    form.patch(route('duties.update', props.duty.id), { preserveScroll: true });
    return;
  }

  // Cross-tenant admin: diff the per-tenant user picker and post to batchUpdateUsers.
  // The acting tenant id is the first (and usually only) entry in actingAssignableTenantIds.
  const actingTenantId = props.actingAssignableTenantIds[0] ?? null;

  const assignableTenants: Array<{ tenant_id: number | null; quota: number | null; user_ids: string[] }>
    = form.assignable_tenants ?? [];

  const tenantRow = assignableTenants.find(r => r.tenant_id === actingTenantId);
  const newUserIds: string[] = tenantRow?.user_ids ?? [];
  const originalUserIds: string[] = props.assignableTenantUsers[actingTenantId ?? 0] ?? [];

  const now = new Date();
  const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  const userChanges = [
    ...newUserIds.filter(id => !originalUserIds.includes(id)).map(id => ({
      user_id: id, action: 'add', start_date: today,
    })),
    ...originalUserIds.filter(id => !newUserIds.includes(id)).map(id => ({
      user_id: id, action: 'remove', end_date: today,
    })),
  ];

  form.transform(() => ({
    user_changes: userChanges,
    ...(actingTenantId !== null ? { tenant_id: actingTenantId } : {}),
  })).post(route('duties.batchUpdateUsers', props.duty.id), { preserveScroll: true });
};
</script>
