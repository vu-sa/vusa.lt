<template>
  <div class="max-w-2xl">
    <NCollapse :default-expanded-names="['vusa.lt']">
      <NCollapseItem
        v-for="item in permissableItems"
        :key="item.groupName"
        :name="item.groupName"
        :title="item.groupName"
      >
        <section v-for="entity in item.entities" :key="entity.key">
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
      </NCollapseItem>
    </NCollapse>
  </div>
</template>

<script setup lang="tsx">
import { NCollapse, NCollapseItem, NDivider } from "naive-ui";

import * as Descriptions from "@/Types/EntityDescriptions/DescriptionComponents";
import { ModelEnum } from "@/Types/enums";
import { pluralizeModels } from "@/Utils/String";
import EntityDescription from "@/Types/EntityDescriptions/EntityDescription.vue";
import Icons from "@/Types/Icons/regular";
import PermissionTable from "@/Features/Admin/PermissionTable/PermissionTable.vue";

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

// console.log(ModelEnum.PAGE);

const page = ModelEnum.PAGE;

console.log(page);
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

const permissableItems = [
  {
    groupName: "vusa.lt",
    entities: [
      {
        title: "Puslapiai",
        icon: Icons.PAGE,
        // get label of enum
        key: pluralizeModels(ModelEnum.PAGE),
        description: Descriptions.PageDescription,
      },
      {
        title: "Naujienos",
        icon: Icons.NEWS,
        key: pluralizeModels(ModelEnum.NEWS),
        description: Descriptions.NewsDescription,
      },
      {
        title: "Baneriai",
        icon: Icons.BANNER,
        key: pluralizeModels(ModelEnum.BANNER),
        description: Descriptions.BannerDescription,
      },
      {
        title: "Kalendorius",
        icon: Icons.CALENDAR,
        key: pluralizeModels(ModelEnum.CALENDAR),
        description: Descriptions.CalendarDescription,
      },
      {
        title: "Greitieji mygtukai",
        icon: Icons.MAIN_PAGE,
        key: pluralizeModels(ModelEnum.MAIN_PAGE),
        description: Descriptions.MainPageDescription,
      },
      {
        title: "Navigacija",
        icon: Icons.NAVIGATION,
        key: pluralizeModels(ModelEnum.NAVIGATION),
        description: Descriptions.NavigationDescription,
      },
      {
        title: "Sąžiningai egzaminai",
        icon: Icons.SAZININGAI_EXAM,
        key: pluralizeModels(ModelEnum.SAZININGAI_EXAM),
        description: Descriptions.SaziningaiExamDescription,
      },
    ],
  },
  {
    groupName: "Atstovavimas",
    entities: [
      {
        title: "Naudotojai",
        icon: Icons.USER,
        key: pluralizeModels(ModelEnum.USER),
        description: Descriptions.UserDescription,
      },
      {
        title: "Institucijos",
        icon: Icons.INSTITUTION,
        key: pluralizeModels(ModelEnum.INSTITUTION),
        description: Descriptions.InstitutionDescription,
      },
      {
        title: "Svarstomi klausimai (temos)",
        icon: Icons.MATTER,
        key: pluralizeModels(ModelEnum.MATTER),
        description: Descriptions.MatterDescription,
      },
      {
        title: "Susitikimai",
        icon: Icons.MEETING,
        key: pluralizeModels(ModelEnum.MEETING),
        description: Descriptions.MeetingDescription,
      },
      {
        title: "Veiklos (veiksmai)",
        icon: Icons.DOING,
        key: pluralizeModels(ModelEnum.DOING),
        description: Descriptions.DoingDescription,
      },
      {
        title: "Pareigybės",
        icon: Icons.DUTY,
        key: pluralizeModels(ModelEnum.DUTY),
        description: Descriptions.DutyDescription,
      },
      {
        title: "Tikslai",
        icon: Icons.GOAL,
        key: pluralizeModels(ModelEnum.GOAL),
        description: Descriptions.GoalDescription,
      },
      {
        title: "Kontaktai",
        icon: Icons.CONTACT,
        key: pluralizeModels(ModelEnum.CONTACT),
        description: Descriptions.ContactDescription,
      },
    ],
  },
  {
    groupName: "Bendri",
    entities: [
      {
        title: "Komentarai",
        icon: Icons.COMMENT,
        key: "comments",
        description: Descriptions.CommentDescription,
      },
      {
        title: "Dokumentai",
        icon: Icons.SHAREPOINT_FILE,
        key: pluralizeModels(ModelEnum.SHAREPOINT_FILE),
        description: Descriptions.SharepointFileDescription,
      },
      {
        title: "Užduotys",
        icon: Icons.TASK,
        key: pluralizeModels(ModelEnum.TASK),
        description: Descriptions.TaskDescription,
      },
      {
        title: "Ryšiai",
        icon: Icons.RELATIONSHIP,
        key: pluralizeModels(ModelEnum.RELATIONSHIP),
        description: Descriptions.RelationshipDescription,
      },
      {
        title: "Rolės",
        icon: Icons.ROLE,
        key: pluralizeModels(ModelEnum.ROLE),
        description: Descriptions.RoleDescription,
      },
      {
        title: "Leidimai",
        icon: Icons.PERMISSION,
        key: pluralizeModels(ModelEnum.PERMISSION),
        description: Descriptions.PermissionDescription,
      },
    ],
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
