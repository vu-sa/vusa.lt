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
      <div class="grid min-h-screen items-center justify-center p-4 md:grid-cols-2">
        <!-- Logo Section -->
        <div class="hidden md:flex items-center justify-center">
          <AppLogo class="w-96 invert" />
        </div>

        <!-- Login Form Section -->
        <div class="m-auto flex flex-col rounded-2xl bg-gradient-to-br from-white/95 via-white/90 to-zinc-50/85 backdrop-blur-md text-zinc-700 shadow-2xl ring-1 ring-zinc-200/50 dark:from-zinc-900/95 dark:via-zinc-900/90 dark:to-zinc-800/85 dark:text-zinc-300 dark:ring-zinc-700/50 max-w-md w-full overflow-hidden">
          <!-- Main content area -->
          <div class="flex flex-col items-center gap-5 p-6 sm:p-10">
            <!-- Mobile Logo -->
            <div class="flex justify-center sm:hidden">
              <AppLogo class="w-20" />
            </div>
            <!-- Welcome Header -->
            <div class="text-center space-y-2">
              <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-800 dark:text-zinc-100">
                {{ $page.props.app.locale === 'en' ? 'Welcome back' : 'Sveiki sugrįžę' }}
              </h1>
              <p class="text-base sm:text-lg font-medium text-vusa-red/80 dark:text-vusa-red/70">
                {{ $page.props.app.locale === 'en' ? 'to my VU SR' : 'į Mano VU SA' }}
              </p>
            </div>

            <!-- Login Options -->
            <FadeTransition mode="out-in">
              <!-- Microsoft Login -->
              <div v-if="!useSimpleRegistration" class="w-full space-y-4">
                <!-- Error Message -->
                <Alert v-if="Object.keys(errors).length > 0 && !errorDismissed" variant="destructive" class="relative">
                  <IFluentErrorCircle16Regular class="size-4" />
                  <AlertTitle>
                    {{ $page.props.app.locale === 'en' ? 'Login failed' : 'Prisijungimas nepavyko' }}
                  </AlertTitle>
                  <AlertDescription>
                    <ul class="space-y-1">
                      <li v-for="(error, key) in errors" :key>
                        {{ error }}
                      </li>
                    </ul>
                  </AlertDescription>
                  <button
                    type="button"
                    class="absolute top-3 right-3 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                    @click="errorDismissed = true"
                  >
                    <IFluentDismiss16Regular class="size-4" />
                  </button>
                </Alert>

                <MicrosoftButton class="w-full" />
                <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                  {{ $page.props.app.locale === 'en' ? 'Quick access with your university account' : 'Greitas priėjimas su universiteto paskyra' }}
                </p>

                <!-- Simple Divider -->
                <div class="relative flex items-center justify-center py-2">
                  <div class="flex-1 border-t border-zinc-300/60 dark:border-zinc-600/60" />
                  <div class="mx-4 text-sm text-zinc-500 dark:text-zinc-400 bg-transparent px-2">
                    {{ $t('Arba') }}
                  </div>
                  <div class="flex-1 border-t border-zinc-300/60 dark:border-zinc-600/60" />
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
                <Alert v-if="Object.keys(errors).length > 0 && !errorDismissed" variant="destructive" class="relative">
                  <IFluentErrorCircle16Regular class="size-4" />
                  <AlertTitle>
                    {{ $page.props.app.locale === 'en' ? 'Something went wrong' : 'Kažkas ne taip' }}...
                  </AlertTitle>
                  <AlertDescription>
                    <ul class="space-y-1">
                      <li v-for="(error, key) in errors" :key>
                        {{ error }}
                      </li>
                    </ul>
                  </AlertDescription>
                  <button
                    type="button"
                    class="absolute top-3 right-3 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                    @click="errorDismissed = true"
                  >
                    <IFluentDismiss16Regular class="size-4" />
                  </button>
                </Alert>

                <Alert v-if="status" class="bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900/30 dark:text-green-200">
                  <IFluentCheckmarkCircle16Regular class="size-4" />
                  <AlertDescription>
                    {{ status }}
                  </AlertDescription>
                </Alert>

                <!-- Login Form -->
                <Form v-slot="{ errors: validationErrors }" :validation-schema @submit="handleSubmit">
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

          <!-- Card Footer - Back to main site (hidden in PWA) -->
          <a
            v-if="!isPWA"
            :href="route('home', { lang: $page.props.app.locale, subdomain: 'www' })"
            class="flex items-center justify-center gap-2 py-3.5 text-xs text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300 transition-colors border-t border-zinc-200/50 dark:border-zinc-700/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
          >
            <IFluentArrowLeft16Regular class="size-3.5" />
            {{ $page.props.app.locale === 'en' ? 'Back to vusa.lt' : 'Grįžti į vusa.lt' }}
          </a>
        </div>
      </div>
    </FadeTransition>
  </div>
</template>

<script setup lang="ts">
import { z } from 'zod';
import { toTypedSchema } from '@vee-validate/zod';
import { trans as $t } from 'laravel-vue-i18n';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Autoplay from 'embla-carousel-autoplay';
import Fade from 'embla-carousel-fade';

import { usePWA } from '@/Composables/usePWA';

// Components
import AppLogo from '@/Components/AppLogo.vue';
import FadeTransition from '@/Components/Transitions/FadeTransition.vue';
import MicrosoftButton from '@/Components/Buttons/MicrosoftLoginButton.vue';
import { Carousel, CarouselContent, CarouselItem } from '@/Components/ui/carousel';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/Components/ui/form';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';

// Icons
import IFluentKey24Filled from '~icons/fluent/key-24-filled';
import IFluentArrowHookUpLeft24Regular from '~icons/fluent/arrow-hook-up-left-24-regular';
import IFluentArrowLeft16Regular from '~icons/fluent/arrow-left-16-regular';
import IFluentErrorCircle16Regular from '~icons/fluent/error-circle-16-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentCheckmarkCircle16Regular from '~icons/fluent/checkmark-circle-16-regular';

defineProps<{
  status?: string;
}>();

const { isPWA } = usePWA();
const useSimpleRegistration = ref(false);
const errorDismissed = ref(false);

// Get errors from Inertia page props - reset dismissed state when errors change
const errors = computed(() => {
  const pageErrors = usePage().props.errors || {};
  // Reset dismissed state when new errors come in
  if (Object.keys(pageErrors).length > 0) {
    errorDismissed.value = false;
  }
  return pageErrors;
});

// Inertia form for submission
const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const $page = usePage();

// Validation schema using Zod
const validationSchema = toTypedSchema(
  z.object({
    email: z
      .string({
        required_error: $page.props.app.locale === 'en'
          ? 'Email is required.'
          : 'El. paštas yra privalomas.',
      })
      .min(1, $page.props.app.locale === 'en'
        ? 'Email is required.'
        : 'El. paštas yra privalomas.')
      .email($page.props.app.locale === 'en'
        ? 'Please enter a valid email address.'
        : 'Įveskite tinkamą el. pašto adresą.'),
    password: z
      .string({
        required_error: $page.props.app.locale === 'en'
          ? 'Password is required.'
          : 'Slaptažodis yra privalomas.',
      })
      .min(1, $page.props.app.locale === 'en'
        ? 'Password is required.'
        : 'Slaptažodis yra privalomas.'),
  }),
);

// Handle form submission
const handleSubmit = (values: { email: string; password: string }) => {
  form.email = values.email;
  form.password = values.password;

  form.post(route('login'), {
    onFinish: () => {
      form.reset('password');
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
