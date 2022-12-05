<template>
  <PageContent :title="dutyInstitution.name">
    <div class="mb-4 flex gap-4">
      <QuickActionButton :icon="PeopleTeamAdd24Filled"
        >Pranešti apie artėjantį posėdį</QuickActionButton
      >
      <QuickActionButton :icon="DocumentAdd24Filled"
        >Įkelti posėdžio protokolą</QuickActionButton
      >
    </div>
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
            Klausimas – tai dalykas, kurį bando išspręsti ši institucija šiuo
            metu.
          </p></HelpTextModal
        >
      </div>
      <NDataTable
        :data="dutyInstitution.questions"
        :columns="columns"
      ></NDataTable>
    </div>
    <template #after-heading>
      <InstitutionAvatarGroup :users="dutyInstitution.users" />
    </template>
    <template #below-header>
      <div class="mb-4">
        <NTag
          v-for="type in dutyInstitution.types"
          :key="type.id"
          size="small"
          :bordered="false"
        >
          {{ type.title }}
        </NTag>
      </div>
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
  AddCircle32Regular,
  ArrowTurnRight20Filled,
  BookQuestionMark20Filled,
  DocumentAdd24Filled,
  DocumentAdd24Regular,
  Edit20Filled,
  PeopleTeam32Filled,
  PeopleTeamAdd24Filled,
  PersonQuestionMark20Filled,
} from "@vicons/fluent";
import { Link, useForm } from "@inertiajs/inertia-vue3";
import {
  NAvatar,
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
  NTag,
} from "naive-ui";
import { h, ref } from "vue";
import route from "ziggy-js";

import { questionOptions } from "@/Composables/someTypes";
import HelpTextModal from "@/Components/HelpTextModal.vue";
import InstitutionAvatarGroup from "@/Components/Admin/Misc/InstitutionAvatarGroup.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import QuickActionButton from "@/Components/Admin/Buttons/QuickActionButton.vue";

const props = defineProps<{
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
    width: 100,
  },
  {
    title: "Veiklų skaičius",
    key: "doings_count",
  },
  {
    title: "Paskutinis atnaujinimas",
    key: "last_activity",
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
