<template>
  <div class="mx-auto max-w-7xl px-12 pt-10">
    <InstitutionContacts :contacts="contacts" :institution="institution" />
  </div>
</template>

<script setup lang="ts">
import InstitutionContacts from "@/Components/Public/InstitutionContacts.vue";

defineProps<{
  contacts: Array<App.Entities.User>;
  institution: App.Entities.Institution;
}>();
</script>
