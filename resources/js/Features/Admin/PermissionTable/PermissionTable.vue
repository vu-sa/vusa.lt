<template>
  <Spinner v-if="formDisabled">
    <p>Netikėta klaida. Praneškite administratoriui.</p>
  </Spinner>
  <table v-else class="w-full table-auto">
    <thead>
      <tr>
        <th />
        <th>{{ padalinysHeaderText }}</th>
        <th>{{ allHeaderText }}</th>
      </tr>
    </thead>
    <tbody class="border-t-8 border-transparent">
      <PermissionTableRow v-for="ability in abilities" :key="ability" :disabled="formDisabled"
        :default-value="permissionData[ability]" :permissions :icon :ability :available-permissions
        @update="(value) => handlePermissionUpdate(ability, value)" />
    </tbody>
  </table>
  <div class="mt-4 flex justify-end">
    <Button :disabled="formDisabled || !isDirty" :class="{ 'opacity-50 cursor-not-allowed': formDisabled || !isDirty }"
      variant="default" :data-loading="loading" @click="updatePermissionsForRole">
      Atnaujinti
    </Button>
  </div>
</template>

<script setup lang="tsx">
import { type Component, ref, computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

import { CRUDEnum } from '@/Types/enums';
import PermissionTableRow from '@/Features/Admin/PermissionTable/PermissionTableRow.vue';
import { Spinner } from '@/Components/ui/spinner';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  permissions: string[];
  role: App.Entities.Role;
  modelType: string;
  icon: Component;
  availablePermissions: string[];
}>();

const abilities = Object.values(CRUDEnum);
const formDisabled = ref(false);
const loading = ref(false);

// Determine available scopes for this model type
const availableScopes = computed(() => {
  const scopes = new Set<string>();

  // Extract scopes from available permissions
  props.availablePermissions.forEach((permission) => {
    const parts = permission.split('.');
    if (parts.length === 3 && parts[2]) {
      scopes.add(parts[2]);
    }
  });

  return {
    hasOwn: scopes.has('own'),
    hasPadalinys: scopes.has('padalinys'),
    hasAll: scopes.has('*'),
  };
});

// Determine the appropriate header text for the second column
const padalinysHeaderText = computed(() => {
  if (availableScopes.value.hasOwn && availableScopes.value.hasPadalinys) {
    return 'Savo / Padalinyje';
  }
  else if (availableScopes.value.hasPadalinys) {
    return 'Padalinyje';
  }
  else {
    return 'Netaikoma';
  }
});

// Determine the appropriate header text for the third column
const allHeaderText = computed(() => {
  if (availableScopes.value.hasAll) {
    return availableScopes.value.hasOwn || availableScopes.value.hasPadalinys ? 'Visur' : props.modelType;
  }
  else {
    return 'Netaikoma';
  }
});

const getAbilityScopeFromModelPermissions = (
  permissions: string[] | [],
  abilityInForm: string,
) => {
  const filteredPermissions = permissions.filter((permission) => {
    return permission.includes(abilityInForm);
  });

  if (filteredPermissions.length !== 1) {
    return undefined;
  }

  // delimit permission string to get ability and scope
  const permissionDelimited = filteredPermissions[0]?.split('.');

  if (!permissionDelimited || permissionDelimited.length !== 3) {
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
  create: getPermissionScope(props.permissions, 'create'),
  read: getPermissionScope(props.permissions, 'read'),
  update: getPermissionScope(props.permissions, 'update'),
  delete: getPermissionScope(props.permissions, 'delete'),
};

// Use reactive object instead of useForm for simpler state management
const permissionData = reactive({ ...formTemplate });
const originalData = { ...formTemplate };

// Check if form has changes
const isDirty = computed(() => {
  return Object.keys(permissionData).some(key =>
    permissionData[key as keyof typeof permissionData] !== originalData[key as keyof typeof originalData],
  );
});

const handlePermissionUpdate = (ability: string, value: string | undefined) => {
  (permissionData as any)[ability] = value;
};

const updatePermissionsForRole = () => {
  if (formDisabled.value) return;
  loading.value = true;

  // Only send abilities that have valid scopes
  const filteredData = Object.fromEntries(
    Object.entries(permissionData).filter(([, value]) => value !== undefined),
  );

  router.patch(
    route('roles.syncPermissionGroup', {
      role: props.role.id,
      model: props.modelType,
    }),
    filteredData,
    {
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false;
        // Update original data to reflect new state
        Object.assign(originalData, permissionData);
      },
      onError: () => {
        loading.value = false;
      },
    },
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
