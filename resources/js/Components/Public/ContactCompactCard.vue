<template>
  <div
    class="group flex items-center gap-3 rounded-xl bg-gradient-to-br from-zinc-50/80 to-white p-2.5 ring-1 ring-zinc-200/50 transition-all duration-200 hover:ring-zinc-300 dark:from-zinc-800/50 dark:to-zinc-900/50 dark:ring-zinc-700/50 dark:hover:ring-zinc-600"
  >
    <!-- Avatar -->
    <div class="shrink-0">
      <img
        v-if="imageUrl"
        :src="imageUrl"
        :alt="contact.name"
        class="size-10 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-600"
        style="object-position: 50% 20%"
        loading="lazy"
      >
      <div
        v-else
        class="size-10 rounded-full flex items-center justify-center bg-zinc-200 dark:bg-zinc-700"
      >
        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ initials }}</span>
      </div>
    </div>

    <!-- Info -->
    <div class="min-w-0 flex-1">
      <p class="text-sm font-medium text-zinc-900 dark:text-zinc-50 truncate leading-tight">
        {{ contact.name }}
        <span v-if="contact.show_pronouns" class="text-[10px] font-normal text-zinc-400">
          ({{ contact.pronouns }})
        </span>
      </p>
      <p v-if="dutyName" class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 truncate">
        {{ dutyName }}
      </p>
      <p v-if="studyProgram" class="text-[10px] text-zinc-400 dark:text-zinc-500 truncate">
        {{ studyProgram }}
      </p>
    </div>

    <!-- Contact actions -->
    <div class="flex shrink-0">
      <TooltipProvider v-if="contact.email || dutyEmail">
        <Tooltip>
          <TooltipTrigger as-child>
            <a 
              :href="`mailto:${dutyEmail || contact.email}`"
              class="flex size-8 items-center justify-center rounded-full text-zinc-400 transition-colors hover:text-vusa-red dark:text-zinc-500 dark:hover:text-vusa-red"
            >
              <MailIcon class="size-4" />
            </a>
          </TooltipTrigger>
          <TooltipContent>{{ dutyEmail || contact.email }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { MailIcon } from "lucide-vue-next";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { changeDutyNameEndings } from "@/Utils/String";
import { usePage } from "@inertiajs/vue3";

const props = defineProps<{
  contact: App.Entities.User;
  duty?: App.Entities.Duty;
}>();

const $page = usePage();

const initials = computed(() => {
  const parts = props.contact.name.split(' ').filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return parts[0]?.substring(0, 2).toUpperCase() ?? '?';
});

const imageUrl = computed(() => {
  // Check for additional photo from pivot
  if (props.duty?.pivot?.additional_photo) {
    return props.duty.pivot.additional_photo;
  }
  if (props.contact.pivot?.additional_photo) {
    return props.contact.pivot.additional_photo;
  }
  return props.contact.profile_photo_path ?? null;
});

const dutyName = computed(() => {
  if (!props.duty) return null;
  return changeDutyNameEndings(
    props.contact,
    props.duty.name,
    $page.props.app.locale,
    props.contact.pronouns,
    props.duty.pivot?.use_original_duty_name
  );
});

const studyProgram = computed(() => {
  return props.duty?.pivot?.study_program?.name ?? null;
});

const dutyEmail = computed(() => {
  return props.duty?.pivot?.additional_email ?? props.duty?.email ?? null;
});
</script>
