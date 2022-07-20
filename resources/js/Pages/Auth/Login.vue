<template>
  <Head title="Log in" />

  <div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100"
  >
    <div>
      <AppLogo class="w-48" />
    </div>

    <div
      class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg"
    >
      <validation-errors class="mb-4" />

      <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
        {{ status }}
      </div>

      <form @submit.prevent="submit">
        <div>
          <NInput
            id="email"
            v-model:value="form.email"
            placeholder="El. paštas"
            type="email"
            class="mt-1 block w-full"
            required
            autofocus
          />
        </div>

        <div class="mt-4">
          <NInput
            id="password"
            v-model:value="form.password"
            type="password"
            class="mt-1 block w-full"
            placeholder="Slaptažodis"
            required
            autocomplete="current-password"
          />
        </div>

        <!-- <div class="block mt-4">
        <label class="flex items-center">
          <jet-checkbox name="remember" v-model:checked="form.remember" />
          <span class="ml-2 text-sm text-gray-600">Prisiminti mane</span>
        </label>
      </div> -->

        <div class="flex items-center justify-end mt-4 gap-4">
          <NPopover
            ><template #trigger>
              <MicrosoftButton></MicrosoftButton>
            </template>
            <span>Tik su VU SA paskyromis.</span></NPopover
          >

          <!-- <Link
            v-if="canResetPassword"
            :href="route('password.request')"
            class="underline text-sm text-gray-600 hover:text-gray-900"
          >
            Forgot your password?
          </Link> -->

          <NButton attr-type="submit" :disabled="form.processing">
            Prisijungti
          </NButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, useForm } from "@inertiajs/inertia-vue3";
import { NButton, NInput, NPopover } from "naive-ui";
import { reactive } from "vue";
import AppLogo from "@/Components/AppLogo.vue";
import MicrosoftButton from "@/Components/MicrosoftButton.vue";
import ValidationErrors from "@/Components/Public/ValidationErrors.vue";

defineProps({
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
