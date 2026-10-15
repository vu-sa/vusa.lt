<template>
  <div class="max-w-2xl">
    <section v-for="entity in entities" :key="entity.key">
      <Alert class="mb-4">
        <AlertTitle class="flex items-center gap-1 text-base">
          <component :is="entity.icon" width="16" /> <span>{{ $t(entity.title) }}</span>
        </AlertTitle>
      </Alert>
      <p v-if="baselineAccess?.[entity.key]" class="mb-3 text-sm text-muted-foreground" data-testid="baseline-access">
        <span class="font-medium text-foreground">{{ $t('access.baseline.title') }}:</span> {{ baselineAccess[entity.key] }}
      </p>
      <PermissionTable :model-type="entity.key" :icon="entity.icon" :permissions="filterPermissionsFor(entity.key)"
        :available-permissions="(allAvailablePermissions && allAvailablePermissions[entity.key]) || []" :role
        :retired-permissions="(retiredPermissions ?? []).filter(permission => permission.startsWith(`${entity.key}.`))"
        :baseline-note="baselineAccess?.[entity.key]" />
      <Separator />
    </section>
  </div>
</template>

<script setup lang="tsx">
import { Separator } from '../ui/separator';

import { Alert, AlertTitle } from '@/Components/ui/alert';
import PermissionTable from '@/Features/Admin/PermissionTable/PermissionTable.vue';
import entities from '@/entities';

const props = defineProps<{
  role: App.Entities.Role;
  allAvailablePermissions?: Record<string, string[]>;
  baselineAccess?: Record<string, string>;
  retiredPermissions?: string[];
}>();

const filterPermissionsFor = (modelType: string) => {
  if (!props.role.permissions) {
    return [];
  }

  const permissions = props.role.permissions.map((permission) => {
    return permission.name;
  });

  const filteredPermissions = permissions.filter((permission) => {
    return permission.includes(modelType);
  });

  return filteredPermissions;
};

</script>

<style scoped>
th {
  background-color: #f7fafc;
  position: sticky;
  top: 0;
}
</style>
