<template>
  <div
    class="border border-border bg-card p-8 sm:p-12 text-center"
    data-slot="empty-contacts-state"
  >
    <div class="mx-auto flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground">
      <IFluentPeople24Regular class="size-6" />
    </div>

    <h3 class="mt-4 text-base sm:text-lg font-bold text-foreground">
      {{ $t('Šiuo metu kontaktų nėra') }}
    </h3>

    <p class="mt-2 max-w-sm mx-auto text-sm text-muted-foreground">
      {{ $t('Ši institucija šiuo metu neturi viešai skelbiamų kontaktų.') }}
    </p>

    <!-- Registration CTA -->
    <div v-if="studentRepFormInfo" class="mt-6">
      <SmartLink
        :href="registrationUrl"
        :class="[
          'inline-flex h-10 items-center justify-center gap-2 border border-border bg-background px-4',
          'text-xs font-bold uppercase tracking-wider text-foreground hover:border-brand hover:text-brand transition-colors',
        ]"
      >
        <IFluentPersonAdd20Regular class="size-4 text-brand" />
        <span>{{ $t('Tapk studentų(-čių) atstovu(-e)!') }}</span>
      </SmartLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '@/Components/Public/SmartLink.vue';
import IFluentPeople24Regular from '~icons/fluent/people-24-regular';
import IFluentPersonAdd20Regular from '~icons/fluent/person-add-20-regular';

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
