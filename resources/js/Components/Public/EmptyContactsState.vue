<template>
  <div class="flex flex-col items-center justify-center rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 p-8 ring-1 ring-zinc-200/50 dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50">
    <div class="rounded-full bg-zinc-200/50 p-4 dark:bg-zinc-700/50">
      <UsersIcon class="size-8 text-zinc-400 dark:text-zinc-500" />
    </div>
    
    <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
      {{ $t('Šiuo metu atstovų nėra') }}
    </h3>
    
    <p class="mt-2 max-w-sm text-center text-sm text-zinc-600 dark:text-zinc-400">
      {{ $t('Šioje institucijoje šiuo metu nėra aktyvių studentų atstovų.') }}
    </p>
    
    <!-- Registration CTA -->
    <SmartLink 
      v-if="studentRepFormInfo" 
      :href="registrationUrl"
      class="mt-6"
    >
      <Button variant="default" class="gap-2">
        <UserPlusIcon class="size-4" />
        {{ $t('Tapk studentų(-čių) atstovu(-e)!') }}
      </Button>
    </SmartLink>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { UsersIcon, UserPlusIcon } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import SmartLink from "@/Components/Public/SmartLink.vue";

interface StudentRepFormInfo {
  formPath: string;
  institutionId: string;
  institutionName: string;
}

const props = defineProps<{
  studentRepFormInfo?: StudentRepFormInfo | null;
  institutionName?: string;
}>();

const registrationUrl = computed(() => {
  if (!props.studentRepFormInfo) return '';
  return `${props.studentRepFormInfo.formPath}?institution=${props.studentRepFormInfo.institutionId}`;
});
</script>
