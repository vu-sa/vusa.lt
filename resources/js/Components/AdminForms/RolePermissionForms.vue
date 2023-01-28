<template>
  <div class="max-w-2xl">
    <!-- <NCollapse :default-expanded-names="['vusa.lt']">
      <NCollapseItem
        v-for="item in permissableItems"
        :key="item.groupName"
        :name="item.groupName"
        :title="item.groupName"
      > -->
    <section v-for="entity in entities" :key="entity.key">
      <EntityDescription :title="entity.title" :icon="entity.icon">
        <component :is="entity.description"></component>
      </EntityDescription>
      <PermissionTable
        :model-type="entity.key"
        :icon="entity.icon"
        :permissions="filterPermissionsFor(entity.key)"
        :role="role"
      ></PermissionTable>
      <NDivider />
    </section>
    <!-- </NCollapseItem>
    </NCollapse> -->
  </div>
</template>

<script setup lang="tsx">
import { NDivider } from "naive-ui";

import EntityDescription from "@/Types/EntityDescriptions/EntityDescription.vue";
import PermissionTable from "@/Features/Admin/PermissionTable/PermissionTable.vue";
import entities from "@/Types/EntityDescriptions/entities";

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
