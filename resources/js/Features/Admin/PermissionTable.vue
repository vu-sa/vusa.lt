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
            <th>Visur</th>
          </tr>
        </thead>
        <tbody class="border-t-8 border-transparent">
          <PermissionTableRow
            v-for="ability in abilities"
            :key="ability"
            :disabled="formDisabled"
            :default-value="formTemplate[ability]"
            :permissions="permissions"
            :icon="ImageArrowBack24Regular"
            :ability="ability"
            @update="permissionForm[ability] = $event"
          ></PermissionTableRow>
        </tbody>
      </table>
      <div class="mt-4">
        <NButton
          :disabled="formDisabled"
          secondary
          @click="updatePermissionsForRole"
          >Atnaujinti</NButton
        >
      </div>
    </div>
  </NSpin>
</template>

<script setup lang="tsx">
import { ImageArrowBack24Regular } from "@vicons/fluent";
import { NButton, NSpin } from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

import { PermissionAbilities } from "@/Types/enums";
import PermissionTableRow from "@/Features/Admin/PermissionTableRow.vue";

const props = defineProps<{
  permissions: string[];
  role: App.Entities.Role;
  modelType: string;
}>();

const abilities = Object.values(PermissionAbilities);
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

  console.log(permissionDelimited, abilityInForm);

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
