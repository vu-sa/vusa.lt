<template>
  <ResponsibilityForm remember-key="CreateResponsibility" :responsibility="{}" @submit:form="form => form.post(route('responsibilities.store'))" />
</template>

<script setup lang="ts">
import ResponsibilityForm from '@/Components/AdminForms/ResponsibilityForm.vue';
</script>
