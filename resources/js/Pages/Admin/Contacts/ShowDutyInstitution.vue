<template>
  <PageContent breadcrumb>
    <template #above-header>
      <NBreadcrumb class="mb-4 w-full">
        <AdminBreadcrumbItem
          :visit-route="route('dashboard')"
          :icon="Home24Regular"
        >
          Pradinis
        </AdminBreadcrumbItem>
        <AdminBreadcrumbItem :icon="PeopleTeam32Filled">
          <NEllipsis style="max-width: 200px">
            {{ dutyInstitution.name }}</NEllipsis
          >
        </AdminBreadcrumbItem>
      </NBreadcrumb>
    </template>
    <template #title>
      <span class="text-3xl">{{ dutyInstitution.name }}</span>
    </template>
    <div class="mb-16 flex min-h-[16em] gap-4 py-2">
      <LastMeetingCard
        :last-meeting-doing="dutyInstitution.lastMeetingDoing"
        :duty-institution="dutyInstitution"
        :doing-types="doingTypes"
      ></LastMeetingCard>
      <DoingsNeedingAttentionCard
        :questions-with-doings="dutyInstitution.questions"
        :duty-institution="dutyInstitution"
      ></DoingsNeedingAttentionCard>
    </div>
    <NTabs
      :default-value="currentDutyInstitutionsTabPane"
      animated
      type="line"
      @update:value="updateDIsTabPane"
    >
      <NTabPane display-directive="show:lazy" name="Apie">
        <div class="m-4">
          <NCollapse
            arrow-placement="right"
            :default-expanded-names="['Studijų programos komitetas']"
          >
            <template v-for="type in dutyInstitution.types" :key="type.id">
              <NCollapseItem display-directive="show" :name="type.title">
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
                <p class="prose-sm dark:prose-invert">
                  {{ type.description }}
                </p>
                <div class="mt-2">
                  <ModelsDocumentViewer
                    v-if="type.documents.length > 0"
                    :model-collection-with-documents="[type]"
                    @file-button-click="updateSelectedDocument"
                  ></ModelsDocumentViewer>
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
      <NTabPane name="Svarstomi klausimai">
        <template #tab>
          <div class="flex gap-2">
            Svarstomi klausimai
            <NTag size="small" round>
              {{ dutyInstitution.questions.length }}
            </NTag>
          </div>
        </template>
        <QuestionsTabPane
          :institution="dutyInstitution"
          :questions="dutyInstitution.questions"
        ></QuestionsTabPane>
      </NTabPane>
      <NTabPane
        name="Susijusios institucijos"
        :disabled="
          dutyInstitution.givenRelationships.length === 0 &&
          dutyInstitution.receivedRelationships.length === 0
        "
      >
        <template #tab>
          <NIcon class="mr-1" :component="PeopleTeam32Filled"></NIcon>
          Susijusios institucijos
        </template>
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
      <NTabPane
        name="Veiklų dokumentai"
        :disabled="dutyInstitution.doings.length === 0"
        display-directive="show:lazy"
      >
        <template #tab>
          <NIcon class="mr-1" :component="Sparkle20Filled" /> Veiklų dokumentai
        </template>
        <div class="m-4">
          <ModelsDocumentViewer
            v-if="dutyInstitution.doings.length > 0"
            :model-collection-with-documents="dutyInstitution.doings"
            @file-button-click="updateSelectedDocument"
          ></ModelsDocumentViewer>
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
  <FileSelectDrawer
    :document="selectedDocument"
    @close-drawer="selectedDocument = documentTemplate"
  ></FileSelectDrawer>
</template>

<script setup lang="tsx">
import {
  Edit20Filled,
  Home24Regular,
  PeopleTeam32Filled,
  Sparkle20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NButton,
  NCollapse,
  NCollapseItem,
  NEllipsis,
  NIcon,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import { useStorage } from "@vueuse/core";
import route from "ziggy-js";

import { documentTemplate } from "@/Composables/someTypes";
import AdminBreadcrumbItem from "@/Components/BreadcrumbItems/AdminBreadcrumbItem.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import DoingsNeedingAttentionCard from "@/Components/Cards/QuickContentCards/DoingsNeedingAttentionCard.vue";
import DutyInstitutionCard from "@/Components/Cards/DutyInstitutionCard.vue";
import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import LastMeetingCard from "@/Components/Cards/QuickContentCards/LastMeetingCard.vue";
import ModelsDocumentViewer from "@/Components/SharepointFileManager/ModelsDocumentViewer.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import QuestionsTabPane from "@/Components/TabPaneContent/QuestionsTabPane.vue";

defineOptions({ layout: AdminLayout });

defineProps<{
  doingTypes: any;
  dutyInstitution: App.Models.DutyInstitution;
}>();

const selectedDocument = ref(null);
const currentDutyInstitutionsTabPane = useStorage(
  "admin-CurrentDutyInstitutionsTabPane",
  "Apie"
);

const updateDIsTabPane = (value) => {
  currentDutyInstitutionsTabPane.value = value;
};

const updateSelectedDocument = (document) => {
  selectedDocument.value = document;
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

  // don't return undefined values, but empty array
  return relationshipModels;
};
</script>
