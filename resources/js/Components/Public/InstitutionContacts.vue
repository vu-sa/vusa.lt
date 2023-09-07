<template>
  <div class="gap-12 md:grid md:grid-cols-[5fr,_3fr]">
    <div class="h-fit md:sticky md:top-32">
      <InstitutionFigure
        only-vertical
        :is-mobile="isMobile"
        :institution="institution"
      ></InstitutionFigure>
    </div>
    <section class="flex flex-col gap-6">
      <ContactCard
        v-for="contact in contacts"
        :key="contact.id"
        :contact="contact"
        :duties="contact.duties"
      >
      </ContactCard>
    </section>
  </div>
</template>

<script setup lang="ts">
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";

import ContactCard from "@/Components/Public/ContactWithPhoto.vue";
import InstitutionFigure from "./InstitutionFigure.vue";

defineProps<{
  contacts: Array<App.Entities.User>;
  institution: App.Entities.Institution;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smallerOrEqual("md");
</script>
