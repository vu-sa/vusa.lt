<template>
  <Head title="Log in" />

  <NConfigProvider :theme-overrides="themeOverrides">
    <div>
      <div
        class="absolute -z-50 h-full w-full blur-[2px] brightness-[35%] saturate-50"
      >
        <NCarousel
          autoplay
          :interval="3000"
          :show-dots="false"
          :transition-style="{ transitionDuration: '1500ms' }"
          effect="fade"
          class="bg-black-800 h-full w-full"
        >
          <img
            src="/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg"
            class="h-full w-full object-cover"
          />
          <img
            src="/images/photos/stovykla.jpg"
            class="h-full w-full object-cover"
          />
          <img
            src="/images/photos/observatorijos_kiemelis.jpg"
            class="h-full w-full object-cover"
          />
        </NCarousel>
      </div>
      <FadeTransition appear>
        <div
          class="grid min-h-screen justify-center p-4 sm:grid-cols-2 sm:grid-rows-none"
        >
          <div class="flex h-fit justify-center sm:h-auto">
            <AppLogo class="hidden w-96 invert sm:block" />
          </div>
          <div
            class="subtle-gray-gradient m-auto mt-0 flex h-auto w-fit flex-col items-center gap-4 rounded-lg from-zinc-100 to-white p-4 text-zinc-700 shadow-xl transition-shadow duration-500 ease-in-out hover:shadow-zinc-900/90 sm:mt-auto sm:justify-center sm:p-12"
          >
            <h1 class="font-bold text-zinc-700">Labas! 👋</h1>
            <AppLogo class="w-24 sm:hidden" />

            <p class="text-center text-xs text-zinc-600 sm:text-left">
              <strong>mano.vusa.lt</strong> gali naudoti VU SA nariai.
              <Link class="text-zinc-400 underline" :href="route('main.home')"
                >Kaip tapti?</Link
              >
            </p>

            <FadeTransition mode="out-in">
              <div
                v-if="!useSimpleRegistration"
                class="mt-4 flex flex-col gap-4"
              >
                <MicrosoftButton />
                <NDivider>Arba</NDivider>
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
                <div class="mt-6 w-full overflow-hidden px-6 py-4">
                  <div v-if="hasErrors" class="mb-4">
                    <div class="font-medium text-vusa-red">
                      Kažkas ne taip...
                    </div>

                    <ul
                      class="mt-3 list-inside list-disc text-sm text-vusa-red"
                    >
                      <li v-for="(error, key) in errors" :key="key">
                        {{ error }}
                      </li>
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
      </FadeTransition>
    </div>
  </NConfigProvider>
</template>

<script setup lang="ts">
import { ArrowHookUpLeft24Regular, Key24Filled } from "@vicons/fluent";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import {
  NButton,
  NCarousel,
  NConfigProvider,
  NDivider,
  NForm,
  NFormItem,
  NIcon,
  NInput,
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
