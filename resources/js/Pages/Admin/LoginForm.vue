<template>
  <Head :title="$t('Prisijungimas')" />

  <main class="grid min-h-svh grid-cols-1 bg-background lg:grid-cols-[1.15fr_1fr]">
    <!-- Hero photo carousel (desktop) -->
    <section class="relative hidden lg:block h-full overflow-hidden bg-ink">
      <Carousel
        class="h-full w-full"
        :plugins="[Autoplay({ delay: 5000 }), Fade()]"
      >
        <CarouselContent class="h-full -ml-0">
          <CarouselItem
            v-for="(photo, index) in heroPhotos"
            :key="index"
            class="h-full pl-0 basis-full"
          >
            <img
              :src="photo.src"
              :alt="photo.alt"
              class="size-full object-cover"
              loading="lazy"
            >
          </CarouselItem>
        </CarouselContent>
      </Carousel>

      <!-- Gradient scrim overlay to guarantee text contrast -->
      <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink/90 via-ink/45 to-ink/60" />

      <!-- Hero overlay content -->
      <div class="pointer-events-none absolute inset-0 flex flex-col justify-between p-10 text-brand-fixed-foreground z-10">
        <div class="flex items-center">
          <img
            :src="logoSrc"
            alt="VU SA"
            class="h-9 w-auto brightness-0 invert"
          >
        </div>
        <div class="max-w-md">
          <h2 class="text-pretty text-3xl font-bold leading-tight text-brand-fixed-foreground">
            {{ $t('auth.sistema_subtitle') }}
          </h2>
        </div>
      </div>
    </section>

    <!-- Auth panel -->
    <section class="flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-14">
      <div class="mx-auto w-full max-w-sm">
        <!-- Mobile brand mark -->
        <div class="mb-10 flex items-center lg:hidden">
          <img
            :src="logoSrc"
            alt="VU SA"
            class="h-9 w-auto dark:invert"
          >
        </div>

        <p class="text-xs font-bold uppercase tracking-[0.28em] text-brand">
          {{ $t('Prisijungimas') }}
        </p>
        <h1 class="mt-3 text-pretty text-3xl font-bold leading-tight text-foreground">
          {{ $t('auth.welcome_back') }}
        </h1>
        <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
          {{ $t('auth.login_description') }}
        </p>

        <!-- Error Alert -->
        <div
          v-if="Object.keys(errors).length > 0 && !errorDismissed"
          class="mt-6 flex items-start gap-3 border border-status-danger/40 bg-status-danger-surface p-4 text-xs text-status-danger relative"
          role="alert"
        >
          <AlertCircle class="size-4 shrink-0 mt-0.5" />
          <div class="flex-1 space-y-1">
            <p class="font-bold">
              {{ $t('Prisijungimas nepavyko') }}
            </p>
            <ul class="list-disc pl-4 space-y-0.5 text-muted-foreground">
              <li v-for="(error, key) in errors" :key>
                {{ error }}
              </li>
            </ul>
          </div>
          <button
            type="button"
            class="absolute top-3 right-3 text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
            aria-label="Dismiss error"
            @click="errorDismissed = true"
          >
            <X class="size-4" />
          </button>
        </div>

        <!-- Success Status Alert -->
        <div
          v-if="status"
          class="mt-6 flex items-center gap-3 border border-status-success/40 bg-status-success-surface p-4 text-xs text-status-success"
          role="status"
        >
          <CheckCircle2 class="size-4 shrink-0" />
          <p>{{ status }}</p>
        </div>

        <!-- Main login options (visible when not viewing email form) -->
        <template v-if="!showEmailForm">
          <!-- Primary: Microsoft OAuth Login -->
          <div class="mt-8">
            <MicrosoftButton />
          </div>

          <!-- Divider -->
          <div class="my-7 flex items-center gap-4">
            <span class="h-px flex-1 bg-border" />
            <span class="text-xs font-medium uppercase tracking-widest text-muted-foreground">{{ $t('Arba') }}</span>
            <span class="h-px flex-1 bg-border" />
          </div>

          <!-- Secondary: Reveal Email & Password Form (Muted button) -->
          <button
            type="button"
            :class="[
              'flex w-full items-center justify-center gap-2',
              'border border-border/70 bg-transparent px-4 py-2.5 text-xs font-medium uppercase tracking-wider',
              'text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground',
              'rounded-none min-h-[44px] cursor-pointer',
            ]"
            @click="showEmailForm = true"
          >
            <Mail class="size-3.5" />
            <span>{{ $t('auth.login_with_email') }}</span>
          </button>
        </template>

        <!-- Email & Password Form (Microsoft button hidden while viewing) -->
        <form
          v-else
          class="mt-8 space-y-4"
          @submit.prevent="handleSubmit"
        >
          <div class="space-y-1.5">
            <label for="email" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
              {{ $t('forms.fields.email') }}
            </label>
            <div class="relative">
              <Mail class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                placeholder="vardas@vu.lt"
                autocomplete="email"
                :class="[
                  'w-full border border-input bg-card py-2.5 pl-9 pr-3 text-sm text-foreground',
                  'outline-none transition-colors placeholder:text-muted-foreground',
                  'focus:border-ring focus:ring-2 focus:ring-ring/30 rounded-none min-h-[44px]',
                ]"
              >
            </div>
            <p v-if="form.errors.email" class="text-xs font-medium text-destructive">
              {{ form.errors.email }}
            </p>
          </div>

          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label for="password" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                {{ $t('forms.fields.password') }}
              </label>
            </div>
            <div class="relative">
              <Lock class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                autocomplete="current-password"
                :class="[
                  'w-full border border-input bg-card py-2.5 pl-9 pr-11 text-sm text-foreground',
                  'outline-none transition-colors placeholder:text-muted-foreground',
                  'focus:border-ring focus:ring-2 focus:ring-ring/30 rounded-none min-h-[44px]',
                ]"
              >
              <button
                type="button"
                :class="[
                  'absolute right-1 top-1/2 flex size-9 -translate-y-1/2',
                  'items-center justify-center text-muted-foreground transition-colors',
                  'hover:text-foreground cursor-pointer',
                ]"
                :aria-label="showPassword ? $t('Slėpti slaptažodį') : $t('Rodyti slaptažodį')"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="size-4" />
                <Eye v-else class="size-4" />
              </button>
            </div>
            <p v-if="form.errors.password" class="text-xs font-medium text-destructive">
              {{ form.errors.password }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            :class="[
              'flex w-full items-center justify-center gap-2',
              'border border-brand-fill bg-brand-fill px-4 py-3 text-sm font-bold text-brand-foreground',
              'transition-opacity hover:opacity-90 disabled:opacity-70 rounded-none min-h-[44px]',
              'cursor-pointer disabled:cursor-not-allowed',
            ]"
          >
            <Loader2 v-if="form.processing" class="size-4 animate-spin" />
            <ArrowRight v-else class="size-4" />
            <span>{{ form.processing ? $t('Prisijungiama...') : $t('auth.login') }}</span>
          </button>

          <button
            type="button"
            class="flex w-full items-center justify-center gap-2 py-2 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground min-h-[44px] cursor-pointer"
            @click="showEmailForm = false"
          >
            <ArrowLeft class="size-3.5" />
            <span>{{ $t('auth.back_to_login_methods') }}</span>
          </button>
        </form>

        <!-- Terms and Privacy -->
        <p class="mt-10 text-center text-xs leading-relaxed text-muted-foreground">
          {{ $t('auth.privacy_agreement') }}
          <a
            :href="privacyUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="text-foreground underline underline-offset-2 hover:text-brand"
          >
            {{ $t('auth.privacy_policy') }}
          </a>.
        </p>

        <!-- Return to Public Site (hidden in PWA) -->
        <a
          v-if="!isPWA"
          :href="homeUrl"
          class="mt-4 flex items-center justify-center gap-2 py-2 text-xs text-muted-foreground hover:text-foreground transition-colors"
        >
          <ArrowLeft class="size-3.5" />
          <span>{{ $t('auth.back_to_vusa') }}</span>
        </a>
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Autoplay from 'embla-carousel-autoplay';
import Fade from 'embla-carousel-fade';
import {
  AlertCircle,
  ArrowLeft,
  ArrowRight,
  CheckCircle2,
  Eye,
  EyeOff,
  Loader2,
  Lock,
  Mail,
  X,
} from 'lucide-vue-next';

import MicrosoftButton from '@/Components/Buttons/MicrosoftLoginButton.vue';
import { Carousel, CarouselContent, CarouselItem } from '@/Components/ui/carousel';
import { usePWA } from '@/Composables/usePWA';
import { getAppLogoSrc } from '@/Utils/AppLogo';

defineProps<{
  status?: string;
}>();

const { isPWA } = usePWA();
const page = usePage();

const showEmailForm = ref(false);
const showPassword = ref(false);
const errorDismissed = ref(false);

const logoSrc = computed(() => getAppLogoSrc('vusa', page.props.app?.locale));

const heroPhotos = [
  { src: '/images/become-a-member/20250510_VUSA-156.webp', alt: 'VU SA nariai 2025' },
  { src: '/images/become-a-member/mokymai2025-2.webp', alt: 'VU SA mokymai' },
  { src: '/images/become-a-member/mokymai2025-1.webp', alt: 'VU SA mokymai' },
  { src: '/images/become-a-member/VU SA 24-25-06.webp', alt: 'VU SA bendruomenė' },
];

const homeUrl = computed(() => {
  try {
    return route('home', { lang: page.props.app?.locale, subdomain: 'www' });
  }
  catch {
    return '/';
  }
});

const privacyUrl = computed(() => {
  return (page.props.organization as { privacyPageUrl?: string } | undefined)?.privacyPageUrl || '#';
});

// Retrieve errors from Inertia page props
const errors = computed(() => page.props.errors || {});

watch(errors, (newErrors) => {
  if (Object.keys(newErrors).length > 0) {
    errorDismissed.value = false;
  }
});

// Inertia form submission
const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const handleSubmit = () => {
  form.post(route('login'), {
    onFinish: () => {
      form.reset('password');
    },
  });
};
</script>

<style scoped>
div[data-slot="carousel-content"] {
  height: 100%;
}
</style>
