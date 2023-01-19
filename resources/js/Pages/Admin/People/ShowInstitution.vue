<template>
  <ShowPageLayout
    :current-tab="currentTab"
    :title="institution.name"
    :breadcrumb-options="breadcrumbOptions"
    :model="institution"
    :related-models="relatedModels"
    @change:tab="currentTab = $event"
  >
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
        @edit-click="router.visit(route('institutions.edit', institution.id))"
      />
    </template>
    <LastMeetingCard
      :last-meeting="institution?.lastMeeting"
      :institution="institution"
      :doing-types="doingTypes"
      content-style="margin-top: 0.5em"
    ></LastMeetingCard>
    <template #below>
      <MattersCardGrid
        v-if="currentTab == 'Svarstomi klausimai'"
        :institution="institution"
        :matters="institution.matters"
      ></MattersCardGrid>
      <RelatedInstitutions
        v-else-if="currentTab === 'Susijusios institucijos'"
        :institution="institution"
      ></RelatedInstitutions>
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { NDivider } from "naive-ui";
import { router } from "@inertiajs/vue3";

// import { documentTemplate } from "@/Types/formOptions";
import { computed, ref, watch } from "vue";
import { useStorage } from "@vueuse/core";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Icons from "@/Types/Icons/filled";
import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import LastMeetingCard from "@/Components/Cards/QuickContentCards/LastMeetingCard.vue";
import MattersCardGrid from "@/Components/TabPaneContent/MattersCardGrid.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import RelatedInstitutions from "@/Components/Carousels/RelatedInstitutions.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  doingTypes: any;
  institution: App.Entities.Institution;
}>();

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: props.institution.name,
    icon: Icons.INSTITUTION,
  },
];

const currentTab = useStorage("show-institution-tab", "Svarstomi klausimai");

const relatedInstitutionCount = computed(() => {
  // check all arrays of related institutions length and sum
  return Object.values(props.institution.relatedInstitutions).reduce(
    (acc: number, curr) => acc + curr.length,
    0
  );
});

const relatedModels = [
  {
    name: "Svarstomi klausimai",
    icon: Icons.MATTER,
    count: props.institution.matters?.length,
  },
  {
    name: "Susijusios institucijos",
    icon: Icons.INSTITUTION,
    count: relatedInstitutionCount.value,
  },
];
</script>
