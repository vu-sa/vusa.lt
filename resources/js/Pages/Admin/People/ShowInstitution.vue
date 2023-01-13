<template>
  <PageContent breadcrumb>
    <template #above-header>
      <AdminBreadcrumbDisplayer
        :options="breadcrumbOptions"
        class="mb-4 w-full"
      />
    </template>
    <template #title>
      <span class="text-3xl">{{ institution.name }}</span>
    </template>
    <div class="mb-16 flex min-h-[16em] gap-4 py-2">
      <LastMeetingCard
        :last-meeting="institution?.lastMeeting"
        :institution="institution"
        :doing-types="doingTypes"
        content-style="margin-top: 0.5em"
      ></LastMeetingCard>
      <!-- <MeetingsNeedingAttentionCard
        content-style="margin-top: 0.5em"
        :matters-with-meetings="institution.matters"
        :institution="institution"
      ></MeetingsNeedingAttentionCard> -->
    </div>
    <NTabs
      :default-value="currentInstitutionsTabPane"
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
            <template v-for="type in institution.types" :key="type.id">
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
                    <InstitutionCard
                      v-for="institution in typeRelationships(type)[0]"
                      :key="institution.id"
                      :institution="institution"
                    ></InstitutionCard>
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
              {{ institution.matters?.length }}
            </NTag>
          </div>
        </template>
        <MattersTabPane
          :institution="institution"
          :matters="institution.matters"
        ></MattersTabPane>
      </NTabPane>
      <NTabPane
        name="Susijusios institucijos"
        :disabled="
          institution.givenRelationships.length === 0 &&
          institution.receivedRelationships.length === 0
        "
      >
        <template #tab>
          <NIcon class="mr-1" :component="PeopleTeam24Filled"></NIcon>
          Susijusios institucijos
        </template>
        <div class="m-4">
          <template v-if="institution.givenRelationships.length > 0">
            <h3>Suteikti ryšiai</h3>
            <div class="my-4 flex max-w-4xl flex-wrap gap-4">
              <template
                v-for="relationship in institution.givenRelationships"
                :key="relationship.pivot.related_model_id"
              >
                <InstitutionCard
                  :institution="relationship.pivot.related_model"
                ></InstitutionCard>
              </template>
            </div>
          </template>
          <template v-if="institution.receivedRelationships.length > 0">
            <h3>Įgyti ryšiai</h3>
            <div class="my-4 flex max-w-4xl flex-wrap gap-4">
              <template
                v-for="relationship in institution.receivedRelationships"
                :key="relationship.pivot.related_model_id"
              >
                <InstitutionCard
                  :institution="relationship.pivot.related_model"
                ></InstitutionCard>
              </template>
            </div>
          </template>
        </div>
      </NTabPane>
      <NTabPane
        name="Posėdžių dokumentai"
        :disabled="institution.meetings?.length === 0"
        display-directive="show:lazy"
      >
        <template #tab>
          <NIcon class="mr-1" :component="DeviceMeetingRoomRemote24Filled" />
          Posėdžių dokumentai
        </template>
        <div class="m-4">
          <ModelsDocumentViewer
            v-if="institution.meetings?.length > 0"
            :model-collection-with-documents="institution.meetings"
            @file-button-click="updateSelectedDocument"
          ></ModelsDocumentViewer>
        </div>
      </NTabPane>
    </NTabs>
    <template #after-heading>
      <InstitutionAvatarGroup :users="institution.users" />
      <template v-if="institution.institutionManagers.length > 0">
        <NDivider v-if="institution.institutionManagers.length > 0" vertical />
        <span class="text-xs text-zinc-500">Admin:</span>
        <InstitutionAvatarGroup :users="institution.institutionManagers" />
      </template>
    </template>
    <template #aside-header>
      <div class="flex gap-2">
        <ActivityLogButton :activities="institution.activities" />

        <MoreOptionsButton
          edit
          @edit-click="
            Inertia.visit(route('institutions.edit', institution.id))
          "
        ></MoreOptionsButton>
      </div>
    </template>
  </PageContent>
  <FileSelectDrawer
    :document="selectedDocument"
    @close-drawer="selectedDocument = documentTemplate"
  ></FileSelectDrawer>
</template>

<script setup lang="tsx">
import {
  DeviceMeetingRoomRemote24Filled,
  PeopleTeam24Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NCollapse,
  NCollapseItem,
  NDivider,
  NIcon,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import { useStorage } from "@vueuse/core";

import { documentTemplate } from "@/Composables/someTypes";
import AdminBreadcrumbDisplayer from "@/Components/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import InstitutionCard from "@/Components/Cards/InstitutionCard.vue";
import LastMeetingCard from "@/Components/Cards/QuickContentCards/LastMeetingCard.vue";
import MattersTabPane from "@/Components/TabPaneContent/MattersTabPane.vue";
import MeetingsNeedingAttentionCard from "@/Components/Cards/QuickContentCards/MeetingsNeedingAttentionCard.vue";
import ModelsDocumentViewer from "@/Components/SharepointFileManager/ModelsDocumentViewer.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

const props = defineProps<{
  doingTypes: any;
  institution: App.Entities.Institution;
}>();

const selectedDocument = ref(null);
const currentInstitutionsTabPane = useStorage(
  "admin-CurrentInstitutionsTabPane",
  "Apie"
);

const updateDIsTabPane = (value) => {
  currentInstitutionsTabPane.value = value;
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

const breadcrumbOptions: App.Props.BreadcrumbOption[] = [
  {
    label: props.institution.name,
    icon: PeopleTeam24Filled,
  },
];
</script>
