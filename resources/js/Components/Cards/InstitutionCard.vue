<template>
  <Card as="button" class="h-fit flex-1 cursor-pointer shadow-xs hover:shadow-sm transition-shadow">
    <template v-if="institution.image_url">
      <img class="h-32 w-full rounded-t-lg object-cover" :src="institution.image_url">
    </template>
    <CardHeader class="flex-row items-center justify-between gap-2 space-y-0">
      <CardTitle :class="['text-base', { 'font-bold': isPadalinys }]">{{ institution.name }}</CardTitle>
      <slot name="header-extra">
        <div v-if="institutionDuties" class="ml-4 inline-flex gap-3">
          <Popover>
            <PopoverTrigger as-child>
              <Button variant="ghost" size="icon-sm" class="rounded-full" @click.stop>
                <UserAvatar :user="$page.props.auth?.user" :size="24" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto">
              <div class="flex flex-col gap-2">
                <Link v-for="duty in institutionDuties" :key="duty.id" :href="route('duties.show', duty.id)">
                  <Button size="sm" variant="secondary">
                    <UserAvatar :user="$page.props.auth?.user" :size="16" />
                    {{ duty.name }}
                  </Button>
                </Link>
              </div>
            </PopoverContent>
          </Popover>
        </div>
      </slot>
    </CardHeader>
    <CardContent>
      <InstitutionAvatarGroup v-if="institution.users" :users="institution.users" :size="size === 'small' ? 32 : 40" />
      <slot />
    </CardContent>
    <CardFooter v-if="institution.types?.length > 0">
      <div class="flex flex-wrap gap-2">
        <Badge v-for="institutionType in institution.types" :key="institutionType.id" variant="secondary">
          {{ institutionType.title }}
        </Badge>
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
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
