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

import * as Descriptions from "@/Components/EntityDescriptions/DescriptionComponents";
import { Models } from "@/Types/enums";
import EntityDescription from "@/Components/EntityDescriptions/EntityDescription.vue";
import Icons from "@/Types/Icons/regular";
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

const pluralize = (word: string) => {
  if (word.endsWith("y")) {
    return word.slice(0, -1) + "ies";
  }

  if (word === "navigation") {
    return word;
  }

  if (word === "calendar") {
    return word;
  }

  return word + "s";
};

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

const permissableItems = [
  {
    groupName: "vusa.lt",
    entities: [
      {
        title: "Puslapiai",
        icon: Icons.PAGE,
        key: pluralize(Models.PAGE),
        description: Descriptions.PageDescription,
      },
      {
        title: "Naujienos",
        icon: Icons.NEWS,
        key: pluralize(Models.NEWS),
        description: Descriptions.NewsDescription,
      },
      {
        title: "Baneriai",
        icon: Icons.BANNER,
        key: pluralize(Models.BANNER),
        description: Descriptions.BannerDescription,
      },
      {
        title: "Kalendorius",
        icon: Icons.CALENDAR,
        key: pluralize(Models.CALENDAR),
        description: Descriptions.CalendarDescription,
      },
      {
        title: "Greitieji mygtukai",
        icon: Icons.MAINPAGE,
        key: pluralize(Models.MAINPAGE),
        description: Descriptions.MainPageDescription,
      },
      {
        title: "Navigacija",
        icon: Icons.NAVIGATION,
        key: pluralize(Models.NAVIGATION),
        description: Descriptions.NavigationDescription,
      },
      {
        title: "Sąžiningai egzaminai",
        icon: Icons.SAZININGAIEXAM,
        key: pluralize(Models.SAZININGAIEXAM),
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
        key: pluralize(Models.USER),
        description: Descriptions.UserDescription,
      },
      {
        title: "Institucijos",
        icon: Icons.INSTITUTION,
        key: pluralize(Models.INSTITUTION),
        description: Descriptions.InstitutionDescription,
      },
      {
        title: "Svarstomi klausimai (temos)",
        icon: Icons.MATTER,
        key: pluralize(Models.MATTER),
        description: Descriptions.MatterDescription,
      },
      {
        title: "Susitikimai",
        icon: Icons.MEETING,
        key: pluralize(Models.MEETING),
        description: Descriptions.MeetingDescription,
      },
      {
        title: "Veiklos (veiksmai)",
        icon: Icons.DOING,
        key: pluralize(Models.DOING),
        description: Descriptions.DoingDescription,
      },
      {
        title: "Pareigybės",
        icon: Icons.DUTY,
        key: pluralize(Models.DUTY),
        description: Descriptions.DutyDescription,
      },
      {
        title: "Tikslai",
        icon: Icons.GOAL,
        key: pluralize(Models.GOAL),
        description: Descriptions.GoalDescription,
      },
      {
        title: "Kontaktai",
        icon: Icons.CONTACT,
        key: pluralize(Models.CONTACT),
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
        icon: Icons.SHAREPOINTDOCUMENT,
        key: pluralize(Models.SHAREPOINTDOCUMENT),
        description: Descriptions.SharepointDocumentDescription,
      },
      {
        title: "Užduotys",
        icon: Icons.TASK,
        key: pluralize(Models.TASK),
        description: Descriptions.TaskDescription,
      },
      {
        title: "Ryšiai",
        icon: Icons.RELATIONSHIP,
        key: pluralize(Models.RELATIONSHIP),
        description: Descriptions.RelationshipDescription,
      },
      {
        title: "Rolės",
        icon: Icons.ROLE,
        key: pluralize(Models.ROLE),
        description: Descriptions.RoleDescription,
      },
      {
        title: "Leidimai",
        icon: Icons.PERMISSION,
        key: pluralize(Models.PERMISSION),
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
