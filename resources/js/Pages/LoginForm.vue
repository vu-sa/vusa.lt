<template>
  <Head title="Log in" />

  <NConfigProvider :theme-overrides="themeOverrides">
    <div class="grid grid-rows-2 sm:grid-cols-2 sm:grid-rows-none">
      <NCarousel
        autoplay
        :show-dots="false"
        :transition-style="{ transitionDuration: '1500ms' }"
        effect="fade"
        class="hidden h-24 w-24 sm:block sm:h-full"
      >
        <img
          src="/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg"
          class="object-cover sm:h-full"
        />
        <img src="/images/photos/stovykla.jpg" class="object-cover sm:h-full" />
        <img
          src="/images/photos/observatorijos_kiemelis.jpg"
          class="object-cover sm:h-full"
        />
      </NCarousel>
      <div
        class="flex min-h-screen flex-col items-center gap-4 bg-white pt-6 sm:justify-center sm:pt-0"
      >
        <AppLogo :is-theme-dark="false" class="w-48" />
        <FadeTransition mode="out-in">
          <div v-if="!useSimpleRegistration" class="mt-4 flex flex-col gap-4">
            <MicrosoftButton />
            <NDivider />
            <NButton
              size="tiny"
              text
              quaternary
              @click="useSimpleRegistration = true"
              ><template #icon
                ><NIcon :component="Key24Filled"></NIcon></template
              >Naudoti kitą prisijungimą</NButton
            >
          </div>
          <div
            v-else
            class="flex w-full flex-col gap-4 sm:w-96 sm:justify-center sm:pt-0"
          >
            <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4">
              <div v-if="hasErrors" class="mb-4">
                <div class="font-medium text-vusa-red">Kažkas ne taip...</div>

                <ul class="mt-3 list-inside list-disc text-sm text-vusa-red">
                  <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                </ul>
              </div>

              <div
                v-if="status"
                class="mb-4 text-sm font-medium text-green-600"
              >
                {{ status }}
              </div>

              <NForm
                ref="formRef"
                :model="form"
                :rules="rules"
                @submit.prevent="submit"
              >
                <NFormItem path="email">
                  <NInput
                    id="email"
                    v-model:value="form.email"
                    round
                    placeholder="vusa@vusa.lt"
                    :input-props="{ type: 'email' }"
                    class="mt-1 block w-full"
                    required
                    autofocus
                  />
                  <template #label><strong>El. paštas</strong></template>
                </NFormItem>

                <NFormItem class="mt-4" path="password">
                  <NInput
                    id="password"
                    v-model:value="form.password"
                    round
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="*********"
                    required
                    autocomplete="current-password"
                  />
                  <template #label><strong>Slaptažodis</strong></template>
                </NFormItem>

                <div class="mt-4 flex items-center justify-between gap-4">
                  <NButton
                    size="small"
                    secondary
                    @click="useSimpleRegistration = false"
                    ><template #icon
                      ><NIcon
                        :component="ArrowHookUpLeft24Regular"
                      ></NIcon></template
                    >Grįžti</NButton
                  >
                  <NButton
                    size="small"
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
        </FadeTransition>
      </div>
    </div>
  </NConfigProvider>
</template>

<script setup lang="ts">
import { ArrowHookUpLeft24Regular, Key24Filled } from "@vicons/fluent";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import {
  NButton,
  NCarousel,
  NConfigProvider,
  NDivider,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NPopover,
} from "naive-ui";
import { reactive, ref } from "vue";

import AppLogo from "@/Components/AppLogo.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import MicrosoftButton from "@/Components/Buttons/MicrosoftLoginButton.vue";

defineProps<{
  status?: string;
}>();

const errors = ref({});
const hasErrors = ref(false);
const formRef = ref(null);

const useSimpleRegistration = ref(false);

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
};

const form = reactive(
  useForm({
    email: "",
    password: "",
    remember: false,
  })
);

const rules = {
  email: [
    {
      required: true,
      message: "El. paštas yra privalomas",
      trigger: "blur",
    },
  ],
  password: [
    {
      required: true,
      message: "Slaptažodis yra privalomas",
      trigger: "blur",
    },
  ],
};

const submit = () => {
  formRef.value.validate((formErrors) => {
    if (!formErrors)
      form.post(route("login"), {
        onFinish: () => {
          form.reset("password");
          errors.value = usePage().props.errors;
          hasErrors.value = Object.keys(errors.value).length > 0;
        },
      });
  });
};
</script>
