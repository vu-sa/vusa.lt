<template>
  <NCard hoverable :size="size" as="button" class="h-fit flex-1 cursor-pointer shadow-xs">
    <template #cover>
      <img v-if="institution.image_url" class="h-32 object-cover" :src="institution.image_url">
    </template>
    <template #header>
      <span :class="{ 'font-bold': isPadalinys }">{{ institution.name }}</span>
    </template>
    <template #header-extra>
      <slot name="header-extra">
        <div v-if="institutionDuties" class="ml-4 inline-flex gap-3">
          <NPopover>
            <template #trigger>
              <NButton text size="small" circle @click.stop>
                <template #icon>
                  <div>
                    <UserAvatar :user="$page.props.auth?.user" :size="24" />
                  </div>
                </template>
              </NButton>
            </template>
            <div class="flex flex-col gap-2">
              <Link v-for="duty in institutionDuties" :key="duty.id" :href="route('duties.show', duty.id)">
              <NButton size="small" secondary>
                <template #icon>
                  <UserAvatar :user="$page.props.auth?.user" :size="16" />
                </template>
                {{ duty.name }}
              </NButton>
              </Link>
            </div>
          </NPopover>
          <!-- <NButton circle size="small" quaternary @click.stop
            ><template #icon
              ><NIcon :component="MoreHorizontal24Filled"></NIcon></template
          ></NButton> -->
        </div>
      </slot>
    </template>
    <InstitutionAvatarGroup v-if="institution.users" :users="institution.users" :size="size === 'small' ? 32 : 40" />
    <slot />
    <template #footer>
      <div v-if="institution.types?.length > 0" class="flex justify-between gap-2">
        <NTag v-for="institutionType in institution.types" :key="institutionType.id" :size="size ? 'tiny' : 'small'"
          :bordered="false">
          {{ institutionType.title }}
        </NTag>
      </div>
    </template>
  </NCard>
</template>

<script setup lang="tsx">
import { Link } from "@inertiajs/vue3";
import { NButton, NCard, NPopover, NTag } from "naive-ui";
import { computed, ref } from "vue";

import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const props = defineProps<{
  institution: App.Entities.Institution;
  isPadalinys?: boolean;
  showLastMeeting?: boolean;
  size?: "small" | "medium" | "large";
  duties?: App.Entities.Duty[];
}>();

const institutionDuties = computed(() => {
  return props.duties?.filter((duty) => {
    return duty.institution_id === props.institution.id;
  });
});
</script>
