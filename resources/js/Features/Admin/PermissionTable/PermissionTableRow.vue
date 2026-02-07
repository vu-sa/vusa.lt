<template>
  <tr>
    <td class="permission-description">
      <template v-if="ability === 'create'">
        <IFluentAddCircle24Filled />
        Sukurti
      </template>
      <template v-if="ability === 'read'">
        <IFluentEye24Filled />
        Matyti
      </template>
      <template v-if="ability === 'update'">
        <IFluentEdit24Filled />
        Redaguoti
      </template>
      <template v-if="ability === 'delete'">
        <IFluentDelete24Filled class="text-vusa-red" />
        Ištrinti
      </template>
    </td>
    <td>
      <div v-if="availableScopes.hasOwn || availableScopes.hasPadalinys" class="flex w-64 items-center gap-2">
        <!-- Show checkbox only if 'own' scope is available -->
        <Checkbox v-if="availableScopes.hasOwn" :checked="checkboxPadalinys" :disabled="switchAll || disabled"
          @update:checked="val => { checkboxPadalinys = val; handleUpdate(); }" />

        <!-- For models with both own and padalinys scopes -->
        <div v-if="availableScopes.hasPadalinys && availableScopes.hasOwn" class="flex items-center gap-2">
          <Switch :checked="switchPadalinys" :disabled="switchAll || !checkboxPadalinys || disabled"
            @update:checked="val => { switchPadalinys = val; handleUpdate(); }" />
          <span class="text-xs">{{ switchPadalinys ? 'Visus' : 'Savo' }}</span>
        </div>

        <!-- For models with only padalinys scope (no own scope) -->
        <div v-if="!availableScopes.hasOwn && availableScopes.hasPadalinys" class="flex items-center gap-2">
          <Switch :checked="switchPadalinys" :disabled="switchAll || disabled"
            @update:checked="val => { switchPadalinys = val; handleUpdate(); }" />
          <span class="text-xs">{{ switchPadalinys ? 'Padalinyje' : '' }}</span>
        </div>
      </div>
      <div v-else class="flex w-64 items-center gap-2 text-gray-500">
        <span class="text-sm">Netaikoma</span>
      </div>
    </td>
    <td>
      <div v-if="showAllControl">
        <Switch :checked="switchAll" :disabled="disabled" @update:checked="val => { switchAll = val; handleUpdate(); }" />
      </div>
      <div v-else class="text-gray-500">
        <span class="text-sm">Netaikoma</span>
      </div>
    </td>
  </tr>
</template>

<script setup lang="ts">
import { type Component, ref, computed, watchEffect, watch } from "vue";
import type { CRUDEnum } from "@/Types/enums";

import { Checkbox } from '@/Components/ui/checkbox';
import { Switch } from '@/Components/ui/switch';

const emit = defineEmits<{
  (e: "update", scope: string | undefined): void;
}>();

const props = defineProps<{
  ability: CRUDEnum;
  disabled: boolean;
  defaultValue: string | undefined;
  icon: Component;
  permissions: string[];
  availablePermissions: string[];
}>();

// Determine available scopes based on available permissions data
const availableScopes = computed(() => {
  const scopes = new Set<string>();

  // Extract scopes from available permissions for this ability
  props.availablePermissions.forEach(permission => {
    const parts = permission.split('.');
    if (parts.length === 3 && parts[1] === props.ability && parts[2]) {
      scopes.add(parts[2]);
    }
  });



  return {
    hasOwn: scopes.has('own'),
    hasPadalinys: scopes.has('padalinys'),
    hasAll: scopes.has('*')
  };
});

// Show padalinys controls only if model supports own or padalinys scopes
const showPadalinysControls = computed(() =>
  availableScopes.value.hasOwn || availableScopes.value.hasPadalinys
);

// Show "all" control only if model supports all scope
const showAllControl = computed(() => availableScopes.value.hasAll);

// Initialize refs
const checkboxPadalinys = ref(false);
const switchPadalinys = ref(false);
const switchAll = ref(false);

// Initialize values based on default and available scopes
const initializeValues = () => {
  checkboxPadalinys.value = availableScopes.value.hasOwn && ["own", "padalinys"].includes(props.defaultValue ?? "");

  // For models with both own and padalinys scopes
  if (availableScopes.value.hasOwn && availableScopes.value.hasPadalinys) {
    switchPadalinys.value = props.defaultValue === "padalinys";
  }

  // For models with only padalinys scope
  if (!availableScopes.value.hasOwn && availableScopes.value.hasPadalinys) {
    switchPadalinys.value = props.defaultValue === "padalinys";
  }

  switchAll.value = props.defaultValue === "*" && availableScopes.value.hasAll;
};

const handleUpdate = () => {
  // Only handle checkbox/switch logic if both 'own' and 'padalinys' scopes are available
  if (availableScopes.value.hasOwn && availableScopes.value.hasPadalinys) {
    if (!checkboxPadalinys.value) {
      switchPadalinys.value = false;
    }

    if (switchPadalinys.value) {
      checkboxPadalinys.value = true;
    }
  }

  // Handle "all" switch logic
  if (switchAll.value && availableScopes.value.hasAll) {
    if (availableScopes.value.hasOwn) {
      checkboxPadalinys.value = true;
    }
    if (availableScopes.value.hasPadalinys) {
      switchPadalinys.value = true;
    }
  }

  emit("update", decidePermission());
};

const decidePermission = () => {
  // If "all" switch is on and model supports it
  if (switchAll.value && availableScopes.value.hasAll) {
    return "*";
  }

  // If model has both own and padalinys scopes (traditional behavior)
  if (availableScopes.value.hasOwn && availableScopes.value.hasPadalinys) {
    if (switchPadalinys.value) {
      return "padalinys";
    }
    if (checkboxPadalinys.value) {
      return "own";
    }
  }

  // If model only has padalinys scope (like institutions)
  if (!availableScopes.value.hasOwn && availableScopes.value.hasPadalinys && !switchAll.value) {
    return switchPadalinys.value ? "padalinys" : undefined;
  }

  // If model has own scope but not padalinys
  if (availableScopes.value.hasOwn && !availableScopes.value.hasPadalinys) {
    if (checkboxPadalinys.value) {
      return "own";
    }
  }

  return undefined;
};

// Watch for changes in defaultValue and reinitialize
watch(() => props.defaultValue, () => {
  initializeValues();
}, { immediate: false });

// Initialize the component
watchEffect(() => {
  initializeValues();
});

// Initial call after setup
initializeValues();
handleUpdate();
</script>

<style scoped>
tr>td.permission-description {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

td {
  padding: 0.5rem;
}
</style>
