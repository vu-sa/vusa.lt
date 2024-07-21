<template>
  <AdminContentPage :title="role.name" :back-url="route('roles.index')">
    <NCard title="Priskirti rolę pareigybėms" class="max-w-4xl">
      <NTransfer ref="transfer" v-model:value="currentDuties" :options="flattenDutyOptions"
        :render-source-list="renderSourceList" source-filterable :show-irrelevant-nodes="true" />
      <template #action>
        <NButton type="primary" class="mt-4" @click="handleDutyUpdate">
          Atnaujinti
        </NButton>
      </template>
    </NCard>
    <NCard>
      <template #header>
        Pasirinkti priskiriamus tipus
      </template>
      <NFormItem :span="6">
        <NTransfer v-model:value="role.attachable_types" :options="allTypes.map((type) => ({
          value: type.id,
          label: type.title,
          type: type,
        }))
          " />
      </NFormItem>
      <template #action>
        <NButton type="primary" class="mt-4" @click="handleAttachableTypesUpdate">
          Atnaujinti
        </NButton>
      </template>
    </NCard>
    <h2 class="mt-4">
      Rolės teisės
    </h2>
    <RolePermissionForms :role="role" model-route="roles.update" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import {
  NButton,
  NIcon,
  NTransfer,
  NTree,
  type TransferRenderSourceList,
  type TreeOption,
} from "naive-ui";
import { h, ref } from "vue";
import { router } from "@inertiajs/vue3";

import Eye16Regular from "~icons/fluent/eye16-regular"

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import RolePermissionForms from "@/Components/AdminForms/RolePermissionForms.vue";

const props = defineProps<{
  tenantsWithDuties: App.Entities.Tenant[];
  role: App.Entities.Role;
  allTypes: App.Entities.Type[];
}>();

const dutyOptions: TreeOption[] = props.tenantsWithDuties.map(
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

const renderLabel = ({ option }: { option: TreeOption }) => {
  // jsx element
  // if value is integer then it's a tenant and doesn't have additional button
  if (typeof option.value === "number") {
    return <span>{option.label}</span>;
  }

  // jsx element with button
  // ! assumption that if checkbox is enabled then it's a duty
  return (
    <span class="inline-flex items-center gap-2">
      {option.label}
      <a
        target="_blank"
        href={
          option.checkboxDisabled
            ? route("institutions.edit", option.value)
            : route("duties.edit", option.value)
        }
      >
        <NButton size="tiny" text>
          {{
            icon: <NIcon component={Eye16Regular} />,
          }}
        </NButton>
      </a>
    </span>
  );
};

const flattenDutyOptions = dutyOptions.flatMap(
  (tenant) =>
    tenant.children?.flatMap(
      (institution) => institution.children?.map((duty) => duty),
    ),
);

const currentDuties = ref(props.role.duties?.map((duty) => duty.id));

// tsx render Ntree
const renderSourceList: TransferRenderSourceList = ({ onCheck, pattern }) => {
  return h(NTree, {
    style: "margin: 0 4px;",
    keyField: "value",
    checkable: true,
    selectable: false,
    blockLine: true,
    virtualScroll: true,
    renderLabel: renderLabel,
    data: dutyOptions,
    pattern,
    checkedKeys: currentDuties,
    onUpdateCheckedKeys: (checkedKeys: Array<string | number>) => {
      onCheck(checkedKeys);
    },
  });
};

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
