<template>
  <Spinner :show="formDisabled">
    <template #description>
      <p>Netikėta klaida. Praneškite administratoriui.</p>
    </template>
    <table class="w-full table-auto">
      <thead>
        <tr>
          <th />
          <!-- TODO: Paaiškinti detaliau, kas tas "padalinyje" -->
          <th>Padalinyje</th>
          <th>{{ modelType }}</th>
        </tr>
      </thead>
      <tbody class="border-t-8 border-transparent">
        <PermissionTableRow v-for="ability in abilities" :key="ability" :disabled="formDisabled"
          :default-value="formTemplate[ability]" :permissions="permissions" :icon="icon" :ability="ability"
          @update="permissionForm[ability] = $event" />
      </tbody>
    </table>
    <div class="mt-4 flex justify-end">
      <Button :disabled="formDisabled || !permissionForm.isDirty" :class="{ 'opacity-50 cursor-not-allowed': formDisabled || !permissionForm.isDirty }"
        variant="default" :data-loading="loading" @click="updatePermissionsForRole">Atnaujinti</Button>
    </div>
  </Spinner>
</template>

<script setup lang="tsx">
import { type Component, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import { CRUDEnum } from "@/Types/enums";
import PermissionTableRow from "@/Features/Admin/PermissionTable/PermissionTableRow.vue";
import { Spinner } from "@/Components/ui/spinner";
import { Button } from "@/Components/ui/button";

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

tbody>tr {
  border-bottom: 1px solid #e5e7eb;
}

tbody>tr:last-child {
  border-bottom: 0;
}

th {
  text-align: left;
}

tr>td.permission-description {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Add loading spinner to button */
[data-loading="true"]::before {
  content: "";
  display: inline-block;
  width: 1em;
  height: 1em;
  margin-right: 0.5rem;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  animation: spin 0.6s linear infinite;
  vertical-align: text-bottom;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
