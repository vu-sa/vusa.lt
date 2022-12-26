<template>
  <PageContent :title="dutyInstitution.name" breadcrumb>
    <template #above-header>
      <NBreadcrumb class="mb-4 w-full">
        <AdminBreadcrumbItem
          :visit-route="route('dashboard')"
          :icon="Home24Regular"
        >
          Pradinis
        </AdminBreadcrumbItem>
        <AdminBreadcrumbItem :icon="PeopleTeam32Filled">
          {{ dutyInstitution.name }}
        </AdminBreadcrumbItem>
      </NBreadcrumb>
    </template>
    <template #below-header>
      <div
        v-if="dutyInstitution.lastMeetingDoing"
        class="mb-2 flex flex-row items-center"
      >
        <span>Paskutinis posėdis vyko:</span>
        <NIcon class="mx-1" :component="CalendarClock24Filled"></NIcon>

        <a
          target="_blank"
          :href="route('doings.show', dutyInstitution.lastMeetingDoing?.id)"
        >
          <span class="font-bold">{{
            getRelativeTime(dutyInstitution.lastMeetingDoing?.date)
          }}</span> </a
        >.
      </div>
    </template>
    <div class="mb-4 flex min-h-[16em] gap-4 py-2">
      <NewMeetingButton
        :duty-institution="dutyInstitution"
        :doing-types="doingTypes"
      />
      <MeetingDocumentButton
        :duty-institution="dutyInstitution"
        :questions="dutyInstitution.questions"
      />
    </div>
    <NTabs animated type="line">
      <NTabPane display-directive="show:lazy" name="Aprašymas">
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
  CalendarClock24Filled,
  Edit20Filled,
  Home24Regular,
  PeopleTeam32Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NButton,
  NCollapse,
  NCollapseItem,
  NIcon,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import { documentTemplate } from "@/Composables/someTypes";
import AdminBreadcrumbItem from "@/Components/BreadcrumbItems/AdminBreadcrumbItem.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import DutyInstitutionCard from "@/Components/Cards/DutyInstitutionCard.vue";
import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import MeetingDocumentButton from "@/Components/Buttons/QActButtons/MeetingDocumentButton.vue";
import ModelsDocumentViewer from "@/Components/SharepointFileManager/ModelsDocumentViewer.vue";
import NewMeetingButton from "@/Components/Buttons/QActButtons/NewMeetingButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import QuestionsTabPane from "@/Components/TabPaneContent/QuestionsTabPane.vue";
import getRelativeTime from "@/Composables/getRelativeTime";

defineOptions({ layout: AdminLayout });

defineProps<{
  doingTypes: any;
  dutyInstitution: App.Models.DutyInstitution;
}>();

const selectedDocument = ref(null);

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
