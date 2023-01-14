<template>
  <NCard
    hoverable
    as="button"
    style="max-width: 400px"
    class="cursor-pointer shadow-sm"
    @click="Inertia.visit(route('institutions.show', institution.id))"
  >
    <template #cover>
      <img
        v-if="institution.image_url"
        class="h-32 object-cover"
        :src="institution.image_url"
      />
    </template>
    <template #header>
      <span :class="{ 'font-bold': isPadalinys }">{{ institution.name }}</span>
    </template>
    <template #header-extra>
      <div class="inline-flex gap-3">
        <NPopover>
          <template #trigger>
            <NButton text size="small" circle @click.stop>
              <template #icon>
                <UserAvatar :user="$page.props.auth.user" :size="24" />
              </template>
            </NButton>
          </template>
          <div class="flex flex-col gap-2">
            <NButton
              v-for="duty in institutionDuties"
              :key="duty.id"
              size="small"
              secondary
            >
              <template #icon>
                <UserAvatar :user="$page.props.auth.user" :size="16" />
              </template>
              {{ duty.name }}</NButton
            >
          </div>
        </NPopover>
        <!-- <NButton circle size="small" quaternary @click.stop
          ><template #icon
            ><NIcon :component="MoreHorizontal24Filled"></NIcon></template
        ></NButton> -->
      </div>
    </template>
    <InstitutionAvatarGroup
      v-if="institution.users"
      :users="institution.users"
    />

    <template #footer>
      <div class="flex justify-between gap-2">
        <NTag
          v-for="type in institution.types"
          :key="type.id"
          size="small"
          :bordered="false"
        >
          {{ type.title }}
        </NTag>
      </div>
    </template>
  </NCard>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { MoreHorizontal24Filled } from "@vicons/fluent";
import { NButton, NCard, NIcon, NPopover, NTag } from "naive-ui";
import { computed } from "vue";

import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const props = defineProps<{
  institution: App.Entities.Institution;
  isPadalinys?: boolean;
  showLastMeeting?: boolean;
  duties: App.Entities.Duty[];
}>();

const institutionDuties = computed(() => {
  return props.duties.filter((duty) => {
    return duty.institution_id === props.institution.id;
  });
});
</script>
