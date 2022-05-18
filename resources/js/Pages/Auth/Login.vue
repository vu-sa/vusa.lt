<template>
  <Head title="Log in" />

  <jet-authentication-card>
    <template #logo>
      <Link :href="route('dashboard')"><AppLogo class="w-48" /></Link>
    </template>

    <jet-validation-errors class="mb-4" />

    <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit">
      <div>
        <jet-label for="email" value="El. paštas" />
        <jet-input
          id="email"
          type="email"
          class="mt-1 block w-full"
          v-model="form.email"
          required
          autofocus
        />
      </div>

      <div class="mt-4">
        <jet-label for="password" value="Slaptažodis" />
        <jet-input
          id="password"
          type="password"
          class="mt-1 block w-full"
          v-model="form.password"
          required
          autocomplete="current-password"
        />
      </div>

      <div class="block mt-4">
        <label class="flex items-center">
          <jet-checkbox name="remember" v-model:checked="form.remember" />
          <span class="ml-2 text-sm text-gray-600">Prisiminti mane</span>
        </label>
      </div>

      <div class="flex items-center justify-end mt-4">
        <MicrosoftButton></MicrosoftButton>

        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="underline text-sm text-gray-600 hover:text-gray-900"
        >
          Forgot your password?
        </Link>

        <jet-button
          class="ml-4"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Prisijungti
        </jet-button>
      </div>
    </form>
  </jet-authentication-card>
</template>

<script setup>
import AppLogo from "@/Components/AppLogo.vue";
import JetAuthenticationCard from "@/Jetstream/AuthenticationCard.vue";
import JetAuthenticationCardLogo from "@/Jetstream/AuthenticationCardLogo.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetValidationErrors from "@/Jetstream/ValidationErrors.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import { reactive } from "vue";
import MicrosoftButton from "@/Components/MicrosoftButton.vue";
1;

const props = defineProps({
  canResetPassword: Boolean,
  status: String,
});

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
      onFinish: () => form.reset("password"),
    });
};
</script>
