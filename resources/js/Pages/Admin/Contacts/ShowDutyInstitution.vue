<template>
  <PageContent :title="dutyInstitution.name">
    <div class="mb-4 flex gap-4 py-2">
      <NewMeetingButton
        :duty-institution="dutyInstitution"
        :doing-types="doingTypes"
      />
      <MeetingDocumentButton
        :duty-institution="dutyInstitution"
        :questions="dutyInstitution.questions"
      />
    </div>
    <NTabs animated type="card">
      <NTabPane display-directive="show" name="Aprašymas">
        <div class="m-4">
          <NCollapse
            arrow-placement="right"
            :default-expanded-names="['Studijų programos komitetas']"
          >
            <template v-for="type in dutyInstitution.types" :key="type.id">
              <NCollapseItem :name="type.title">
                <template #header>
                  <NTag
                    class="cursor-pointer"
                    size="small"
                    strong
                    :bordered="false"
                  >
                    <span>{{ type.title }}</span>
                  </NTag>
                </template>
                <div class="mt-2">
                  <p class="prose-sm dark:prose-invert">
                    {{ type.description }}
                  </p>
                  <ModelDocumentButtons
                    v-if="type.documents.length > 0"
                    :documents="type.documents"
                    :model="{ id: type.id, model_type: 'App\\Models\\Type' }"
                    @file-button-click="updateSelectedDocument"
                  ></ModelDocumentButtons>
                  <!-- Something is amiss here -->
                  <template v-if="typeRelationships(type).length > 0">
                    <h3 class="my-4">Susijusios institucijos pagal tipą</h3>
                    <DutyInstitutionCard
                      v-for="institution in typeRelationships(type)[0]"
                      :key="institution.id"
                      :institution="institution"
                    ></DutyInstitutionCard>
                  </template>
                </div>
              </NCollapseItem>
            </template>
          </NCollapse>
        </div>
      </NTabPane>
      <NTabPane name="Klausimai">
        <template #tab>
          <div class="flex gap-2">
            {{ $t("Klausimai") }}
            <NTag size="small" round>
              {{ dutyInstitution.questions.length }}
            </NTag>
          </div>
        </template>
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
      <NTabPane name="Susijusios institucijos">
        <div class="m-4">
          <template v-if="dutyInstitution.givenRelationships.length > 0">
            <h3>Suteikti ryšiai</h3>
            <div class="my-4 flex max-w-4xl flex-wrap gap-4">
              <template
                v-for="relationship in dutyInstitution.givenRelationships"
                :key="relationship.pivot.related_model_id"
              >
                <DutyInstitutionCard
                  :institution="relationship.pivot.related_model"
                ></DutyInstitutionCard>
              </template>
            </div>
          </template>
          <template v-if="dutyInstitution.receivedRelationships.length > 0">
            <h3>Įgyti ryšiai</h3>
            <div class="my-4 flex max-w-4xl flex-wrap gap-4">
              <template
                v-for="relationship in dutyInstitution.receivedRelationships"
                :key="relationship.pivot.related_model_id"
              >
                <DutyInstitutionCard
                  :institution="relationship.pivot.related_model"
                ></DutyInstitutionCard>
              </template>
            </div>
          </template>
        </div>
      </NTabPane>
    </NTabs>
    <template #after-heading>
      <InstitutionAvatarGroup :users="dutyInstitution.users" />
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
  <FileSelectDrawer
    :document="selectedDocument"
    @close-drawer="selectedDocument = documentTemplate"
  ></FileSelectDrawer>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import DutyInstitutionCard from "@/Components/Admin/Cards/DutyInstitutionCard.vue";

export default {
  components: { DutyInstitutionCard },
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  ArrowTurnRight20Filled,
  BookQuestionMark20Filled,
  Edit20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link, useForm } from "@inertiajs/inertia-vue3";
import {
  NButton,
  NCollapse,
  NCollapseItem,
  NDataTable,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NMessageProvider,
  NModal,
  NPopover,
  NSelect,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { computed, h, ref } from "vue";
import route from "ziggy-js";

import { documentTemplate, questionOptions } from "@/Composables/someTypes";
import FileSelectDrawer from "@/Components/Admin/Nav/FileSelectDrawer.vue";
import HelpTextModal from "@/Components/HelpTextModal.vue";
import InstitutionAvatarGroup from "@/Components/Admin/Misc/InstitutionAvatarGroup.vue";
import MeetingDocumentButton from "@/Components/Admin/QActButtons/MeetingDocumentButton.vue";
import ModelDocumentButtons from "@/Components/Admin/ModelDocumentButtons.vue";
import NewMeetingButton from "@/Components/Admin/QActButtons/NewMeetingButton.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import StatusTag from "@/Components/Admin/StatusTag.vue";

const props = defineProps<{
  doingTypes: any;
  dutyInstitution: App.Models.DutyInstitution;
}>();

const showModal = ref(false);

const selectedDocument = ref(null);

const updateSelectedDocument = (document) => {
  console.table(document);
  selectedDocument.value = document;
};

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

const typeRelationships = (type) => {
  // create array
  let relationshipModels = [];

  relationshipModels = relationshipModels.concat(
    type.givenRelationships.map((relationshipPacket) => {
      return relationshipPacket.relationships.map((relationship) => {
        return relationship.receiver_model;
      });
    })
  );

  // append receivedRelationships
  relationshipModels = relationshipModels.concat(
    type.receivedRelationships.map((relationshipPacket) => {
      return relationshipPacket.relationships.map((relationship) => {
        return relationship.giver_model;
      });
    })
  );

  console.log(relationshipModels, relationshipModels[0]);

  // don't return undefined values, but empty array
  return relationshipModels;
};
</script>
