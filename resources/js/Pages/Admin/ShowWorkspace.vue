<template>
  <div
    class="m-2 grid h-[calc(100vh-8rem)] w-full max-w-7xl grid-cols-[16rem_1fr] gap-x-4"
  >
    <NScrollbar class="rounded-sm">
      <template v-if="institution">
        <h2>{{ institution.name }}</h2>
        <!-- <NTree :data="treeOptions"> </NTree> -->
        <div>
          <NButton
            v-for="meeting in institution.meetings"
            :key="meeting.id"
            @click="handleClick(meeting.id)"
            >{{ meeting.id }}:{{ meeting.comments.length }}</NButton
          >
        </div>
      </template>
      <template v-else>
        <p>Pasirink instituciją</p>
        <Link
          v-for="userInstitution in user.institutions"
          :key="userInstitution.id"
          :href="route('workspace', userInstitution.id)"
          ><NButton>{{ userInstitution.name }}</NButton></Link
        >
      </template>
    </NScrollbar>
    <CommentPart
      :text="currentCommentText"
      :meeting-with-comments="meetingShown"
    ></CommentPart>
  </div>
</template>

<script setup lang="ts">
import { NButton, NScrollbar, NTree } from "naive-ui";
import { computed, ref } from "vue";

import { Link } from "@inertiajs/inertia-vue3";
import CommentPart from "@/Features/Admin/Workspace/CommentPart.vue";

const props = defineProps<{
  institution?: App.Entities.Institution;
  user: App.Entities.User;
}>();

const selectedChild = ref(null);

const handleClick = (id: string) => {
  console.log(id);
  selectedChild.value = id;
};

const currentCommentText = ref("");

const meetingShown = computed(() => {
  return props.institution?.meetings?.find((meeting) => {
    return meeting.id === selectedChild.value;
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
