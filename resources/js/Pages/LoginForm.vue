<template>
  <Head title="Log in" />

  <div
    class="flex min-h-screen flex-col items-center bg-zinc-200 pt-6 sm:justify-center sm:pt-0"
  >
    <div>
      <AppLogo :is-theme-dark="false" class="w-48" />
    </div>

    <div
      class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
    >
      <div v-if="hasErrors" class="mb-4">
        <div class="font-medium text-vusa-red">Kažkas ne taip...</div>

        <ul class="mt-3 list-inside list-disc text-sm text-vusa-red">
          <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
        </ul>
      </div>

      <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
        {{ status }}
      </div>

      <NForm :show-feedback="false" @submit.prevent="submit">
        <NFormItem>
          <NInput
            id="email"
            v-model:value="form.email"
            placeholder="vusa@vusa.lt"
            :input-props="{ type: 'email' }"
            class="mt-1 block w-full"
            required
            autofocus
          />
          <template #label><strong>El. paštas</strong></template>
        </NFormItem>

        <NFormItem class="mt-4">
          <NInput
            id="password"
            v-model:value="form.password"
            type="password"
            class="mt-1 block w-full"
            placeholder="*********"
            required
            autocomplete="current-password"
          />
          <template #label><strong>Slaptažodis</strong></template>
        </NFormItem>

        <div class="mt-4 flex items-center justify-end gap-4">
          <NPopover
            ><template #trigger>
              <MicrosoftButton></MicrosoftButton>
            </template>
            <span>Tik su VU SA paskyromis.</span></NPopover
          >

          <NButton
            attr-type="submit"
            :disabled="form.processing"
            :loading="form.processing"
          >
            Prisijungti
          </NButton>
        </div>
      </NForm>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, useForm, usePage } from "@inertiajs/inertia-vue3";
import { NButton, NForm, NFormItem, NInput, NPopover } from "naive-ui";
import { reactive, ref } from "vue";
import route from "ziggy-js";

import AppLogo from "@/Components/AppLogo.vue";
import MicrosoftButton from "@/Components/Buttons/MicrosoftLoginButton.vue";

defineProps<{
  status?: string;
}>();

const errors = ref({});
const hasErrors = ref(false);

const form = reactive(
  useForm({
    email: "",
    password: "",
    remember: false,
  })
);

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      remember: form.remember ? "1" : "0",
    }))
    .post(route("login"), {
      onFinish: () => {
        form.reset("password");
        errors.value = usePage<InertiaProps>().props.value.errors;
        hasErrors.value = Object.keys(errors.value).length > 0;
      },
    });
};
</script>
