<template>
  <PageContent :title="question.title">
    <template #aside-header>
      <ShowActivityLog :activities="question.activities" />
    </template>
    <template #below-header>
      <NBreadcrumb class="w-full">
        <NBreadcrumbItem
          @click="Inertia.get(route('dutyInstitutions.show', question.institution.id))"
          >{{ question.institution.name }}</NBreadcrumbItem
        >
        <NBreadcrumbItem
          ><NPopover class="max-w-xl" placement="right"
            ><template #trigger>{{ question.title }}</template
            >{{ question.description }}</NPopover
          ></NBreadcrumbItem
        >
      </NBreadcrumb>
    </template>
    <div class="main-card w-full">
      <div class="mb-2 flex items-center gap-4">
        <h2 class="mb-0">Veiklos</h2>
        <NButton round size="tiny" secondary @click="showModal = true"
          ><template #icon><NIcon :component="AddCircle32Regular" /></template>Sukurti
          veiklą</NButton
        >
        <HelpTextModal class="ml-auto" title="Kas yra veikla?"
          ><p>
            Veikla – tai bet koks nutikęs veiksmas, susijęs su šiuo klausimu.
          </p></HelpTextModal
        >
      </div>
      <NTimeline v-if="question.doings.length > 0" horizontal class="overflow-auto py-8">
        <NTimelineItem
          v-for="doing in question.doings"
          :key="doing.id"
          :content="doing.title"
          :time="doing.date"
          ><template #header
            ><Link
              :href="
                route('doings.show', {
                  question: question.id,
                  doing: doing.id,
                })
              "
              >{{ doing.title }}</Link
            ></template
          ></NTimelineItem
        >
      </NTimeline>
      <p v-else>Jokių veiklų nerasta.</p>
    </div>
    <template #aside-card>
      <div class="main-card w-80">
        <h2 class="mb-0">Komentarai</h2>
      </div></template
    >
  </PageContent>
  <NModal
    v-model:show="showModal"
    class="prose-sm prose max-w-xl dark:prose-invert"
    :title="`${$t('Sukurti veiklą')} (${question.title})`"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
  >
    <NForm :model="doingForm">
      <NGrid cols="2">
        <NFormItemGi label="Veiklos pavadinimas" path="title" required :span="2">
          <NSelect
            v-model:value="doingForm.title"
            placeholder="Susitikimas su studentais"
            filterable
            tag
            :options="doingOptions"
            ><template #action>
              <span class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
                >Gali įrašyti ir savo veiklą...</span
              >
            </template></NSelect
          >
        </NFormItemGi>
        <NFormItemGi label="Tipas" path="doing_type_id" required
          ><NSelect
            v-model:value="doingForm.type_id"
            placeholder="Pasirinkti tipą"
            filterable
            :options="doingTypes"
          ></NSelect
        ></NFormItemGi>

        <NFormItemGi :span="2" :show-label="false"
          ><NButton type="primary" @click="createDoing">Sukurti</NButton></NFormItemGi
        >
      </NGrid>
    </NForm>
  </NModal>
</template>

<script lang="ts">
import { h } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
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
import { Link } from "@inertiajs/inertia-vue3";
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
  NTimeline,
  NTimelineItem,
} from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import HelpTextModal from "@/Components/HelpTextModal.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import ShowActivityLog from "@/Components/Admin/Buttons/ShowActivityLog.vue";

const props = defineProps<{
  question: Record<string, any>;
  doingTypes: Record<string, any>;
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

const doingOptions = [
  {
    label: "Susitikimas su studentais",
    value: "Susitikimas su studentais",
  },
  {
    label: "Planuotas posėdis",
    value: "Planuotas posėdis",
  },
  {
    label: "Susitikimas su koordinatoriumi",
    value: "Susitikimas su koordinatoriumi",
  },
];

const doingForm = useForm({
  title: "",
  type_id: "",
});

const createDoing = () => {
  doingForm.post(route("doings.store", { question: props.question.id }), {
    onSuccess: () => {
      showModal.value = false;
    },
  });
};
</script>
