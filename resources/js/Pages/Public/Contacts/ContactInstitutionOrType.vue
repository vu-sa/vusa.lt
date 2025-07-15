<template>
  <InstitutionContacts
    class="mt-4"
    :contacts="contacts"
    :contact-sections="contactSections"
    :has-mixed-grouping="hasMixedGrouping"
    :institution="institution"
  />
</template>

<script setup lang="ts">
import InstitutionContacts from "@/Components/Public/InstitutionContacts.vue";

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

defineProps<{
  contacts?: Array<App.Entities.User>;
  contactSections?: Array<ContactSection>;
  hasMixedGrouping?: boolean;
  institution: App.Entities.Institution;
}>();
</script>
