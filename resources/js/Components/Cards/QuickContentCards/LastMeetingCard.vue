<template>
  <QuickContentCard>
    <div class="mt-2 inline-flex items-center gap-2">
      <NIcon :size="45" :component="CalendarClock20Regular"></NIcon>

      <span class="text-5xl font-bold">
        <NNumberAnimation
          :from="0"
          :to="getDaysDifference(lastMeetingDoing.date)"
        ></NNumberAnimation>
      </span>
    </div>
    <p class="mt-4">
      Paskutinis posėdis vyko
      <span class="font-bold">{{ getMMMMDD(lastMeetingDoing.date) }}</span>
    </p>
    <template #action-button>
      <NewMeetingButton
        :duty-institution="dutyInstitution"
        :doing-types="doingTypes"
      />
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import {
  CalendarClock20Regular,
  CalendarClock24Filled,
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
  NNumberAnimation,
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
import LastMeetingCard from "@/Components/Cards/QuickContentCards/LastMeetingCard.vue";
import MeetingDocumentButton from "@/Components/Buttons/QActButtons/MeetingDocumentButton.vue";
import ModelsDocumentViewer from "@/Components/SharepointFileManager/ModelsDocumentViewer.vue";
import NewMeetingButton from "@/Components/Buttons/NewMeetingButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import QuestionsTabPane from "@/Components/TabPaneContent/QuestionsTabPane.vue";
import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";
import getRelativeTime, {
  getDaysDifference,
  getMMMMDD,
} from "@/Composables/getRelativeTime";

defineProps<{
  doingTypes: any;
  dutyInstitution: App.Models.DutyInstitution;
  lastMeetingDoing: App.Models.Doing;
}>();
</script>
