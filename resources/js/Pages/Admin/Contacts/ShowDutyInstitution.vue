<template>
  <PageContent :title="dutyInstitution.name">
    <div class="mb-4 flex gap-4 py-2">
      <NewMeetingButton
        :duty-institution="dutyInstitution"
        :doing-types="doingTypes"
        >Pranešti apie artėjantį posėdį</NewMeetingButton
      >
      <QuickActionButton :icon="DocumentAdd24Filled"
        >Įkelti posėdžio protokolą</QuickActionButton
      >
    </div>
    <NTabs animated type="card">
      <NTabPane name="Aprašymas">
        <div class="m-4">
          <template v-for="type in dutyInstitution.types" :key="type.id">
            <NTag size="small" :bordered="false">
              {{ type.title }}
            </NTag>
            <p class="prose-sm mt-2 dark:prose-invert">
              {{ type.description }}
            </p>
          </template>
        </div>
      </NTabPane>
      <NTabPane name="Klausimai">
        <div class="main-card">
          <div class="mb-2 flex items-center gap-4">
            <h2 class="mb-0">Klausimai</h2>
            <NButton round size="tiny" secondary @click="showModal = true"
              ><template #icon
                ><NIcon :component="BookQuestionMark20Filled" /></template
              >Sukurti klausimą</NButton
            >
            <HelpTextModal class="ml-auto" title="Kas yra klausimas?"
              ><p>
                Klausimas – tai dalykas, kurį bando išspręsti ši institucija
                šiuo metu.
              </p></HelpTextModal
            >
          </div>
          <NDataTable
            :data="dutyInstitution.questions"
            :columns="columns"
          ></NDataTable>
        </div>
      </NTabPane>
    </NTabs>
    <template #after-heading>
      <InstitutionAvatarGroup :users="users" />
    </template>
    <template #aside-header>
      <NButton
        secondary
        circle
        @click="
          Inertia.visit(route('dutyInstitutions.edit', dutyInstitution.id))
        "
        ><template #icon><NIcon :component="Edit20Filled"></NIcon></template
      ></NButton>
    </template>
  </PageContent>
  <NModal
    v-model:show="showModal"
    class="prose prose-sm max-w-xl dark:prose-invert"
    :title="`${$t('Sukurti klausimą')} (${dutyInstitution.name})`"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
  >
    <NForm :model="questionForm">
      <NGrid cols="1">
        <NFormItemGi label="Klausimo pavadinimas" path="title" required>
          <NSelect
            v-model:value="questionForm.title"
            placeholder="Studijų tinklelio peržiūra"
            filterable
            tag
            :options="questionOptions"
            ><template #action>
              <span
                class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
                >Gali įrašyti ir savo klausimą...</span
              >
            </template></NSelect
          >
        </NFormItemGi>
        <NFormItemGi label="Aprašymas" path="description">
          <NInput
            v-model:value="questionForm.description"
            type="textarea"
            placeholder="Aprašykite klausimo kontekstą, jeigu to reikia..."
          ></NInput>
        </NFormItemGi>
        <NFormItemGi :show-label="false"
          ><NButton type="primary" @click="createQuestion"
            >Sukurti</NButton
          ></NFormItemGi
        >
      </NGrid>
    </NForm>
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
  ArrowTurnRight20Filled,
  BookQuestionMark20Filled,
  DocumentAdd24Filled,
  Edit20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link, useForm } from "@inertiajs/inertia-vue3";
import {
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
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { h, ref } from "vue";
import route from "ziggy-js";

import { questionOptions } from "@/Composables/someTypes";
import HelpTextModal from "@/Components/HelpTextModal.vue";
import InstitutionAvatarGroup from "@/Components/Admin/Misc/InstitutionAvatarGroup.vue";
import NewMeetingButton from "@/Components/Admin/QActButtons/NewMeetingButton.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import QuickActionButton from "@/Components/Admin/Buttons/QuickActionButton.vue";
import StatusTag from "@/Components/Admin/StatusTag.vue";

const props = defineProps<{
  doingTypes: any;
  dutyInstitution: App.Models.DutyInstitution;
  users: App.Models.User[];
}>();

const showModal = ref(false);

const columns = [
  {
    title: "ID",
    key: "id",
    width: 50,
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
    title: "Veiklų skaičius",
    key: "doings_count",
  },
  {
    title: "Klausimo sukūrimo data",
    key: "created_at",
  },
  {
    title: "Veiksmai",
    key: "actions",
    render(row) {
      return h(
        "div",
        { class: "flex gap-2" },
        {
          default: () => [
            h(
              NPopover,
              {},
              {
                default: () => "Eiti į klausimą",
                trigger: () =>
                  h(
                    NButton,
                    {
                      size: "small",
                      tag: Link,
                      href: route("dutyInstitutions.questions.show", {
                        dutyInstitution: props.dutyInstitution.id,
                        question: row.id,
                      }),
                    },
                    h(NIcon, { component: ArrowTurnRight20Filled })
                  ),
              }
            ),
            // h(
            //   NPopover,
            //   {},
            //   {
            //     default: () => "Pridėk failą prie įvykio",
            //     trigger: () =>
            //       h(
            //         NButton,
            //         { size: "small", secondary: true },
            //         {
            //           default: () =>
            //             h(NIcon, { component: DocumentAdd24Regular }),
            //         }
            //       ),
            //   }
            // ),
            // h(
            //   NButton,
            //   { size: "small", secondary: true },
            //   { default: () => h(NIcon, { component: Edit20Filled }) }
            // ),
            // h(
            //   NButton,
            //   { round: true, size: "small", type: "error" },
            //   { default: () => "Ištrinti" }
            // ),
          ],
        }
      );
    },
  },
];

const questionForm = useForm({
  title: "",
  description: "",
});

const createQuestion = () => {
  questionForm.post(
    route("dutyInstitutions.questions.store", {
      dutyInstitution: props.dutyInstitution.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        showModal.value = false;
        questionForm.reset();
      },
    }
  );
};
</script>
