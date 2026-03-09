<template>
  <figure
    class="group relative flex flex-col overflow-hidden rounded-xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 ring-1 ring-zinc-200/50 transition-all duration-300 hover:ring-zinc-300 hover:shadow-lg dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600 sm:rounded-2xl"
  >
    <!-- Photo section -->
    <div v-if="imageUrl" class="relative aspect-[4/3] w-full overflow-hidden">
      <img 
        :src="imageUrl" 
        :alt="contact?.name"
        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
        loading="lazy" 
        style="object-position: 50% 20%"
      >
      <!-- Subtle gradient overlay at bottom for text readability -->
      <div class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-black/20 to-transparent" />
    </div>

    <!-- Avatar fallback when no photo -->
    <div v-else class="flex aspect-[4/3] w-full items-center justify-center bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800">
      <span class="text-2xl font-bold text-zinc-400 dark:text-zinc-500 sm:text-3xl">
        {{ getInitials(contact.name) }}
      </span>
    </div>
    
    <!-- Content section -->
    <div class="flex flex-1 flex-col justify-between gap-2 p-3 sm:gap-3 sm:p-4">
      <div>
        <!-- Name -->
        <h3 class="text-sm font-bold leading-tight text-zinc-900 dark:text-zinc-50 sm:text-base">
          {{ contact.name }}
          <span v-if="contact.show_pronouns" class="text-[10px] font-normal text-zinc-400 sm:text-xs">
            ({{ contact.pronouns }})
          </span>
        </h3>
        
        <!-- Duties -->
        <div v-if="duties" class="mt-1.5 space-y-0.5 sm:mt-2 sm:space-y-1">
          <p 
            v-for="duty in duties" 
            :key="duty.id"
            class="text-[11px] leading-relaxed text-zinc-600 dark:text-zinc-400 sm:text-xs"
          >
            {{ changeDutyNameEndings(contact, duty.name, $page.props.app.locale, contact.pronouns, duty.pivot?.use_original_duty_name) }}
            <span v-if="showAdditionalInfo(duty)" class="text-zinc-400 dark:text-zinc-500">
              {{ showAdditionalInfo(duty) }}
            </span>
            <InfoPopover 
              v-if="duty.description && duty.description !== '<p></p>'" 
              style="max-width: 400px" 
              trigger="hover" 
              color="gray"
              class="ml-0.5 inline align-middle"
            >
              <span v-html="dutyDescription(duty)" />
            </InfoPopover>
          </p>
        </div>
      </div>
      
      <!-- Action buttons -->
      <div class="flex items-center gap-1 pt-0.5 sm:gap-1.5 sm:pt-1">
        <TooltipProvider v-if="contact.phone">
          <Tooltip>
            <TooltipTrigger as-child>
              <a :href="`tel:${contact.phone}`">
                <Button variant="ghost" size="icon-sm" class="size-7 rounded-full text-zinc-500 hover:bg-zinc-200/70 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700/70 dark:hover:text-zinc-200 sm:size-8">
                  <IFluentPhone20Regular class="size-3.5 sm:size-4" />
                </Button>
              </a>
            </TooltipTrigger>
            <TooltipContent side="bottom" class="px-3 py-1.5">
              <span class="text-xs">{{ contact.phone }}</span>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
        
        <a v-if="contact.facebook_url" :href="contact.facebook_url" target="_blank" rel="noopener noreferrer">
          <Button variant="ghost" size="icon-sm" class="size-7 rounded-full text-zinc-500 hover:bg-blue-50 hover:text-blue-600 dark:text-zinc-400 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 sm:size-8">
            <IMdiFacebook class="size-3.5 sm:size-4" />
          </Button>
        </a>
        
        <Popover v-if="shownContactEmail.length > 1">
          <PopoverTrigger as-child>
            <Button variant="ghost" size="icon-sm" class="size-7 rounded-full text-zinc-500 hover:bg-zinc-200/70 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700/70 dark:hover:text-zinc-200 sm:size-8">
              <IFluentMail20Regular class="size-3.5 sm:size-4" />
            </Button>
          </PopoverTrigger>
          <PopoverContent align="start" class="w-auto max-w-xs p-3">
            <div class="flex flex-col gap-2 text-sm">
              <template v-for="(email, index) in shownContactEmail" :key="email.email">
                <div>
                  <span class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ email.name }}</span>
                  <a 
                    :href="`mailto:${email.email}`" 
                    class="text-sm font-medium text-zinc-900 hover:text-vusa-red dark:text-zinc-100"
                  >
                    {{ email.email }}
                  </a>
                </div>
                <div v-if="index < shownContactEmail.length - 1" class="h-px bg-zinc-200 dark:bg-zinc-700" />
              </template>
            </div>
          </PopoverContent>
        </Popover>
        
        <TooltipProvider v-else-if="shownContactEmail.length === 1">
          <Tooltip>
            <TooltipTrigger as-child>
              <a :href="`mailto:${shownContactEmail[0].email}`">
                <Button variant="ghost" size="icon-sm" class="size-7 rounded-full text-zinc-500 hover:bg-zinc-200/70 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700/70 dark:hover:text-zinc-200 sm:size-8">
                  <IFluentMail20Regular class="size-3.5 sm:size-4" />
                </Button>
              </a>
            </TooltipTrigger>
            <TooltipContent side="bottom" class="px-3 py-1.5">
              <span class="text-xs">{{ shownContactEmail[0].email }}</span>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>
    </div>
  </figure>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { changeDutyNameEndings } from "@/Utils/String";
import InfoPopover from "../Buttons/InfoPopover.vue";

const props = defineProps<{
  contact: App.Entities.User;
  duties: App.Entities.Duty[];
}>();

const dutyDescription = (duty) => {
  const locale = usePage().props.app.locale;

  if (locale === "en") {
    return (
      duty.pivot?.description ??
      duty.description
    );
  }

  return duty.pivot?.description ?? duty.description;
};

// Some users have multiple duties, so we need to show all of their emails AND duty name
// alongside the email
const shownContactEmail = computed(() => {
  return props.duties.reduce((acc, duty) => {
    acc.push({ name: duty.name, email: duty.pivot?.additional_email ?? duty.email ?? props.contact.email });
    return acc;
  }, []);
});

const showAdditionalInfo = (duty) => {
  if (!duty.pivot?.study_program) {
    return null;
  }

  return `(${duty.pivot.study_program?.name})`;
};

// NOTE: Nusprendžia, kurią nuotrauką imti, pagal tai, ar url turi "kuratoriai"
const imageUrl = computed(() => {
  // check all duties for duties name which includes kuratorius
  // iterate object simply because it may not be iterable

  for (const duty of Object.keys(props.contact.duties)) {

    // check if props.contact.duties?.[duty] has pivot
    if (!props.contact.duties?.[duty].pivot) {
      continue;
    }

    return (
      props.contact.duties?.[duty].pivot.additional_photo ?? props.contact.profile_photo_path
    );
  }

  if (props.contact.pivot?.additional_photo) {
    return props.contact.pivot?.additional_photo;
  }

  return props.contact.profile_photo_path ?? "";
});

// Get initials from name for avatar fallback
const getInitials = (name: string): string => {
  const parts = name.split(' ').filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return parts[0]?.substring(0, 2).toUpperCase() ?? '?';
};

</script>
