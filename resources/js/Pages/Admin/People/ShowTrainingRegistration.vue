<template>
  <AdminContentPage :title="`Registracija: ${training.name}`">
    <div class="mt-8 max-w-prose text-base">
      <RegistrationForm :form="training.form" @submit="handleSubmit" />
    </div>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { router, usePage } from '@inertiajs/vue3';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import RegistrationForm from '@/Features/Registrations/RegistrationForm.vue';

const { training } = defineProps<{
  training: App.Entities.Training;
}>();

const handleSubmit = (data: Record<string, any>) => {
  router.post(
    route('registrations.store', { form: training.form.id }),
    { data, user_id: usePage().props.auth?.user.id },
    {
      onSuccess: () => router.visit(route('trainings.show', training.id)),
    },
  );
};
</script>
