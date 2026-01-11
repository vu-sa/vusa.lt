<template>
  <!-- Empty state when no contacts -->
  <div v-if="!hasAnyContacts">
    <div v-if="institution.contacts_layout === 'below'" class="flex flex-col gap-8">
      <InstitutionFigure only-vertical :institution />
      <EmptyContactsState :student-rep-form-info="studentRepFormInfo" :institution-name="String(institution.name)" />
    </div>
    <div v-else class="gap-12 md:grid md:grid-cols-[auto__250px] xl:grid-cols-[5fr__3fr]">
      <div class="h-fit md:sticky md:top-36">
        <InstitutionFigure only-vertical :institution />
      </div>
      <h2 v-if="isMobile" class="mb-4 mt-0">
        {{ $t('Kontaktai') }}
      </h2>
      <EmptyContactsState :student-rep-form-info="studentRepFormInfo" :institution-name="String(institution.name)" />
    </div>
  </div>

  <!-- Mixed contact sections (some grouped, some flat) -->
  <div v-else-if="hasMixedGrouping">
    <div v-if="institution.contacts_layout === 'below'" class="flex flex-col gap-8">
      <InstitutionFigure only-vertical :institution />
      <div v-for="section in contactSections" :key="section.dutyName" class="mb-8">
        <h3 class="mb-4 text-lg font-semibold">{{ section.dutyName }}</h3>
        <div v-if="section.type === 'grouped_duty'" class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="group in section.groups" :key="group.name" class="mb-4">
            <h4 class="mb-3 text-md font-medium">{{ group.name }}</h4>
            <div class="space-y-3">
              <ContactCard v-for="contact in group.contacts" :key="contact.id" :contact="contact" :duties="contact.duties || []" />
            </div>
          </div>
        </div>
        <div v-else class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
          <ContactCard v-for="contact in section.contacts" :key="contact.id" :contact="contact" :duties="contact.duties || []" />
        </div>
      </div>
    </div>
    <div v-else class="gap-12 md:grid md:grid-cols-[auto__250px] xl:grid-cols-[5fr__3fr]">
      <div class="h-fit md:sticky md:top-36">
        <InstitutionFigure only-vertical :institution />
      </div>
      <div class="flex flex-col gap-6">
        <h2 v-if="isMobile" class="mb-4 mt-0">
          {{ $t('Kontaktai') }}
        </h2>
        <div v-for="section in contactSections" :key="section.dutyName" class="mb-6">
          <h3 class="mb-3 text-lg font-semibold">{{ section.dutyName }}</h3>
          <div v-if="section.type === 'grouped_duty'" class="space-y-4">
            <div v-for="group in section.groups" :key="group.name" class="space-y-3">
              <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ group.name }}</h4>
              <div class="space-y-2">
                <ContactCard v-for="contact in group.contacts" :key="contact.id" :contact="contact" :duties="contact.duties || []" />
              </div>
            </div>
          </div>
          <div v-else class="flex flex-col gap-4 max-md:grid max-md:grid-cols-2">
            <ContactCard v-for="contact in section.contacts" :key="contact.id" :contact="contact" :duties="contact.duties || []" />
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Non-grouped contacts (existing behavior) -->
  <div v-else>
    <div v-if="institution.contacts_layout === 'below'" class="flex flex-col gap-8">
      <InstitutionFigure only-vertical :institution />
      <section>
        <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
          <ContactCard v-for="contact in contacts" :key="contact.id" :contact="contact" :duties="contact.duties || []" />
        </div>
      </section>
    </div>
    <div v-else class="gap-12 md:grid md:grid-cols-[auto__250px] xl:grid-cols-[5fr__3fr]">
      <div class="h-fit md:sticky md:top-36">
        <InstitutionFigure only-vertical :institution />
      </div>
      <h2 v-if="isMobile" class="mb-4 mt-0">
        {{ $t('Kontaktai') }}
      </h2>
      <section class="flex flex-col gap-4 max-md:grid max-md:grid-cols-2">
        <ContactCard v-for="contact in contacts" :key="contact.id" :contact="contact" :duties="contact.duties || []" />
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";

import ContactCard from "@/Components/Public/ContactWithPhoto.vue";
import EmptyContactsState from "./EmptyContactsState.vue";
import InstitutionFigure from "./InstitutionFigure.vue";

interface ContactGroup {
  name: string;
  contacts: Array<App.Entities.User>;
}

interface ContactSection {
  type: 'grouped_duty' | 'flat_duty';
  dutyName: string;
  groups?: Array<ContactGroup>;
  contacts?: Array<App.Entities.User>;
}

interface StudentRepFormInfo {
  formPath: string;
  institutionId: string;
  institutionName: string;
}

const props = defineProps<{
  contacts?: Array<App.Entities.User>;
  contactSections?: Array<ContactSection>;
  hasMixedGrouping?: boolean;
  institution: App.Entities.Institution;
  studentRepFormInfo?: StudentRepFormInfo | null;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smallerOrEqual("md");

const hasAnyContacts = computed(() => {
  if (props.hasMixedGrouping) {
    // Check if any contact section has contacts
    return (props.contactSections?.length ?? 0) > 0 && props.contactSections?.some(section => {
      if (section.type === 'grouped_duty') {
        return section.groups?.some(group => (group.contacts?.length ?? 0) > 0);
      }
      return (section.contacts?.length ?? 0) > 0;
    });
  }
  return (props.contacts?.length ?? 0) > 0;
});
</script>
