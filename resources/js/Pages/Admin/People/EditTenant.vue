<template>
  <PageContent title="Naujas padalinys" :heading-icon="Icons.TENANT">
    <div class="mb-4">
      <SmartLink :href="route('tenants.editMainPage', tenant.id)" class="text-sm text-gray-500">
        <Button variant="secondary">
          Redaguoti padalinio pagr. puslapį
        </Button>
      </SmartLink>
    </div>
    <UpsertModelLayout>
      <TenantForm :tenant :assignable-institutions
        @submit:form="(form) => form.patch(route('tenants.update', tenant.id), { preserveScroll: true })" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import TenantForm from '@/Components/AdminForms/TenantForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';

defineProps<{
  assignableInstitutions: Array<App.Entities.Institution>;
  tenant: App.Entities.Tenant;
}>();
</script>
