<template>
  <AdminContentPage :title="role.name" :back-url="route('roles.index')">
    <Card class="max-w-4xl">
      <CardHeader>
        <CardTitle>Priskirti rolę pareigybėms</CardTitle>
      </CardHeader>
      <CardContent>
        <TransferList v-model="currentDuties" :options="flattenDutyOptions">
          <template #source="{ filter }">
            <Tree
              v-model="currentDuties"
              :items="dutyOptions"
              :get-key="(item) => String(item.value)"
              :get-label="(item) => item.label"
              :is-item-disabled="(item) => item.checkboxDisabled ?? false"
              :filter="filter"
              multiple
              class="p-1"
            >
              <template #item="{ item }">
                <span class="inline-flex items-center gap-2">
                  {{ item.label }}
                  <a
                    v-if="typeof item.value !== 'number'"
                    target="_blank"
                    :href="item.checkboxDisabled
                      ? route('institutions.edit', item.value)
                      : route('duties.edit', item.value)"
                  >
                    <Button variant="ghost" size="icon-xs" @click.stop>
                      <Eye16Regular />
                    </Button>
                  </a>
                </span>
              </template>
            </Tree>
          </template>
        </TransferList>
      </CardContent>
      <CardFooter>
        <Button @click="handleDutyUpdate">
          Atnaujinti
        </Button>
      </CardFooter>
    </Card>
    <Card>
      <CardHeader>
        <CardTitle>Pasirinkti priskiriamus tipus</CardTitle>
      </CardHeader>
      <CardContent>
        <TransferList
          v-model="role.attachable_types"
          :options="allTypes.map((type) => ({
            value: type.id,
            label: type.title,
          }))"
        />
      </CardContent>
      <CardFooter>
        <Button @click="handleAttachableTypesUpdate">
          Atnaujinti
        </Button>
      </CardFooter>
    </Card>
    <h2 class="mt-4">
      Rolės teisės
    </h2>
    <RolePermissionForms :role="role" :allAvailablePermissions="allAvailablePermissions" model-route="roles.update" />
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import Eye16Regular from "~icons/fluent/eye16-regular"

import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { TransferList } from "@/Components/ui/transfer-list";
import { Tree } from "@/Components/ui/tree";
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import RolePermissionForms from "@/Components/AdminForms/RolePermissionForms.vue";

const props = defineProps<{
  tenantsWithDuties: App.Entities.Tenant[];
  role: App.Entities.Role;
  allTypes: App.Entities.Type[];
  allAvailablePermissions: Record<string, string[]>;
}>();

interface DutyTreeOption {
  label: string
  value: string | number
  checkboxDisabled?: boolean
  children?: DutyTreeOption[]
}

const dutyOptions: DutyTreeOption[] = props.tenantsWithDuties.map(
  (tenant) => ({
    label: tenant.shortname,
    value: tenant.id,
    checkboxDisabled: true,
    children: tenant.institutions?.map((institution) => ({
      label: institution.name,
      value: institution.id,
      checkboxDisabled: true,
      children: institution.duties?.map((duty) => ({
        label: duty.name,
        value: duty.id,
      })),
    })),
  }),
);

const flattenDutyOptions = dutyOptions.flatMap(
  (tenant) =>
    tenant.children?.flatMap(
      (institution) => institution.children?.map((duty) => ({
        value: duty.value,
        label: duty.label,
      })) ?? [],
    ) ?? [],
);

const currentDuties = ref(props.role.duties?.map((duty) => duty.id));

const handleDutyUpdate = () => {
  router.put(
    route("roles.syncDuties", props.role.id),
    {
      duties: currentDuties.value,
    },
    {
      preserveState: true,
    },
  );
};

const handleAttachableTypesUpdate = () => {
  router.put(
    route("roles.syncAttachableTypes", props.role.id),
    {
      attachable_types: props.role.attachable_types,
    },
    {
      preserveState: true,
    },
  );
};
</script>
