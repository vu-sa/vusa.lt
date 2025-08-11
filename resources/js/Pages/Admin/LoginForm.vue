<template>
  <Head title="Log in" />

  <!-- Background Carousel -->
  <Carousel class="bg-black-800 h-full fixed -z-50 blur-1 brightness-15 saturate-50 contrast-100" :plugins="[Autoplay({
    delay: 5000,
  }), Fade()]">
    <CarouselContent class="h-full">
      <CarouselItem>
        <img src="/images/become-a-member/20250510_VUSA-156.webp" class="size-full object-cover"
          alt="VU SA members group photo 2025">
      </CarouselItem>
      <CarouselItem>
        <img src="/images/become-a-member/mokymai2025-2.webp" class="size-full object-cover" alt="Student Trainings">
      </CarouselItem>
      <CarouselItem>
        <img src="/images/become-a-member/mokymai2025-1.webp" class="size-full object-cover" alt="Student Trainings">
      </CarouselItem>
    </CarouselContent>
  </Carousel>

  <!-- Main Content -->
  <div class="min-h-screen">
    <FadeTransition appear>
      <div class="grid min-h-screen justify-center p-4 md:grid-cols-2">
        <!-- Logo Section -->
        <div class="flex h-fit justify-center sm:h-auto">
          <AppLogo class="hidden w-96 invert sm:block" />
        </div>
        
        <!-- Login Form Section -->
        <div class="m-auto mt-0 flex h-auto flex-col items-center gap-6 rounded-lg bg-white/95 backdrop-blur-sm p-8 text-zinc-700 shadow-xl dark:bg-zinc-900/95 dark:text-zinc-300 sm:mt-auto sm:justify-center sm:p-12 max-w-md w-full -order-1 md:order-2">
          <!-- Mobile Logo -->
          <div class="flex justify-center mb-4 sm:hidden">
            <AppLogo class="w-24" />
          </div>
          <!-- Welcome Header -->
          <div class="text-center space-y-2">
            <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
              {{ $page.props.app.locale === 'en' ? 'Welcome back' : 'Sveiki sugrįžę' }}
            </h1>
            <p class="text-lg text-zinc-700 dark:text-zinc-300">
              {{ $page.props.app.locale === 'en' ? 'to my VU SR' : 'į Mano VU SA' }}
            </p>
            <!-- <p class="text-sm text-zinc-600 dark:text-zinc-400"> -->
            <!--   {{ $page.props.app.locale === 'en' ? 'Continue your student representation journey' : 'Tęskite savo studentų atstovavimo kelionę' }} -->
            <!-- </p> -->
          </div>

          <!-- Login Options -->
          <FadeTransition mode="out-in">
            <!-- Microsoft Login -->
            <div v-if="!useSimpleRegistration" class="w-full space-y-4">
              <MicrosoftButton class="w-full" />
              <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                {{ $page.props.app.locale === 'en' ? 'Quick access with your university account' : 'Greitas priėjimas su universiteto paskyra' }}
              </p>
              
              <!-- Simple Divider -->
              <div class="relative flex items-center justify-center my-6">
                <div class="flex-1 border-t border-zinc-300 dark:border-zinc-600"></div>
                <div class="mx-4 text-sm text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 px-2">
                  {{ $t('Arba') }}
                </div>
                <div class="flex-1 border-t border-zinc-300 dark:border-zinc-600"></div>
              </div>
              
              <Button
                variant="ghost"
                size="sm"
                class="w-full text-zinc-600 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200"
                @click="useSimpleRegistration = true"
              >
                <IFluentKey24Filled class="w-4 h-4 mr-2" />
                {{ $page.props.app.locale === 'en' ? 'Sign in with email' : 'Prisijungti el. paštu' }}
              </Button>
            </div>
            
            <!-- Email/Password Form -->
            <div v-else class="w-full space-y-6">
              <!-- Admin Login Explanation -->
              <div class="p-3 rounded-md bg-zinc-50/50 border border-zinc-200/30 dark:bg-zinc-800/30 dark:border-zinc-700/30">
                <div class="text-xs text-zinc-600 dark:text-zinc-400">
                  <p class="font-medium mb-1 text-zinc-700 dark:text-zinc-300">
                    {{ $page.props.app.locale === 'en' ? 'Administrator Access' : 'Administratoriaus prieiga' }}
                  </p>
                  <p>
                    {{ $page.props.app.locale === 'en' 
                      ? 'These credentials should be provided by a VU SR administrator. If you don\'t have them, please continue with Microsoft login.' 
                      : 'Šiuos prisijungimo duomenis turėjo suteikti VU SA administratorius. Jei jų neturite, prisijunkite su Microsoft paskyra.' }}
                  </p>
                </div>
              </div>
              
              <!-- Status Messages -->
              <div v-if="Object.keys(errors).length > 0" class="p-4 rounded-lg bg-red-50 border border-red-200 dark:bg-red-950/20 dark:border-red-900/30">
                <div class="font-medium text-red-800 dark:text-red-200 text-sm">
                  {{ $page.props.app.locale === 'en' ? 'Something went wrong' : 'Kažkas ne taip' }}...
                </div>
                <ul class="mt-2 text-sm text-red-700 dark:text-red-300 space-y-1">
                  <li v-for="(error, key) in errors" :key="key">
                    {{ error }}
                  </li>
                </ul>
              </div>

              <div v-if="status" class="p-4 rounded-lg bg-green-50 border border-green-200 dark:bg-green-950/20 dark:border-green-900/30">
                <div class="text-sm font-medium text-green-800 dark:text-green-200">
                  {{ status }}
                </div>
              </div>

              <!-- Login Form -->
              <Form v-slot="{ errors: validationErrors }" :validation-schema="validationSchema" @submit="handleSubmit">
                <div class="space-y-4">
                  <!-- Email Field -->
                  <FormField v-slot="{ componentField }" name="email">
                    <FormItem>
                      <FormLabel class="text-zinc-800 dark:text-zinc-200">
                        {{ $t("forms.fields.email") }}
                      </FormLabel>
                      <FormControl>
                        <Input
                          id="email"
                          v-bind="componentField"
                          type="email"
                          :placeholder="$page.props.app.locale === 'en' ? 'Enter your email address' : 'Įveskite el. pašto adresą'"
                          autocomplete="email"
                          autofocus
                          class="transition-colors focus:ring-2 focus:ring-vusa-red/20 focus:border-vusa-red"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  </FormField>

                  <!-- Password Field -->
                  <FormField v-slot="{ componentField }" name="password">
                    <FormItem>
                      <FormLabel class="text-zinc-800 dark:text-zinc-200">
                        {{ $t("forms.fields.password") }}
                      </FormLabel>
                      <FormControl>
                        <Input
                          id="password"
                          v-bind="componentField"
                          type="password"
                          :placeholder="$page.props.app.locale === 'en' ? 'Enter your password' : 'Įveskite slaptažodį'"
                          autocomplete="current-password"
                          class="transition-colors focus:ring-2 focus:ring-vusa-red/20 focus:border-vusa-red"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  </FormField>

                  <!-- Form Actions -->
                  <div class="flex items-center justify-between gap-4 pt-4">
                    <Button
                      variant="ghost"
                      size="sm"
                      type="button"
                      class="text-zinc-600 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200"
                      @click="useSimpleRegistration = false"
                    >
                      <IFluentArrowHookUpLeft24Regular class="w-4 h-4 mr-2" />
                      {{ $t("Grįžti") }}
                    </Button>
                    
                    <Button
                      type="submit"
                      size="sm"
                      class="bg-vusa-red hover:bg-vusa-red/90 text-white transition-colors duration-200 focus:ring-2 focus:ring-vusa-red/20 disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="form.processing"
                    >
                      <span v-if="form.processing" class="flex items-center">
                        <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2" />
                        {{ $page.props.app.locale === 'en' ? 'Signing in...' : 'Prisijungiama...' }}
                      </span>
                      <span v-else>
                        {{ $page.props.app.locale === 'en' ? 'Sign In' : 'Prisijungti' }}
                      </span>
                    </Button>
                  </div>
                </div>
              </Form>
            </div>
          </FadeTransition>
        </div>
      </div>
    </FadeTransition>
  </div>
</template>

<script setup lang="ts">
import { z } from "zod";
import { toTypedSchema } from "@vee-validate/zod";
import { trans as $t } from "laravel-vue-i18n";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Autoplay from "embla-carousel-autoplay";
import Fade from "embla-carousel-fade";

// Components
import AppLogo from "@/Components/AppLogo.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import MicrosoftButton from "@/Components/Buttons/MicrosoftLoginButton.vue";
import { Carousel, CarouselContent, CarouselItem } from "@/Components/ui/carousel";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/Components/ui/form";

// Icons
import IFluentKey24Filled from "~icons/fluent/key-24-filled";
import IFluentArrowHookUpLeft24Regular from "~icons/fluent/arrow-hook-up-left-24-regular";

defineProps<{
  status?: string;
}>();

const useSimpleRegistration = ref(false);

// Get errors from Inertia page props
const errors = computed(() => usePage().props.errors || {});

// Inertia form for submission
const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const $page = usePage();

// Validation schema using Zod
const validationSchema = toTypedSchema(
  z.object({
    email: z
      .string({ 
        required_error: $page.props.app.locale === 'en' 
          ? "Email is required." 
          : "El. paštas yra privalomas." 
      })
      .min(1, $page.props.app.locale === 'en' 
        ? "Email is required." 
        : "El. paštas yra privalomas.")
      .email($page.props.app.locale === 'en' 
        ? "Please enter a valid email address." 
        : "Įveskite tinkamą el. pašto adresą."),
    password: z
      .string({ 
        required_error: $page.props.app.locale === 'en' 
          ? "Password is required." 
          : "Slaptažodis yra privalomas." 
      })
      .min(1, $page.props.app.locale === 'en' 
        ? "Password is required." 
        : "Slaptažodis yra privalomas."),
  })
);

// Handle form submission
const handleSubmit = (values: { email: string; password: string }) => {
  form.email = values.email;
  form.password = values.password;
  
  form.post(route("login"), {
    onFinish: () => {
      form.reset("password");
    },
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
