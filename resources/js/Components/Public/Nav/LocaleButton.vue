<template>
  <DropdownMenu @update:open="isOpen = $event">
    <DropdownMenuTrigger as-child>
      <Button 
        variant="ghost" 
        :size="props.size || 'sm'" 
        class="gap-2 tracking-normal"
      >
        <img 
          alt="Change language" 
          :src="`https://hatscripts.github.io/circle-flags/flags/${locale === 'lt' ? 'lt' : 'gb'}.svg`" 
          width="16" 
          class="rounded-full"
        >
        <span class="text-zinc-700 dark:text-zinc-300 tracking-wide font-medium">
          {{ locale === "lt" ? "LT" : "EN" }}
        </span>
        <ChevronDown class="h-4 w-4 opacity-50 transition-transform duration-200" 
          :class="{ 'rotate-180': isOpen }" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-48">
      <DropdownMenuItem 
        as-child 
        :disabled="!$page.props.otherLangURL"
        class="cursor-pointer"
      >
        <SmartLink target="_self" :href="$page.props.otherLangURL" class="flex items-center gap-2">
          <img 
            :src="`https://hatscripts.github.io/circle-flags/flags/${locale === 'lt' ? 'gb' : 'lt'}.svg`" 
            width="16" 
            class="rounded-full"
          />
          {{ locale === LocaleEnum.LT ? "Change page language" : "Pakeisti puslapio kalbą" }}
        </SmartLink>
      </DropdownMenuItem>
      <DropdownMenuItem as-child class="cursor-pointer">
        <SmartLink :href="`/${otherLocale}`" class="flex items-center gap-2">
          <img 
            :src="`https://hatscripts.github.io/circle-flags/flags/${locale === 'lt' ? 'gb' : 'lt'}.svg`" 
            width="16" 
            class="rounded-full"
          />
          {{ locale === LocaleEnum.LT ? "Go to main page" : "Eiti į pagrindinį" }}
        </SmartLink>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="tsx">
import { computed, ref, watch } from "vue";
import { loadLanguageAsync } from "laravel-vue-i18n";
import { usePage } from "@inertiajs/vue3";
import { ChevronDown } from "lucide-vue-next";

import { LocaleEnum } from "@/Types/enums";
import SmartLink from "@/Components/Public/SmartLink.vue";
import { Button } from "@/Components/ui/button";
import type { ButtonVariants } from "@/Components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

interface Props {
  /** Current locale for the application */
  locale: LocaleEnum;
  /** Button size variant - affects padding and height */
  size?: ButtonVariants['size'];
}

const props = defineProps<Props>();

const isOpen = ref(false);

const otherLocale = computed(() => {
  return props.locale === "lt" ? "en" : "lt";
});

watch(
  () => props.locale,
  () => {
    loadLanguageAsync(props.locale);
  },
);
</script>
