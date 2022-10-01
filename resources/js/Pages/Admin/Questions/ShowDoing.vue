<template>
  <PageContent :title="doing.title">
    <template #aside-header>
      <ShowActivityLog :activities="doing.activities" />
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
import { NBreadcrumb, NBreadcrumbItem, NPopover } from "naive-ui";
// import { ref } from "vue";
import route from "ziggy-js";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import ShowActivityLog from "@/Components/Admin/Buttons/ShowActivityLog.vue";

defineProps<{
  doing: Record<string, any>;
  question: Record<string, any>;
}>();
</script>
