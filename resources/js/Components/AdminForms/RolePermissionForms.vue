<template>
  <div class="max-w-2xl">
    <section v-for="entity in entities" :key="entity.key">
      <EntityDescription :title="entity.title" :icon="entity.icon">
        <div class="col-span-2" v-html="entity.description"></div>
      </EntityDescription>
      <PermissionTable
        :model-type="entity.key"
        :permissions="filterPermissionsFor(entity.key)"
        :role="role"
      ></PermissionTable>
    </section>
    <NDivider />
  </div>
</template>

<script setup lang="tsx">
import { ImageArrowBack24Regular } from "@vicons/fluent";
import { NDivider } from "naive-ui";

import { h } from "vue";
import EntityDescription from "@/Components/EntityDescriptions/EntityDescription.vue";
import PermissionTable from "@/Features/Admin/PermissionTable.vue";

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

// create const abilities from App.Enums.PermissionAbilities
// const abilities = Object.values(PermissionAbilities);
// const models = Object.values(Models);

// const pluralize = (word: string) => {
//   if (word.endsWith("y")) {
//     return word.slice(0, -1) + "ies";
//   }

//   if (word === "navigation") {
//     return word;
//   }

//  if (word === "calendar") {
//     return word;
//   }

//   return word + "s";
// };

// const getPermissions = () => {
//   const permissions = [];
//   for (const model of models) {
//     for (const ability of abilities) {
//       permissions.push({
//         name: `${ability}.${pluralize(model)}`,
//         granted: false,
//       });
//     }
//   }
//   return permissions;
// };

const entities = [
  {
    title: "Baneriai",
    icon: ImageArrowBack24Regular,
    key: "banners",
    description: `
      <p>Baneriai yra paveikslėliai su nuorodomis, besikeičiantys karuselės principu. Jie yra naudojami pagrindiniame puslapyje, skirti reklamuoti bendroves, VU SA PKP bei partnerius.</p><p>Šiuo metu banerių funkcija yra <strong class='text-vusa-red'>išjungta.</strong></p>`,
  },
];
</script>

<style scoped>
th {
  background-color: #f7fafc;
  position: sticky;
  top: 0;
}
</style>
