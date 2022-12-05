<template>
  <PageContent :title="question.title">
    <template #aside-header>
      <ShowActivityLog :activities="question.activities" />
    </template>
    <template #below-header>
      <NBreadcrumb class="mb-4 w-full">
        <NBreadcrumbItem
          @click="
            Inertia.get(route('dutyInstitutions.show', question.institution.id))
          "
          ><div>
            <NIcon
              class="mr-2"
              size="16"
              :component="PeopleTeam32Filled"
            ></NIcon
            >{{ question.institution.name }}
          </div></NBreadcrumbItem
        >
        <NBreadcrumbItem
          ><NPopover class="max-w-xl" placement="right"
            ><template #trigger>
              <div>
                <NIcon
                  class="mr-2"
                  size="16"
                  :component="BookQuestionMark20Filled"
                />{{ question.title }}
              </div> </template
            >{{ question.description }}</NPopover
          ></NBreadcrumbItem
        >
      </NBreadcrumb>
    </template>
    <div class="main-card w-full">
      <div class="mb-2 flex items-center gap-4">
        <h2 class="mb-0">Veiklos</h2>
        <NButton round size="tiny" secondary @click="showModal = true"
          ><template #icon><NIcon :component="Sparkle20Filled" /></template
          >Sukurti veiklą</NButton
        >
        <HelpTextModal class="ml-auto" title="Kas yra veikla?"
          ><p>
            Veikla – tai bet koks nutikęs veiksmas, susijęs su šiuo klausimu.
          </p></HelpTextModal
        >
      </div>

      <NDataTable :data="question.doings" :columns="columns"></NDataTable>
    </div>
  </PageContent>
  <NModal
    v-model:show="showModal"
    class="prose prose-sm max-w-xl dark:prose-invert"
    :title="`${$t('Sukurti veiklą')} (${question.title})`"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
  >
    <DoingForm
      :doing="doingTemplate"
      :question="question"
      :doing-types="doingTypes"
      model-route="doings.store"
      @success="showModal = false"
    ></DoingForm>
  </NModal>
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
  BookQuestionMark20Filled,
  DocumentAdd24Regular,
  Edit20Filled,
  PeopleTeam32Filled,
  Sparkle20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NButton,
  NDataTable,
  NIcon,
  NModal,
  NPopover,
} from "naive-ui";
import { h, ref } from "vue";
import route from "ziggy-js";

import DoingForm from "@/Components/Admin/Forms/DoingForm.vue";
import HelpTextModal from "@/Components/HelpTextModal.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import ShowActivityLog from "@/Components/Admin/Buttons/ShowActivityLog.vue";
import StatusTag from "@/Components/Admin/StatusTag.vue";
import getRelativeTime from "@/Composables/getRelativeTime";

const props = defineProps<{
  question: Record<string, any>;
  doingTypes: Record<string, any>;
}>();

const showModal = ref(false);

const doingTemplate = {
  title: "",
  type_id: "",
  status: "Sukurtas",
  // datetime now YYYY-MM-DD HH:MM:SS and delimit T
  date: new Date().toISOString().split("T").join(" ").slice(0, 16) + ":00",
};

const columns = [
  {
    title: "ID",
    key: "id",
  },
  {
    title: "Pavadinimas",
    key: "title",
  },
  {
    title: "Status",
    key: "status",
    render(row) {
      return h(StatusTag, {
        status: row.status,
      });
    },
  },
  {
    title: "Įvykio data",
    key: "date",
  },
  {
    title: "Paskutinis atnaujinimas",
    key: "updated_at",
    render(row) {
      return h("span", getRelativeTime(row.updated_at));
    },
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
            trigger: () =>
              h(
                NButton,
                {
                  size: "small",
                  tag: Link,
                  href: route("doings.show", {
                    question: props.question.id,
                    doing: row.id,
                  }),
                },
                {
                  default: () =>
                    h(NIcon, { component: ArrowTurnRight20Filled }),
                }
              ),
          }
        ),
        // h(
        //   NPopover,
        //   {},
        //   {
        //     default: () => "Pridėti failą prie įvykio",
        //     trigger: () =>
        //       h(
        //         NButton,
        //         { size: "small", secondary: true },
        //         { default: () => h(NIcon, { component: DocumentAdd24Regular }) }
        //       ),
        //   }
        // ),
        // h(
        //   NButton,
        //   { size: "small", secondary: true },
        //   { default: () => h(NIcon, { component: Edit20Filled }) }
        // ),
      ]);
    },
  },
];
</script>
