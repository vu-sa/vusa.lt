<template>
  <div class="max-w-2xl">
    <!-- <NTransfer
          ref="transfer"
          v-model:value="form.duties"
          :options="dutyOptions"
          source-filterable
          source-filter-placeholder="Ieškoti pareigų..."
          size="small"
        ></NTransfer> -->
    <section v-for="entity in entities" :key="entity.key">
      <Alert class="mb-4">
        <AlertTitle class="mb-2 flex items-center gap-1 text-base">
          <component :is="entity.icon" width="16" /> <span>{{ entity.title }}</span>
        </AlertTitle>
        <AlertDescription class="[&_p]:mb-2 [&_p]:leading-tight">
          <MdSuspenseWrapper :directory="entity.key" :locale="$page.props.app.locale" file="description" />
        </AlertDescription>
      </Alert>
      <PermissionTable :model-type="entity.key" :icon="entity.icon" :permissions="filterPermissionsFor(entity.key)"
        :role="role" />
      <Separator />
    </section>
  </div>
</template>

<script setup lang="tsx">
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert'

import MdSuspenseWrapper from "@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue";
import PermissionTable from "@/Features/Admin/PermissionTable/PermissionTable.vue";
import entities from "@/entities";
import { Separator } from '../ui/separator';

const props = defineProps<{
  role: App.Entities.Role;
}>();

const filterPermissionsFor = (modelType: string) => {
  if (!props.role.permissions) {
    return [];
  }

  const permissions = props.role.permissions.map((permission) => {
    return permission.name;
  });

  // filter permissions by model type
  const filteredPermissions = permissions.filter((permission) => {
    return permission.includes(modelType);
  });

  return filteredPermissions;
};

// create const abilities from PermissionAbilities
// const abilities = Object.values(PermissionAbilities);
// const models = Object.values(Models);

// const getPermissions = () => {
//   const permissions = [];
//   for (const model of models) {
//     for (const ability of abilities) {
//       permissions.push({
//         name: `${ability}.${pluralizeModels(model)}`,
//         granted: false,
//       });
//     }
//   }
//   return permissions;
// };
</script>

<style scoped>
th {
  background-color: #f7fafc;
  position: sticky;
  top: 0;
}
</style>
