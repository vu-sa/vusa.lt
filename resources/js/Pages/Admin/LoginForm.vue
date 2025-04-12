<template>

  <Head title="Log in" />

  <Carousel class="bg-black-800 h-full fixed -z-50 blur-1 brightness-15 saturate-50 contrast-100" :plugins="[Autoplay({
    delay: 5000,
  }), Fade()]">
    <CarouselContent class="h-full">
      <CarouselItem>
        <img src="/images/photos/VU SA 2023.jpg" class="size-full object-cover" alt="VU SA members group photo 2023">
      </CarouselItem>
      <CarouselItem>
        <img src="/images/photos/stovykla.jpg" class="size-full object-cover" alt="Students at a summer camp">
      </CarouselItem>
      <CarouselItem>
        <img src="/images/photos/observatorijos_kiemelis.jpg" class="size-full object-cover"
          alt="Vilnius University Observatory Courtyard">
      </CarouselItem>
    </CarouselContent>
  </Carousel>

  <NConfigProvider :theme-overrides="themeOverrides">
    <div>
      <FadeTransition appear>
        <div class="grid min-h-screen justify-center p-4 sm:grid-cols-2 sm:grid-rows-none">
          <div class="flex h-fit justify-center sm:h-auto">
            <AppLogo class="hidden w-96 invert sm:block" />
          </div>
          <div
            class="m-auto mt-0 flex h-auto flex-col items-center gap-4 rounded-lg bg-zinc-50 p-4 text-zinc-700 shadow-xl transition-shadow duration-500 ease-in-out hover:shadow-zinc-900/90 dark:bg-zinc-900 sm:mt-auto sm:justify-center sm:p-12">
            <h1 class="font-bold text-zinc-700">
              {{ $t("Labas") }}! 👋
            </h1>
            <AppLogo class="w-24 sm:hidden" />

            <p class="max-w-xs text-center text-xs text-zinc-600 sm:text-center">
              <strong>vusa.lt/mano</strong> {{ $t("auth.usage_status") }}.
              <!-- <Link class="text-zinc-400 underline" :href="route('home')"
                >Kaip tapti?</Link
              > -->
            </p>

            <FadeTransition mode="out-in">
              <div v-if="!useSimpleRegistration" class="mt-4 flex flex-col gap-4">
                <MicrosoftButton />
                <NDivider>{{ $t("Arba") }}</NDivider>
                <NButton size="tiny" text quaternary @click="useSimpleRegistration = true">
                  <template #icon>
                    <IFluentKey24Filled />
                  </template>{{ $t("auth.use_other_login") }}
                </NButton>
              </div>
              <div v-else class="flex flex-col gap-4 sm:w-96 sm:justify-center sm:pt-0">
                <div class="px-6 py-4">
                  <div v-if="hasErrors" class="mb-4">
                    <div class="font-medium text-vusa-red">
                      {{ $t("Kažkas ne taip") }}...
                    </div>

                    <ul class="mt-3 text-sm text-vusa-red">
                      <li v-for="(error, key) in errors" :key="key">
                        {{ error }}
                      </li>
                    </ul>
                  </div>

                  <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                  </div>

                  <NForm ref="formRef" :model="form" :rules="rules" @submit.prevent="submit">
                    <NFormItem path="email">
                      <NInput id="email" v-model:value="form.email" round placeholder="vusa@vusa.lt"
                        :input-props="{ type: 'email' }" required autofocus />
                      <template #label>
                        <strong>{{
                          $t("forms.fields.email")
                        }}</strong>
                      </template>
                    </NFormItem>

                    <NFormItem class="mt-4" path="password">
                      <NInput id="password" v-model:value="form.password" round type="password" placeholder="*********"
                        required autocomplete="current-password" />
                      <template #label>
                        <strong>{{
                          $t("forms.fields.password")
                        }}</strong>
                      </template>
                    </NFormItem>

                    <div class="mt-4 flex items-center justify-between gap-4">
                      <NButton size="small" secondary @click="useSimpleRegistration = false">
                        <template #icon>
                          <IFluentArrowHookUpLeft24Regular />
                        </template>{{ $t("Grįžti") }}
                      </NButton>
                      <NButton size="small" attr-type="submit" :disabled="form.processing" :loading="form.processing">
                        {{ $t("auth.login") }}
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
import { trans as $t } from "laravel-vue-i18n";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";

import AppLogo from "@/Components/AppLogo.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import MicrosoftButton from "@/Components/Buttons/MicrosoftLoginButton.vue";
import { Carousel, CarouselContent, CarouselItem } from "@/Components/ui/carousel";
import Autoplay from "embla-carousel-autoplay";
import Fade from "embla-carousel-fade";

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

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const rules = {
  email: [
    {
      required: true,
      message: $t("validation.required", { attribute: $t("El. paštas") }),
      trigger: "blur-sm",
    },
  ],
  password: [
    {
      required: true,
      message: $t("validation.required", {
        attribute: $t("forms.fields.password"),
      }),
      trigger: "blur-sm",
    },
  ],
};

const submit = () => {
  formRef.value?.validate((formErrors) => {
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

<style scoped>
/* 
The carousel content is not that easily accessed,
but i'll keep it that way. 
*/
div[data-slot="carousel-content"] {
  height: 100vh;
}
</style>
