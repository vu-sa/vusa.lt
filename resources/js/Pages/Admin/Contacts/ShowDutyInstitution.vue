<template>
  <PageContent :title="dutyInstitution.name">
    <div class="main-card">
      <div class="mb-2 flex items-center gap-4">
        <h2 class="mb-0">Klausimai</h2>
        <NButton round size="tiny" secondary @click="showModal = true"
          ><template #icon><NIcon :component="AddCircle32Regular" /></template
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
    <template #aside-card>
      <div class="main-card max-w-sm">
        <h3>Institucijos nariai</h3>
        <div class="flex flex-row flex-wrap gap-2">
          <div
            v-for="user in users"
            :key="user.id"
            class="flex w-fit items-center gap-2"
          >
            <NAvatar round :src="user.profile_photo_path"></NAvatar
            >{{ user.name }}
          </div>
        </div>
      </div>
    </template>
  </PageContent>
  <NModal
    v-model:show="showModal"
    class="prose-sm prose max-w-xl dark:prose-invert"
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
          ><NButton type="primary">Sukurti</NButton></NFormItemGi
        >
      </NGrid>
    </NForm>
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
  AddCircle32Regular,
  ArrowTurnRight20Filled,
  DocumentAdd24Regular,
  Edit20Filled,
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
} from "naive-ui";
import { ref } from "vue";
import HelpTextModal from "@/Components/HelpTextModal.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import route from "ziggy-js";

defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  users: App.Models.User[];
}>();

const showModal = ref(false);

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
                      href: route("questions.show", row.id),
                    },
                    h(NIcon, { component: ArrowTurnRight20Filled })
                  ),
              }
            ),
            h(
              NPopover,
              {},
              {
                default: () => "Pridėk failą prie įvykio",
                trigger: () =>
                  h(
                    NButton,
                    { size: "small", secondary: true },
                    {
                      default: () =>
                        h(NIcon, { component: DocumentAdd24Regular }),
                    }
                  ),
              }
            ),
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

const questionOptions = [
  {
    label: "Studijų tinklelio peržiūra",
    value: "Studijų tinklelio peržiūra",
  },
  {
    label: "Studentų nuomonės išnagrinėjimas posėdyje",
    value: "Studentų nuomonės išnagrinėjimas posėdyje",
  },
  {
    label: "Dėstytojo keitimas",
    value: "Dėstytojo keitimas",
  },
];

const questionForm = useForm({
  title: "",
  description: "",
});
</script>
