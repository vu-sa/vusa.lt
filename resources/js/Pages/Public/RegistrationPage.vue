<template>
  <article class="gap-y-4 pt-8 last:pb-2">
    <h1>
      {{ $t(form.name) }}
    </h1>
    <div class="typography">
      <div v-html="form.description" />
    </div>
    <div class="mt-8 max-w-prose text-base">
      <RegistrationForm :form @submit="handleSubmit" />
    </div>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { router } from "@inertiajs/vue3";
import RegistrationForm from "../../Features/Registrations/RegistrationForm.vue";

const { form } = defineProps<{
  form: unknown;
}>();

const handleSubmit = (data: Record<string, any>) => {
  router.post(route('registrations.store', { form: form.id }), { data });
};
</script>
