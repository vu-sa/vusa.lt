<template>
  <PageContent :title="question.title">
    <template #aside-header
      ><span class="prose dark:prose-invert">{{
        question.question_group.title
      }}</span></template
    >
    <NBreadcrumb class="mb-2 w-fit">
      <NBreadcrumbItem
        @click="
          Inertia.get(route('dutyInstitutions.show', question.institution.id))
        "
        >{{ question.institution.name }}</NBreadcrumbItem
      >
      <NBreadcrumbItem>{{ question.title }}</NBreadcrumbItem>
    </NBreadcrumb>
    <div class="main-card">abc</div>
  </PageContent>
  <NModal
    v-model:show="showModal"
    class="prose-sm prose max-w-xl dark:prose-invert"
    :title="$t('Sukurti klausimą')"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
  >
    <p>abc</p>
  </NModal>
</template>

<script lang="ts">
import { h } from "vue";
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  ArrowTurnRight20Filled,
  DocumentAdd24Regular,
  Edit20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NButton,
  NIcon,
  NModal,
  NPopover,
} from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

defineProps<{
  question: Record<string, any>;
}>();

const showModal = ref(false);

const columns = [
  {
    title: "Pavadinimas",
    key: "title",
  },
  {
    title: "Status",
    key: "status",
  },
  {
    title: "Paskutinis atnaujinimas",
    key: "last_activity",
  },
  {
    title: "Veiksmai",
    key: "actions",
    render(row) {
      return h("div", { class: "flex gap-2" }, [
        h(
          NPopover,
          {},
          {
            default: () => "Peržiūrėti įvykį",
            trigger: h(
              NButton,
              { size: "small" },
              h(NIcon, { component: ArrowTurnRight20Filled })
            ),
          }
        ),
        h(
          NPopover,
          {},
          {
            default: () => "Pridėti failą prie įvykio",
            trigger: h(
              NButton,
              { size: "small", secondary: true },
              h(NIcon, { component: DocumentAdd24Regular })
            ),
          }
        ),
        h(
          NButton,
          { size: "small", secondary: true },
          h(NIcon, { component: Edit20Filled })
        ),
        // h(
        //   NButton,
        //   { round: true, size: "small", type: "error" },
        //   { default: () => "Ištrinti" }
        // ),
      ]);
    },
  },
];
</script>
