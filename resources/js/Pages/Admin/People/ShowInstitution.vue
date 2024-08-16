<template>
  <ShowPageLayout :current-tab="currentTab" :title="institution.name" :breadcrumb-options="breadcrumbOptions"
    :model="institution" :related-models="relatedModels" @change:tab="currentTab = $event">
    <template #title>
      <span class="text-3xl">{{ institution.name }}</span>
    </template>
    <template #after-heading>
      <InstitutionAvatarGroup :max="5" :users="institution.users" />
      <template v-if="institution.managers.length > 0">
        <NDivider v-if="institution.managers.length > 0" vertical />
        <span class="text-xs text-zinc-500">Admin:</span>
        <InstitutionAvatarGroup :users="institution.managers" />
      </template>
    </template>
    <template #more-options>
      <MoreOptionsButton edit @edit-click="router.visit(route('institutions.edit', institution.id))" />
    </template>
    <LastMeetingCard :last-meeting="institution?.lastMeeting" :institution="institution" :doing-types="doingTypes"
      content-style="margin-top: 0.5em" />
    <template #below>
      <MeetingsTabPane v-if="currentTab === 'Susitikimai'" :institution="institution"
        :meetings="institution.meetings" />
      <div v-else-if="currentTab === 'Failai'">
        <Suspense v-if="institution.types.length > 0">
          <SimpleFileViewer :fileable="{ id: institution.id, type: 'Institution' }" />
          <template #fallback>
            <div class="flex h-24 items-center justify-center">
              Kraunami susiję failai...
            </div>
          </template>
        </Suspense>
        <FileManager :starting-path="institution.sharepointPath" :fileable="{ ...institution, type: 'Institution' }" />
      </div>
      <MattersCardGrid v-else-if="currentTab === 'Svarstomi klausimai'" :institution="institution"
        :matters="institution.matters" />
      <RelatedInstitutions v-else-if="currentTab === 'Susijusios institucijos'" :institution="institution" />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { NDivider } from "naive-ui";
import { computed, defineAsyncComponent } from "vue";
import { router } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

// import { documentTemplate } from "@/Types/formOptions";
import Icons from "@/Types/Icons/filled";
import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import LastMeetingCard from "@/Components/Cards/QuickContentCards/LastMeetingCard.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import SimpleFileViewer from "@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  doingTypes: any;
  institution: App.Entities.Institution;
}>();

const currentTab = useStorage("show-institution-tab", "Svarstomi klausimai");

const MeetingsTabPane = defineAsyncComponent(
  () => import("@/Components/TabPaneContent/MeetingsTabPane.vue")
);

const FileManager = defineAsyncComponent(
  () => import("@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue")
);

const MattersCardGrid = defineAsyncComponent(
  () => import("@/Components/TabPaneContent/MattersCardGrid.vue")
);

const RelatedInstitutions = defineAsyncComponent(
  () => import("@/Components/Carousels/RelatedInstitutions.vue")
);

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: props.institution.name,
    icon: Icons.INSTITUTION,
  },
];

const relatedInstitutionCount = computed(() => {
  // reduce props.institution.relatedInstitutions object by checking all arrays lengths
  return Object.values(props.institution.relatedInstitutions).reduce(
    (acc, val) => acc + val.length,
    0
  );
});

const relatedModels = [
  {
    name: "Susitikimai",
    icon: Icons.MEETING,
    count: props.institution.meetings?.length,
  },
  {
    name: "Failai",
    icon: Icons.SHAREPOINT_FILE,
    count: props.institution.files?.length,
  },
  //{
  //  name: "Svarstomi klausimai",
  //  icon: Icons.MATTER,
  //  count: props.institution.matters?.length,
  //},
  {
    name: "Susijusios institucijos",
    icon: Icons.INSTITUTION,
    disabled: relatedInstitutionCount.value === 0,
  },
];
</script>
