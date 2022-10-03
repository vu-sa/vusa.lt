<template>
  <PageContent :title="doing.title">
    <template #aside-header>
      <div class="flex items-center gap-4">
        <NTag :bordered="false" round type="success">Sukurtas</NTag>
        <ShowActivityLog :activities="doing.activities" />
      </div>
    </template>
    <template #below-header>
      <NBreadcrumb class="w-full">
        <NBreadcrumbItem
          @click="
            Inertia.get(route('dutyInstitutions.show', question.institution.id))
          "
          >{{ question.institution.name }}</NBreadcrumbItem
        >
        <NBreadcrumbItem
          @click="
            Inertia.visit(
              route('dutyInstitutions.questions.show', {
                question: question.id,
                dutyInstitution: question.institution.id,
              })
            )
          "
          ><NPopover class="max-w-xl" placement="right"
            ><template #trigger>{{ question.title }}</template
            >{{ question.description }}</NPopover
          ></NBreadcrumbItem
        >
        <NBreadcrumbItem>{{ doing.title }}</NBreadcrumbItem>
      </NBreadcrumb>
    </template>
    <template #aside-card>
      <div>
        <h2>Užduotys</h2>
        <div class="main-card w-80">
          <TaskViewer :tasks="doing.tasks" />
        </div>
      </div>
    </template>
    <div class="main-card">
      <div class="mb-4 flex items-center gap-4">
        <h2 class="mb-0">Dokumentai</h2>
        <FileUploader
          :content-type-options="contentTypeOptions"
          :content-model="{ id: doing.id, type: 'App\\Models\\Doing' }"
        ></FileUploader>
      </div>
      <NDataTable :columns="columns" :data="documents"></NDataTable>
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NDataTable,
  NPopover,
  NSpace,
  NTag,
} from "naive-ui";
import { h } from "vue";
import route from "ziggy-js";

import FileUploader from "@/Components/Admin/Buttons/FileUploader.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import ShowActivityLog from "@/Components/Admin/Buttons/ShowActivityLog.vue";
import TaskViewer from "@/Components/Admin/Tasks/TaskViewer.vue";

defineProps<{
  doing: Record<string, any>;
  question: Record<string, any>;
  documents: Record<string, any>[];
}>();

const columns = [
  {
    title: "Pavadinimas",
    key: "name",
    render(row) {
      return h("a", { href: row.webUrl, target: "_blank" }, row.name);
    },
  },
  {
    title: "Tipas",
    key: "type",
  },
  {
    title: "Raktažodžiai",
    key: "keywords",
    render(row) {
      return h(
        NSpace,
        {},
        {
          default: () =>
            row.keywords?.map((keyword) =>
              h(NTag, {}, { default: () => keyword })
            ),
        }
      );
    },
  },
  {
    title: "Data",
    key: "date",
  },
  // {
  //   title: "Veiksmai",
  //   key: "actions",
  //   render(row) {
  //     return h(
  //       NSpace,
  //       {},
  //       {
  //         default: () =>
  //           h(
  //             NButton,
  //             {
  //               type: "error",
  //               onClick: () => handleDeleteClick(row.id),
  //             },
  //             { default: () => "Ištrinti" }
  //           ),
  //       }
  //     );
  //   },
  // },
];

const contentTypeOptions = [
  {
    label: "Protokolai",
    value: "Protokolai",
  },
  {
    label: "Pristatymai",
    value: "Pristatymai",
  },
];
</script>
