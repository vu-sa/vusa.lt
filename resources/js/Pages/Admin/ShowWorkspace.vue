<template>
  <AdminContentPage>
    <div
      class="m-2 grid h-[calc(100vh-8rem)] w-full grid-cols-[275px_1fr] gap-x-4"
    >
      <NScrollbar class="rounded-sm">
        <FadeTransition mode="out-in">
          <div v-if="institution" class="mx-4 flex flex-col gap-2">
            <NButton quaternary @click="handleBack"
              ><template #icon
                ><NIcon :component="ChevronLeft24Filled"></NIcon></template
              >Grįžti</NButton
            >
            <InstitutionCard
              size="small"
              class="outline outline-vusa-red"
              :institution="institution"
            ></InstitutionCard>
            <div class="my-2 flex flex-col">
              <h3>Susitikimai</h3>
              <MeetingCard
                v-for="meeting in institution.meetings"
                :key="meeting.id"
                class="focus:ring focus:ring-vusa-yellow"
                :meeting="meeting"
                @click="handleClick(meeting)"
              />
            </div>
          </div>
          <div v-else class="mx-4">
            <h3 class="font-black">Institucijos</h3>
            <div class="mb-4 flex flex-col gap-2">
              <InstitutionCard
                v-for="userInstitution in userInstitutions"
                :key="userInstitution.id"
                size="small"
                :institution="userInstitution"
                @click="
                  router.reload({
                    data: { institution_id: userInstitution.id },
                    only: ['institution'],
                  })
                "
              ></InstitutionCard>
            </div>
          </div>
        </FadeTransition>
      </NScrollbar>
      <div class="flex flex-col items-center justify-center">
        <p v-if="selectedChild">
          {{
            formatStaticTime(new Date(selectedChild?.start_time), {
              year: "numeric",
              month: "long",
              day: "numeric",
            })
          }}
          susitikimas
        </p>
        <CommentPart
          :commentable_type="'meeting'"
          :text="currentCommentText"
          class="m-auto"
          :model="meetingShown"
        ></CommentPart>
      </div>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { NButton, NIcon, NScrollbar } from "naive-ui";
import { computed, ref } from "vue";

import { ChevronLeft24Filled } from "@vicons/fluent";
import { formatStaticTime } from "@/Utils/IntlTime";
import { router } from "@inertiajs/vue3";
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import CommentPart from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import InstitutionCard from "@/Components/Cards/InstitutionCard.vue";
import MeetingCard from "@/Components/Cards/MeetingCard.vue";

const props = defineProps<{
  institution?: App.Entities.Institution;
  userInstitutions: App.Entities.Institution[];
}>();

const selectedChild = ref(null);

const handleClick = (model) => {
  selectedChild.value = model;
};

const handleBack = () => {
  router.reload({
    only: ["institution"],
    data: { institution_id: null },
    onSuccess: () => {
      selectedChild.value = null;
    },
  });
};

const currentCommentText = ref("");

const meetingShown = computed(() => {
  return props.institution?.meetings?.find((meeting) => {
    return meeting.id === selectedChild.value?.id;
  });
});

// const treeOptions = computed(() => {
//   return props.institution.meetings.map((institution) => {
//     return {
//       label: institution.created_at,
//       key: institution.id,
//     };
//   });
// });
</script>
