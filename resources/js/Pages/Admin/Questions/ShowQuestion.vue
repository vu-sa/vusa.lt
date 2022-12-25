<template>
  <PageContent :title="question.title" breadcrumb>
    <template #above-header>
      <NBreadcrumb class="mb-4 w-full">
        <NBreadcrumbItem @click="Inertia.get(route('dashboard'))">
          <div>
            <NIcon class="mr-2" size="16" :component="Home24Regular"> </NIcon>

            Pradinis
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          @click="
            Inertia.get(route('dutyInstitutions.show', question.institution.id))
          "
          ><div>
            <NIcon class="mr-2" size="16" :component="PeopleTeam32Filled">
            </NIcon
            >{{ question.institution.name }}
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem>
          <div>
            <NIcon
              class="mr-2"
              size="16"
              :component="BookQuestionMark20Filled"
            />
            {{ question.title }}
          </div>
        </NBreadcrumbItem>
      </NBreadcrumb>
    </template>
    <template #title>
      <span class="inline-flex items-center"
        ><NIcon class="mr-2" :component="BookQuestionMark20Filled" />
        {{ question.title }}</span
      >
    </template>
    <template #after-heading>
      <StatusTag :status="question.status" />
    </template>
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ShowActivityLog :activities="question.activities" />
        <NButton secondary circle @click="showQuestionModal = true"
          ><template #icon
            ><NIcon :component="DocumentEdit24Regular"></NIcon></template
        ></NButton>
      </div>
    </template>

    <NTabs animated type="line" default-value="Veiklos">
      <NTabPane name="Aprašymas">
        <p>{{ question.description }}</p>
      </NTabPane>
      <NTabPane name="Veiklos">
        <div class="main-card w-full">
          <div class="mb-2 flex items-center gap-4">
            <h2 class="mb-0">Veiklos</h2>
            <NButton round size="tiny" secondary @click="showDoingModal = true"
              ><template #icon><NIcon :component="Sparkle20Filled" /></template
              >Sukurti veiklą</NButton
            >
            <HelpTextModal class="ml-auto" title="Kas yra veikla?"
              ><p>
                Veikla – tai bet koks nutikęs veiksmas, susijęs su šiuo
                klausimu.
              </p></HelpTextModal
            >
          </div>

          <NDataTable :data="question.doings" :columns="columns"></NDataTable>
        </div>
      </NTabPane>
    </NTabs>
  </PageContent>
  <NModal
    v-model:show="showDoingModal"
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
      @success="showDoingModal = false"
    ></DoingForm>
  </NModal>
  <NModal
    v-model:show="showQuestionModal"
    class="prose prose-sm max-w-xl dark:prose-invert"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
    ><QuestionForm
      :form="question"
      :duty-institution="question.institution"
      @question-stored="showQuestionModal = false"
    ></QuestionForm
  ></NModal>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  ArrowTurnRight20Filled,
  BookQuestionMark20Filled,
  DocumentEdit24Regular,
  Home24Regular,
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
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import DoingForm from "@/Components/AdminForms/DoingForm.vue";
import HelpTextModal from "@/Components/Buttons/HelperButtons/HelpTextModal.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import QuestionForm from "@/Components/AdminForms/QuestionForm.vue";
import ShowActivityLog from "@/Components/Buttons/ShowActivityLog.vue";
import StatusTag from "@/Components/Tags/StatusTag.vue";
import getRelativeTime from "@/Composables/getRelativeTime";

defineOptions({ layout: AdminLayout });

const props = defineProps<{
  question: Record<string, any>;
  doingTypes: Record<string, any>;
}>();

const showDoingModal = ref(false);
const showQuestionModal = ref(false);

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
    title: "Tipai",
    key: "types",
    render(row) {
      return (
        <div>
          {row.types.map(({ title }) => {
            return <NTag size="small">{title}</NTag>;
          })}
        </div>
      );
    },
  },
  {
    title: "Status",
    key: "status",
    render(row) {
      return <StatusTag status={row.status} />;
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
      return <span>{getRelativeTime(row.updated_at)}</span>;
    },
  },
  {
    title: "Veiksmai",
    key: "actions",
    render(row) {
      return (
        <div class="flex gap-2">
          <NPopover>
            {{
              default: () => "Peržiūrėti įvykį",
              trigger: () => (
                <Link
                  href={route("doings.show", {
                    question: props.question.id,
                    doing: row.id,
                  })}
                >
                  <NButton size="small">
                    {{
                      icon: () => <NIcon component={ArrowTurnRight20Filled} />,
                    }}
                  </NButton>
                </Link>
              ),
            }}
          </NPopover>
          <DeleteModelButton
            model={row}
            size="small"
            modelRoute="doings.destroy"
            modelRouteParams={{
              doing: row.id,
            }}
          />
        </div>
      );
    },
  },
];
</script>
