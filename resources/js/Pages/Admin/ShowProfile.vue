<template>
  <FormPage
    :title="userTitle"
    :bar-title="userTitle"
    :head-title="userHeadTitle"
    :lead="$t('Tvarkyk savo asmeninę informaciją, kontaktus ir paskyros nustatymus.')"
    :entity-type="ModelEnum.USER"
    :back-href="route('dashboard')"
    :back-label="$t('Pradžia')"
    :save-label="$t('Išsaugoti nustatymus')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    mode="edit"
    :available-locales="[]"
    :created-at="user?.created_at"
    :updated-at="user?.updated_at"
    @submit="handleSubmit"
  >
    <!-- Asmens duomenys ir kontaktai -->
    <div class="space-y-6">
      <FormFieldWrapper
        id="user-name"
        :label="$t('forms.fields.name_and_surname')"
        required
        :error="form.errors.name"
      >
        <Input
          id="user-name"
          v-model="form.name"
          :disabled="user.name_was_changed"
          type="text"
          placeholder="Vardas Pavardė"
        />
        <p v-if="user.name_was_changed" class="flex items-start gap-1.5 text-xs text-muted-foreground">
          <Lock class="mt-0.5 size-3 shrink-0" aria-hidden="true" />
          {{ $t('Paskyros vardas jau buvo pakeistas. Jei reikia jį keisti, kreipkis į administratorių.') }}
        </p>
        <p v-else class="text-xs text-status-attention">
          {{ $t('Paskyros vardą galima pakeisti tik VIENĄ kartą!') }}
        </p>
      </FormFieldWrapper>

      <FormFieldWrapper
        id="user-email"
        :label="$t('El. paštas')"
        required
      >
        <Input
          id="user-email"
          :model-value="user.email"
          disabled
        />
        <p class="flex items-start gap-1.5 text-xs text-muted-foreground">
          <Lock class="mt-0.5 size-3 shrink-0" aria-hidden="true" />
          {{ $t('users.identity_locked_hint') }}
        </p>
      </FormFieldWrapper>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormFieldWrapper
          id="user-phone"
          :label="`${$t('forms.fields.phone')} (${$t('neprivaloma')})`"
          :error="form.errors.phone"
        >
          <Input id="user-phone" v-model="form.phone" placeholder="+370 612 34 567" />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="user-facebook"
          :label="`${$t('forms.fields.facebook_url')} (${$t('neprivaloma')})`"
          :error="form.errors.facebook_url"
        >
          <Input id="user-facebook" v-model="form.facebook_url" placeholder="https://www.facebook.com/..." />
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper
        id="user-picture"
        :label="`${$t('forms.fields.picture')} (${$t('neprivaloma')})`"
        :error="form.errors.profile_photo_path"
      >
        <ImageUpload
          v-model:url="form.profile_photo_path"
          v-model:focal-point-value="form.profile_photo_focal_point"
          mode="immediate"
          folder="contacts"
          cropper
          focal-point
          preview-aspect="4/3"
          :existing-url="user?.profile_photo_path"
        />
      </FormFieldWrapper>
    </div>

    <!-- Slaptažodžio keitimas -->
    <FormSection
      v-if="user.has_password"
      :title="$t('Slaptažodžio keitimas')"
      :description="$t('Pakeisk savo prisijungimo slaptažodį. Įvesk dabartinį ir naują slaptažodį.')"
    >
      <FormFieldWrapper
        id="current-password"
        :label="$t('Dabartinis slaptažodis')"
        required
        :error="passwordForm.errors.current_password"
      >
        <Input
          id="current-password"
          v-model="passwordForm.current_password"
          type="password"
          autocomplete="current-password"
          placeholder="Įveskite dabartinį slaptažodį"
          @keydown.enter.prevent="handlePasswordUpdate"
        />
      </FormFieldWrapper>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormFieldWrapper
          id="new-password"
          :label="$t('Naujas slaptažodis')"
          required
          :error="passwordForm.errors.password"
        >
          <Input
            id="new-password"
            v-model="passwordForm.password"
            type="password"
            autocomplete="new-password"
            placeholder="Įveskite naują slaptažodį"
            @keydown.enter.prevent="handlePasswordUpdate"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="password-confirmation"
          :label="$t('Pakartokite naują slaptažodį')"
          required
          :error="passwordForm.errors.password_confirmation"
        >
          <Input
            id="password-confirmation"
            v-model="passwordForm.password_confirmation"
            type="password"
            autocomplete="new-password"
            placeholder="Pakartokite naują slaptažodį"
            @keydown.enter.prevent="handlePasswordUpdate"
          />
        </FormFieldWrapper>
      </div>

      <div class="pt-2">
        <Button
          type="button"
          :disabled="passwordForm.processing"
          variant="outline"
          voice="sentence"
          @click="handlePasswordUpdate"
        >
          <Loader2 v-if="passwordForm.processing" class="size-4 mr-1.5 animate-spin" />
          <Lock v-else class="size-4 mr-1.5" />
          {{ $t('Keisti slaptažodį') }}
        </Button>
      </div>
    </FormSection>

    <template #aside>
      <!-- Įvardžiai ir kreipinys -->
      <FormPanel
        :title="$t('Kreipinys ir įvardžiai')"
        :icon="UserCheck"
        title-class="text-brand"
      >
        <FormFieldWrapper
          id="user-pronouns"
          :label="`${$t('forms.fields.pronouns')} (${$t('neprivaloma')})`"
          :hint="$t('Nurodžius įvardį, pareigybės pavadinimo galūnė keičiasi automatiškai.')"
          :error="form.errors.pronouns || form.errors['pronouns.lt'] || form.errors['pronouns.en']"
        >
          <MultiLocaleInput
            id="user-pronouns"
            v-model:input="form.pronouns"
            :placeholder="{ lt: 'Jie/jų', en: 'They/them' }"
          />
        </FormFieldWrapper>

        <div class="flex items-start gap-2.5 pt-1">
          <Checkbox
            id="user-show-pronouns"
            class="mt-0.5"
            :model-value="Boolean(form.show_pronouns)"
            :disabled="!hasPronouns"
            @update:model-value="form.show_pronouns = $event === true"
          />
          <div class="space-y-0.5">
            <Label for="user-show-pronouns" class="cursor-pointer text-sm font-normal">
              {{ $t('forms.fields.show_pronouns') }}
            </Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('Įvardžiai rodomi prie asmens vardo ir pavardės.') }}
            </p>
          </div>
        </div>
      </FormPanel>

      <!-- Prieinamumas -->
      <FormPanel
        :title="$t('Prieinamumas')"
        :icon="Eye"
        title-class="text-brand"
        flush
      >
        <FormToggleRow
          v-model="animationsEnabled"
          :label="$t('Puslapių animacijos')"
          :hint="$t('Puslapių perėjimo animacijos naršant sistemoje.')"
        />
      </FormPanel>

      <!-- Vadovų nustatymai -->
      <FormPanel
        :title="$t('Vadovų nustatymai')"
        :icon="RefreshCw"
        title-class="text-brand"
      >
        <p class="text-xs text-muted-foreground leading-relaxed">
          {{ $t('Galite iš naujo peržiūrėti interaktyvius vadovus, kurie padeda susipažinti su sistema.') }}
        </p>

        <div class="flex items-center gap-3 pt-1">
          <Button
            type="button"
            :disabled="tutorialResetLoading"
            variant="outline"
            size="sm"
            voice="sentence"
            @click="handleResetTutorials"
          >
            <RefreshCw class="size-3.5 mr-1.5" :class="{ 'animate-spin': tutorialResetLoading }" />
            {{ $t('Atstatyti vadovus') }}
          </Button>
          <span v-if="tutorialResetSuccess" class="text-xs text-status-success font-medium">
            {{ $t('Vadovai atstatyti!') }}
          </span>
        </div>
      </FormPanel>

      <!-- Susiję puslapiai -->
      <FormPanel :title="$t('Susiję puslapiai')" :icon="Settings" title-class="text-brand" flush>
        <div class="divide-y divide-border">
          <Link
            :href="route('profile.notifications')"
            class="group flex items-center justify-between gap-3 px-4 py-3 text-sm transition-colors hover:bg-secondary/40"
          >
            <span class="flex items-center gap-2.5 font-medium text-foreground group-hover:text-brand">
              <Bell class="size-4 text-muted-foreground transition-colors group-hover:text-brand" />
              <span>{{ $t('shell.account.notifications') }}</span>
            </span>
            <ChevronRight class="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
          </Link>

          <Link
            :href="route('profile.roles')"
            class="group flex items-center justify-between gap-3 px-4 py-3 text-sm transition-colors hover:bg-secondary/40"
          >
            <span class="flex items-center gap-2.5 font-medium text-foreground group-hover:text-brand">
              <ShieldCheck class="size-4 text-muted-foreground transition-colors group-hover:text-brand" />
              <span>{{ $t('shell.account.roles') }}</span>
            </span>
            <ChevronRight class="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
          </Link>
        </div>
      </FormPanel>
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Bell,
  ChevronRight,
  Eye,
  Loader2,
  Lock,
  RefreshCw,
  Settings,
  ShieldCheck,
  UserCheck,
} from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { FormPanel, FormSection, FormToggleRow } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ImageUpload } from '@/Components/ui/upload';
import { useApiMutation } from '@/Composables/useApi';
import { resetInitialization } from '@/Composables/useTutorialProgress';
import { ModelEnum } from '@/Types/enums';

const props = defineProps<{
  user: App.Entities.User;
}>();

const userTitle = computed(() => props.user.name || $t('Profilis'));
const userHeadTitle = computed(() => (props.user.name ? `${props.user.name} · ${$t('Profilis')}` : $t('Profilis')));

const tutorialResetLoading = ref(false);
const tutorialResetSuccess = ref(false);

// View transitions / reduced motion preference
const REDUCE_MOTION_KEY = 'vusa-reduce-motion';
const reduceMotion = ref(
  typeof window !== 'undefined'
    ? localStorage.getItem(REDUCE_MOTION_KEY) === 'true'
    : false,
);

const animationsEnabled = computed({
  get: () => !reduceMotion.value,
  set: (enabled: boolean) => {
    const shouldReduce = !enabled;
    reduceMotion.value = shouldReduce;
    if (typeof window !== 'undefined') {
      localStorage.setItem(REDUCE_MOTION_KEY, String(shouldReduce));
      if (shouldReduce) {
        document.documentElement.classList.add('reduce-motion');
      }
      else {
        document.documentElement.classList.remove('reduce-motion');
      }
    }
  },
});

// Initialize class on mount
if (typeof window !== 'undefined' && reduceMotion.value) {
  document.documentElement.classList.add('reduce-motion');
}

const form = useForm({
  name: props.user.name ?? '',
  phone: (props.user.phone ?? null) as string | null,
  facebook_url: (props.user.facebook_url ?? null) as string | null,
  profile_photo_path: (props.user.profile_photo_path ?? null) as string | null,
  profile_photo_focal_point: ((props.user as unknown as Record<string, unknown>).profile_photo_focal_point ?? null) as string | null,
  pronouns: (Array.isArray(props.user.pronouns) || !props.user.pronouns
    ? { lt: '', en: '' }
    : { lt: '', en: '', ...(props.user.pronouns as Record<string, string>) }) as Record<'lt' | 'en', string>,
  show_pronouns: Boolean(props.user.show_pronouns),
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const fieldIds = {
  name: 'user-name',
  phone: 'user-phone',
  facebook_url: 'user-facebook',
  profile_photo_path: 'user-picture',
  pronouns: 'user-pronouns',
  show_pronouns: 'user-show-pronouns',
};

const hasPronouns = computed(() => Boolean(form.pronouns?.lt || form.pronouns?.en));

watch(hasPronouns, (has) => {
  if (!has) {
    form.show_pronouns = false;
  }
});

const handleSubmit = () => {
  form.patch(route('profile.update'), {
    preserveScroll: true,
  });
};

const handlePasswordUpdate = () => {
  passwordForm.patch(route('profile.updatePassword'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset();
    },
  });
};

const handleResetTutorials = async () => {
  tutorialResetLoading.value = true;
  tutorialResetSuccess.value = false;

  try {
    const { execute, isSuccess } = useApiMutation(
      route('api.v1.admin.tutorials.resetAll'),
      'POST',
      {},
      { showSuccessToast: false, showErrorToast: true },
    );

    await execute();

    if (isSuccess.value) {
      tutorialResetSuccess.value = true;

      // Reset the shared tutorial progress state
      resetInitialization();

      // Also clear localStorage (legacy)
      if (typeof window !== 'undefined') {
        localStorage.removeItem('vusa-tutorial-progress');
      }

      // Hide success message after 3 seconds
      setTimeout(() => {
        tutorialResetSuccess.value = false;
      }, 3000);
    }
  }
  finally {
    tutorialResetLoading.value = false;
  }
};
</script>
