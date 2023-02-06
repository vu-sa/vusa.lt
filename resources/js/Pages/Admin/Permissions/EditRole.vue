<template>
  <AdminContentPage :title="role.name" :back-url="route('roles.index')">
    <div class="subtle-gray-gradient max-w-4xl p-4">
      <NTransfer
        ref="transfer"
        v-model:value="currentDuties"
        :options="flattenDutyOptions"
        :render-source-list="renderSourceList"
        source-filterable
        :show-irrelevant-nodes="true"
      ></NTransfer>
      <NButton class="mt-4" @click="handleDutyUpdate">Atnaujinti</NButton>
    </div>
    <RolePermissionForms :role="role" model-route="roles.update" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";

import { Eye16Regular } from "@vicons/fluent";
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
import RolePermissionForms from "@/Components/AdminForms/RolePermissionForms.vue";

const props = defineProps<{
  padaliniaiWithDuties: App.Entities.Padalinys[];
  role: App.Entities.Role;
}>();

const dutyOptions: TreeOption[] = props.padaliniaiWithDuties.map(
  (padalinys) => ({
    label: padalinys.shortname,
    value: padalinys.id,
    checkboxDisabled: true,
    children: padalinys.institutions?.map((institution) => ({
      label: institution.name,
      value: institution.id,
      checkboxDisabled: true,
      children: institution.duties?.map((duty) => ({
        label: duty.name,
        value: duty.id,
      })),
    })),
  })
);

const renderLabel = ({ option }: { option: TreeOption }) => {
  // jsx element
  // if value is integer then it's a padalinys and doesn't have additional button
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

const flattenDutyOptions = dutyOptions.flatMap((padalinys) =>
  padalinys.children?.flatMap((institution) =>
    institution.children?.map((duty) => duty)
  )
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
    }
  );
};
</script>
