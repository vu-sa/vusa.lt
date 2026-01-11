<template>
  <!-- Member Registration Special Section -->
  <div v-if="props.canAccessMemberForm" class="mb-6">
    <Card class="border-primary/20 bg-primary/5">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <UsersIcon class="h-5 w-5" />
          {{ $t('Member Registrations') }}
        </CardTitle>
        <CardDescription>
          {{ $t('View and manage membership registrations from your tenant(s)') }}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Button as-child class="gap-2">
          <Link :href="route('forms.show', props.memberFormId)">
            <EyeIcon class="h-4 w-4" />
            {{ $t('View Member Registrations') }}
          </Link>
        </Button>
      </CardContent>
    </Card>
  </div>

  <!-- Student Rep Registration Special Section -->
  <div v-if="props.canAccessStudentRepForm" class="mb-6">
    <Card class="border-green-500/20 bg-green-500/5">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <UserPlusIcon class="h-5 w-5" />
          {{ $t('Student Representative Registrations') }}
        </CardTitle>
        <CardDescription>
          {{ $t('View and manage student representative registrations') }}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Button as-child variant="outline" class="gap-2">
          <Link :href="route('forms.show', props.studentRepFormId)">
            <EyeIcon class="h-4 w-4" />
            {{ $t('View Student Rep Registrations') }}
          </Link>
        </Button>
      </CardContent>
    </Card>
  </div>

  <!-- Regular Forms Table -->
  <IndexPageLayout title="Formos" model-name="forms" :can-use-routes="canUseRoutes" :columns
    :paginated-models="props.forms" />
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";
import { usePage, Link } from "@inertiajs/vue3";
import { provide, ref } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { Users as UsersIcon, Eye as EyeIcon, UserPlus as UserPlusIcon } from 'lucide-vue-next';

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import { tenantColumn } from "@/Composables/dataTableColumns";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";

const props = defineProps<{
  forms: PaginatedModels<App.Entities.Form>;
  memberFormId?: string;
  canAccessMemberForm?: boolean;
  studentRepFormId?: string;
  canAccessStudentRepForm?: boolean;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

const filters = ref<Record<string, any>>({
  "tenant.id": [],
  "types.id": [],
});

provide("filters", filters);

// add columns
const columns: DataTableColumns<App.Entities.Form> = [
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Nuoroda",
    key: "path",
  },
  {
    ...tenantColumn(filters, usePage().props.tenants),
    render(row) {
      return $t(row.tenant?.shortname ?? "");
    },
  },
];
</script>
