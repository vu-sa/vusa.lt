<template>
  <OverviewPage :title="$t('Profilis')">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
      <SectionCard :title="$t('Nustatymai')">
        <p class="mb-4 text-sm text-muted-foreground">
          Kitus nustatymus gali tvarkyti komunikacijos ir atstovų koordinatoriai.
        </p>
        <FormFieldWrapper id="name" :label="$t('forms.fields.name_and_surname')">
          <div class="flex grow flex-col gap-1">
            <Input v-model="form.name" :disabled="user.name_was_changed" />
            <InfoText v-if="!user.name_was_changed">
              Paskyros vardą galima pakeisti tik VIENĄ kartą!
            </InfoText>
          </div>
        </FormFieldWrapper>
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="phone" :label="$t('forms.fields.phone')">
            <Input v-model="form.phone" placeholder="+370 612 34 567" />
          </FormFieldWrapper>
          <FormFieldWrapper id="facebook_url" :label="$t('forms.fields.facebook_url')">
            <Input v-model="form.facebook_url" placeholder="https://www.facebook.com/..." />
          </FormFieldWrapper>
        </div>
        <FormFieldWrapper id="picture" :label="$t('forms.fields.picture')">
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

        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="pronouns" :label="$t('forms.fields.pronouns')">
            <MultiLocaleInput v-model:input="form.pronouns" :placeholder="{ lt: 'Jie/jų', en: 'They/them' }" />
          </FormFieldWrapper>
          <FormFieldWrapper id="show_pronouns" :label="$t('forms.fields.show_pronouns')">
            <div class="flex items-center gap-2">
              <Switch :model-value="form.show_pronouns" :disabled="form.pronouns === ''" @update:model-value="val => form.show_pronouns = val" />
              <span class="text-sm text-muted-foreground">{{ form.show_pronouns ? 'Įvardžiai rodomi viešai' : 'Įvardžiai nerodomi viešai' }}</span>
            </div>
          </FormFieldWrapper>
        </div>
        <Button :disabled="loading" variant="default" @click="handleSubmit">
          <Save />
          {{ $t("Išsaugoti") }}
        </Button>
      </SectionCard>

      <SectionCard v-if="user.has_password" :title="$t('Slaptažodžio keitimas')">
        <p class="mb-4 text-sm text-muted-foreground">
          Jūs galite pakeisti savo slaptažodį čia. Įveskite dabartinį slaptažodį ir naują slaptažodį.
        </p>
        <FormFieldWrapper id="current_password" :label="$t('Dabartinis slaptažodis')" required>
          <Input
            v-model="passwordForm.current_password"
            type="password"
            placeholder="Įveskite dabartinį slaptažodį"
          />
        </FormFieldWrapper>
        <FormFieldWrapper id="password" :label="$t('Naujas slaptažodis')" required>
          <Input
            v-model="passwordForm.password"
            type="password"
            placeholder="Įveskite naują slaptažodį"
          />
        </FormFieldWrapper>
        <FormFieldWrapper id="password_confirmation" :label="$t('Pakartokite naują slaptažodį')" required>
          <Input
            v-model="passwordForm.password_confirmation"
            type="password"
            placeholder="Pakartokite naują slaptažodį"
          />
        </FormFieldWrapper>
        <Button :disabled="passwordLoading" variant="default" @click="handlePasswordUpdate">
          <Lock />
          {{ $t("Keisti slaptažodį") }}
        </Button>
      </SectionCard>

      <SectionCard :title="$t('Vadovų nustatymai')">
        <p class="mb-4 text-sm text-muted-foreground">
          {{ $t("Galite iš naujo peržiūrėti interaktyvius vadovus, kurie padeda susipažinti su sistema.") }}
        </p>
        <div class="flex items-center gap-4">
          <Button
            :disabled="tutorialResetLoading"
            variant="outline"
            @click="handleResetTutorials"
          >
            <RefreshCw />
            {{ $t("Atstatyti vadovus") }}
          </Button>
          <span v-if="tutorialResetSuccess" class="text-sm text-status-success">
            {{ $t("Vadovai atstatyti!") }}
          </span>
        </div>
      </SectionCard>

      <SectionCard :title="$t('Prieinamumas')">
        <p class="mb-4 text-sm text-muted-foreground">
          {{ $t("Nustatymai, padedantys pritaikyti sistemą pagal jūsų poreikius.") }}
        </p>
        <FormFieldWrapper id="reduce_motion" :label="$t('Išjungti puslapių perėjimo animacijas')">
          <div class="flex items-center gap-2">
            <Switch :model-value="reduceMotion" @update:model-value="handleReduceMotionChange" />
            <span class="text-sm text-muted-foreground">{{ reduceMotion ? $t('Animacijos išjungtos') : $t('Animacijos įjungtos') }}</span>
          </div>
        </FormFieldWrapper>
        <p class="text-sm text-muted-foreground">
          {{ $t('Šį nustatymą taip pat galima valdyti operacinės sistemos prieinamumo nustatymuose ("Reduce motion").') }}
        </p>
      </SectionCard>

      <div class="grid gap-3 sm:grid-cols-2">
        <EntityLinkCard
          :href="route('profile.notifications')"
          :icon="Bell"
          :title="$t('shell.account.notifications')"
        />
        <EntityLinkCard
          :href="route('profile.roles')"
          :icon="ShieldCheck"
          :title="$t('shell.account.roles')"
        />
      </div>
    </div>
  </OverviewPage>
</template>

<script setup lang="tsx">
import { trans as $t } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Bell, Lock, RefreshCw, Save, Settings, ShieldCheck } from 'lucide-vue-next';

import { useApiMutation } from '@/Composables/useApi';
import { resetInitialization } from '@/Composables/useTutorialProgress';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import { EntityLinkCard, SectionCard } from '@/Components/Patterns';
import { ImageUpload } from '@/Components/ui/upload';
import InfoText from '@/Components/SmallElements/InfoText.vue';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  user: App.Entities.User;
}>();

const loading = ref(false);
const passwordLoading = ref(false);
const tutorialResetLoading = ref(false);
const tutorialResetSuccess = ref(false);

// View transitions / reduced motion preference
const REDUCE_MOTION_KEY = 'vusa-reduce-motion';
const reduceMotion = ref(
  typeof window !== 'undefined'
    ? localStorage.getItem(REDUCE_MOTION_KEY) === 'true'
    : false,
);

const handleReduceMotionChange = (value: boolean) => {
  reduceMotion.value = value;
  localStorage.setItem(REDUCE_MOTION_KEY, String(value));
  // Also toggle a class on documentElement for CSS-based disabling
  if (value) {
    document.documentElement.classList.add('reduce-motion');
  }
  else {
    document.documentElement.classList.remove('reduce-motion');
  }
};

// Initialize class on mount
if (typeof window !== 'undefined' && reduceMotion.value) {
  document.documentElement.classList.add('reduce-motion');
}

const form = useForm({
  name: props.user.name,
  phone: props.user.phone,
  facebook_url: props.user.facebook_url,
  picture: props.user.profile_photo_path,
  profile_photo_path: props.user.profile_photo_path,
  profile_photo_focal_point: props.user.profile_photo_focal_point,
  pronouns: props.user.pronouns,
  show_pronouns: props.user.show_pronouns,
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs([
  { label: $t('Profilis'), icon: Settings },
]);

const handleSubmit = () => {
  loading.value = true;
  form.patch(route('profile.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false;
    },
  });
};

const handlePasswordUpdate = () => {
  passwordLoading.value = true;
  passwordForm.patch(route('profile.updatePassword'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordLoading.value = false;
      passwordForm.reset();
    },
    onError: () => {
      passwordLoading.value = false;
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
