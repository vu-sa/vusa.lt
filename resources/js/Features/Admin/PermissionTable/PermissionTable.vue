<template>
  <NSpin :show="formDisabled">
    <template #description>
      <p>Netikėta klaida. Praneškite administratoriui.</p>
    </template>
    <div
      class="rounded-sm border border-zinc-200 p-4 shadow-sm dark:border-zinc-700"
    >
      <table class="w-full table-auto">
        <thead>
          <tr>
            <th></th>
            <!-- TODO: Paaiškinti detaliau, kas tas "padalinyje" -->
            <th>Padalinyje</th>
            <th>{{ modelType }}</th>
          </tr>
        </thead>
        <tbody class="border-t-8 border-transparent">
          <PermissionTableRow
            v-for="ability in abilities"
            :key="ability"
            :disabled="formDisabled"
            :default-value="formTemplate[ability]"
            :permissions="permissions"
            :icon="icon"
            :ability="ability"
            @update="permissionForm[ability] = $event"
          ></PermissionTableRow>
        </tbody>
      </table>
      <div class="mt-4 flex justify-end">
        <NButton
          :disabled="formDisabled || !permissionForm.isDirty"
          :loading="loading"
          type="primary"
          @click="updatePermissionsForRole"
          >Atnaujinti</NButton
        >
      </div>
    </div>
  </NSpin>
</template>

<script setup lang="tsx">
import { type Component, ref } from "vue";
import { NButton, NSpin } from "naive-ui";
import { useForm } from "@inertiajs/vue3";

import { CRUDEnum } from "@/Types/enums";
import PermissionTableRow from "@/Features/Admin/PermissionTable/PermissionTableRow.vue";

const props = defineProps<{
  permissions: string[];
  role: App.Entities.Role;
  modelType: string;
  icon: Component;
}>();

const abilities = Object.values(CRUDEnum);
const formDisabled = ref(false);
const loading = ref(false);

const getAbilityScopeFromModelPermissions = (
  permissions: string[] | [],
  abilityInForm: string
) => {
  const filteredPermissions = permissions.filter((permission) => {
    return permission.includes(abilityInForm);
  });

  if (filteredPermissions.length !== 1) {
    return undefined;
  }

  // delimit permission string to get ability and scope
  let permissionDelimited = filteredPermissions[0].split(".");

  if (permissionDelimited.length !== 3) {
    formDisabled.value = true;
    return undefined;
  }

  if (permissionDelimited[0] !== props.modelType) {
    formDisabled.value = true;
    return undefined;
  }

  return permissionDelimited[2];
};

// alias previous function
const getPermissionScope = getAbilityScopeFromModelPermissions;

const formTemplate = {
  create: getPermissionScope(props.permissions, "create"),
  read: getPermissionScope(props.permissions, "read"),
  update: getPermissionScope(props.permissions, "update"),
  delete: getPermissionScope(props.permissions, "delete"),
};

const permissionForm =
  useForm<Record<string, string | undefined>>(formTemplate);

const updatePermissionsForRole = () => {
  if (formDisabled.value) return;
  loading.value = true;

  permissionForm.patch(
    route("roles.syncPermissionGroup", {
      role: props.role.id,
      model: props.modelType,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false;
      },
    }
  );
};
</script>

<style scoped>
td {
  padding: 0.5rem;
}

tbody > tr {
  border-bottom: 1px solid #e5e7eb;
}

tbody > tr:last-child {
  border-bottom: 0;
}

th {
  text-align: left;
}

tr > td.permission-description {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
