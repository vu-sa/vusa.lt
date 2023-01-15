<template>
  <ShowPageLayout :breadcrumb-options="breadcrumbOptions" :model="institution">
    <template #title>
      <span class="text-3xl">{{ institution.name }}</span>
    </template>
    <template #after-heading>
      <InstitutionAvatarGroup :users="institution.users" />
      <template v-if="institution.institutionManagers.length > 0">
        <NDivider v-if="institution.institutionManagers.length > 0" vertical />
        <span class="text-xs text-zinc-500">Admin:</span>
        <InstitutionAvatarGroup :users="institution.institutionManagers" />
      </template>
    </template>
    <template #more-options>
      <MoreOptionsButton
        edit
        @edit-click="Inertia.visit(route('institutions.edit', institution.id))"
      />
    </template>
    <div class="mb-16 flex min-h-[16em] gap-4 py-2">
      <LastMeetingCard
        :last-meeting="institution?.lastMeeting"
        :institution="institution"
        :doing-types="doingTypes"
        content-style="margin-top: 0.5em"
      ></LastMeetingCard>
    </div>
    <div>
      <h3>Svarstomi institucijos klausimai</h3>
      <MattersCardGrid
        :institution="institution"
        :matters="institution.matters"
      ></MattersCardGrid>
    </div>
    <div class="mt-2">
      <h3>Susijusios institucijos</h3>
      <RelatedModelCarousel :institution="institution"></RelatedModelCarousel>
    </div>
  </ShowPageLayout>
  <!-- <FileSelectDrawer
    :document="selectedDocument"
    @close-drawer="selectedDocument = documentTemplate"
  ></FileSelectDrawer> -->
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { NDivider } from "naive-ui";
// import { ref } from "vue";

// import { documentTemplate } from "@/Types/formOptions";
// import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import Icons from "@/Types/Icons/filled";
import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import LastMeetingCard from "@/Components/Cards/QuickContentCards/LastMeetingCard.vue";
import MattersCardGrid from "@/Components/TabPaneContent/MattersCardGrid.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import RelatedModelCarousel from "@/Components/Carousels/RelatedModelCarousel.vue";
import ShowPageLayout from "@/Components/Layouts/ShowPageLayout.vue";
import type { BreadcrumbOption } from "@/Components/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  doingTypes: any;
  institution: App.Entities.Institution;
}>();

// const selectedDocument = ref(null);

// const updateSelectedDocument = (document) => {
//   selectedDocument.value = document;
// };

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: props.institution.name,
    icon: Icons.INSTITUTION,
  },
];
</script>
