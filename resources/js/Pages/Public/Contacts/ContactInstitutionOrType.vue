<template>
  <InstitutionContacts class="mt-4" :contacts :institution />
</template>

<script setup lang="ts">
import InstitutionContacts from "@/Components/Public/InstitutionContacts.vue";

defineProps<{
  contacts: Array<App.Entities.User>;
  institution: App.Entities.Institution;
}>();
</script>
