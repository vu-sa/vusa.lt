<template>
  <PageContent :title="doing.title">
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
import { trans as $t } from "laravel-vue-i18n";
import {
  AddCircle32Regular,
  ArrowTurnRight20Filled,
  DocumentAdd24Regular,
  Edit20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NButton,
  NDataTable,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NModal,
  NPopover,
  NSelect,
} from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import HelpTextModal from "@/Components/HelpTextModal.vue";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

const props = defineProps<{
  doing: Record<string, any>;
  question: Record<string, any>;
}>();

const showModal = ref(false);
</script>
