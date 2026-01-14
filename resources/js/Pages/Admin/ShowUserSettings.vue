<template>
  <PageContent :title="`${$page.props.auth?.user?.name}`">
    <ThemeProvider>
      <NCard>
        <!-- <p>{{ salutation }}</p> -->
        <div class="mb-4">
          <NForm :model="form">
          <FormElement>
            <template #title>
              {{ $t("Nustatymai") }}
            </template>
            <template #description>
              Kitus nustatymus gali tvarkyti komunikacijos ir atstovų koordinatoriai.
            </template>
            <NFormItem :label="$t('forms.fields.name_and_surname')">
              <div class="flex grow flex-col gap-1">
                <NInput v-model:value="form.name" :disabled="user.name_was_changed" />
                <InfoText v-if="!user.name_was_changed">
                  Paskyros vardą galima pakeisti tik VIENĄ kartą!
                </InfoText>
              </div>
            </NFormItem>
            <div class="grid gap-4 lg:grid-cols-2">
              <NFormItem :label="$t('forms.fields.phone')">
                <NInput v-model:value="form.phone" placeholder="+370 612 34 567" />
              </NFormItem>
              <NFormItem :label="$t('forms.fields.facebook_url')">
                <NInput v-model:value="form.facebook_url" placeholder="https://www.facebook.com/..." />
              </NFormItem>
            </div>
            <NFormItem :label="$t('forms.fields.picture')">
              <UploadImageWithCropper v-model:url="form.profile_photo_path" folder="contacts" />
            </NFormItem>

            <div class="grid gap-4 lg:grid-cols-2">
              <NFormItem :label="$t('forms.fields.pronouns')">
                <MultiLocaleInput v-model:input="form.pronouns" :placeholder="{ lt: 'Jie/jų', en: 'They/them' }" />
              </NFormItem>
              <NFormItem :label="$t('forms.fields.show_pronouns')">
                <NSwitch v-model:value="form.show_pronouns" :disabled="form.pronouns === ''">
                  <template #checked>
                    <span>Įvardžiai rodomi viešai</span>
                  </template>
                  <template #unchecked>
                    <span>Įvardžiai nerodomi viešai</span>
                  </template>
                </NSwitch>
              </NFormItem>
            </div>
            <Button :disabled="loading" variant="default" @click="handleSubmit">
              <IMdiContentSave />
              {{ $t("Išsaugoti") }}
            </Button>
          </FormElement>

          <!-- Password Change Section -->
          <FormElement v-if="user.has_password">
            <template #title>
              {{ $t("Slaptažodžio keitimas") }}
            </template>
            <template #description>
              Jūs galite pakeisti savo slaptažodį čia. Įveskite dabartinį slaptažodį ir naują slaptažodį.
            </template>
            <NForm :model="passwordForm">
              <NFormItem :label="$t('Dabartinis slaptažodis')" required>
                <NInput 
                  v-model:value="passwordForm.current_password" 
                  type="password" 
                  placeholder="Įveskite dabartinį slaptažodį" 
                />
              </NFormItem>
              <NFormItem :label="$t('Naujas slaptažodis')" required>
                <NInput 
                  v-model:value="passwordForm.password" 
                  type="password" 
                  placeholder="Įveskite naują slaptažodį" 
                />
              </NFormItem>
              <NFormItem :label="$t('Pakartokite naują slaptažodį')" required>
                <NInput 
                  v-model:value="passwordForm.password_confirmation" 
                  type="password" 
                  placeholder="Pakartokite naują slaptažodį" 
                />
              </NFormItem>
              <Button :disabled="passwordLoading" variant="default" @click="handlePasswordUpdate">
                <IMdiLock />
                {{ $t("Keisti slaptažodį") }}
              </Button>
            </NForm>
          </FormElement>

          <!-- Tutorial Settings Section -->
          <FormElement>
            <template #title>
              {{ $t("Vadovų nustatymai") }}
            </template>
            <template #description>
              {{ $t("Galite iš naujo peržiūrėti interaktyvius vadovus, kurie padeda susipažinti su sistema.") }}
            </template>
            <div class="flex items-center gap-4">
              <Button 
                :disabled="tutorialResetLoading" 
                variant="outline" 
                @click="handleResetTutorials"
              >
                <IMdiRefresh />
                {{ $t("Atstatyti vadovus") }}
              </Button>
              <span v-if="tutorialResetSuccess" class="text-sm text-green-600 dark:text-green-400">
                {{ $t("Vadovai atstatyti!") }}
              </span>
            </div>
          </FormElement>

          <!-- Accessibility Settings Section -->
          <FormElement>
            <template #title>
              {{ $t("Prieinamumas") }}
            </template>
            <template #description>
              {{ $t("Nustatymai, padedantys pritaikyti sistemą pagal jūsų poreikius.") }}
            </template>
            <NFormItem :label="$t('Išjungti puslapių perėjimo animacijas')">
              <NSwitch v-model:value="reduceMotion" @update:value="handleReduceMotionChange">
                <template #checked>
                  <span>{{ $t('Animacijos išjungtos') }}</span>
                </template>
                <template #unchecked>
                  <span>{{ $t('Animacijos įjungtos') }}</span>
                </template>
              </NSwitch>
            </NFormItem>
            <p class="text-sm text-muted-foreground">
              {{ $t('Šį nustatymą taip pat galima valdyti operacinės sistemos prieinamumo nustatymuose ("Reduce motion").') }}
            </p>
          </FormElement>

          <!-- Push Notifications Settings Section -->
          <FormElement>
            <template #title>
              {{ $t("Push pranešimai") }}
            </template>
            <template #description>
              {{ $t("Gaukite pranešimus net kai naršyklė uždaryta. Veikia tik įdiegus programėlę (PWA).") }}
            </template>
            <div class="space-y-4">
              <div class="flex items-center gap-4">
                <template v-if="!hasPushSubscription && canSubscribeToPush">
                  <Button 
                    :disabled="isSubscribingToPush" 
                    variant="outline" 
                    @click="handleSubscribeToPush"
                  >
                    <IMdiBellPlus v-if="!isSubscribingToPush" />
                    <IMdiLoading v-else class="animate-spin" />
                    {{ $t("Įjungti push pranešimus") }}
                  </Button>
                </template>
                <template v-else-if="hasPushSubscription">
                  <Button 
                    variant="outline" 
                    @click="handleUnsubscribeFromPush"
                  >
                    <IMdiBellOff />
                    {{ $t("Išjungti push pranešimus") }}
                  </Button>
                  <Button 
                    :disabled="testNotificationLoading" 
                    variant="secondary" 
                    @click="handleSendTestNotification"
                  >
                    <IMdiBellRing v-if="!testNotificationLoading" />
                    <IMdiLoading v-else class="animate-spin" />
                    {{ $t("Siųsti bandomąjį pranešimą") }}
                  </Button>
                </template>
                <template v-else-if="pushPermission === 'denied'">
                  <p class="text-sm text-destructive">
                    {{ $t("Push pranešimai užblokuoti naršyklės nustatymuose. Atblokuokite juos norėdami gauti pranešimus.") }}
                  </p>
                </template>
                <template v-else-if="!pushSupported">
                  <p class="text-sm text-muted-foreground">
                    {{ $t("Jūsų naršyklė nepalaiko push pranešimų.") }}
                  </p>
                </template>
              </div>
              <p v-if="testNotificationSuccess" class="text-sm text-green-600 dark:text-green-400">
                {{ $t("Bandomasis pranešimas išsiųstas!") }}
              </p>
            </div>
          </FormElement>

          <h2>{{ $t("Tavo rolės") }}</h2>
          <ul class="list-inside">
            <li v-for="(role, index) in user.roles" :key="role.id">
              <strong>{{ $t(role.name) }}</strong>
            </li>
            <template v-for="duty in user.current_duties">
              <li v-for="role in duty.roles" :key="role.id">
                <strong>{{ $t(role.name) }}</strong> ({{
                  `iš pareigybės „${duty.name}“, kuri yra iš ${duty.institution?.tenant?.shortname ?? "nežinomo\
                padalinio"}` }})
              </li>
            </template>
          </ul>
          </nform>
        </div>
      </NCard>
      <p class="mt-4 flex items-center justify-center gap-2">
        <a class="inline-flex items-center" target="_blank" href="https://github.com/vu-sa/vusa.lt/">
          <Button variant="link">
            <IMdiGithub />
            {{ $t("Projekto puslapis") }}
          </Button>
        </a>
      </p>
    </ThemeProvider>
  </PageContent>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ref, computed } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UploadImageWithCropper from "@/Components/Buttons/UploadImageWithCropper.vue";
import InfoText from "@/Components/SmallElements/InfoText.vue";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import ThemeProvider from "@/Components/Providers/ThemeProvider.vue";
import IMdiContentSave from '~icons/mdi/content-save';
import IMdiGithub from '~icons/mdi/github';
import IMdiLock from '~icons/mdi/lock';
import IMdiRefresh from '~icons/mdi/refresh';
import IMdiSettings from '~icons/mdi/settings';
import IMdiBellPlus from '~icons/mdi/bell-plus';
import IMdiBellOff from '~icons/mdi/bell-off';
import IMdiBellRing from '~icons/mdi/bell-ring';
import IMdiLoading from '~icons/mdi/loading';
import { usePWA } from '@/Composables/usePWA';

const props = defineProps<{
  user: App.Entities.User;
}>();

const loading = ref(false);
const passwordLoading = ref(false);
const tutorialResetLoading = ref(false);
const tutorialResetSuccess = ref(false);
const testNotificationLoading = ref(false);
const testNotificationSuccess = ref(false);

// PWA push notification state
const { 
  pushSupported, 
  pushPermission, 
  canSubscribeToPush, 
  hasPushSubscription, 
  isSubscribingToPush,
  subscribeToPush, 
  unsubscribeFromPush 
} = usePWA();

const handleSubscribeToPush = async () => {
  await subscribeToPush();
};

const handleUnsubscribeFromPush = async () => {
  await unsubscribeFromPush();
};

const handleSendTestNotification = async () => {
  testNotificationLoading.value = true;
  testNotificationSuccess.value = false;
  
  try {
    const page = usePage();
    const csrfToken = (page.props.csrf_token as string) || '';
    
    const response = await fetch(route('push-subscription.test'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    
    if (response.ok) {
      testNotificationSuccess.value = true;
      setTimeout(() => {
        testNotificationSuccess.value = false;
      }, 5000);
    }
  } catch (error) {
    console.error('Failed to send test notification:', error);
  } finally {
    testNotificationLoading.value = false;
  }
};

// View transitions / reduced motion preference
const REDUCE_MOTION_KEY = 'vusa-reduce-motion';
const reduceMotion = ref(
  typeof window !== 'undefined' 
    ? localStorage.getItem(REDUCE_MOTION_KEY) === 'true'
    : false
);

const handleReduceMotionChange = (value: boolean) => {
  localStorage.setItem(REDUCE_MOTION_KEY, String(value));
  // Also toggle a class on documentElement for CSS-based disabling
  if (value) {
    document.documentElement.classList.add('reduce-motion');
  } else {
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
  { label: $t('Nustatymai'), icon: IMdiSettings }
]);

const handleSubmit = () => {
  loading.value = true;
  form.patch(route("profile.update", props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false;
    },
  });
};

const handlePasswordUpdate = () => {
  passwordLoading.value = true;
  passwordForm.patch(route("profile.updatePassword"), {
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

const handleResetTutorials = () => {
  tutorialResetLoading.value = true;
  tutorialResetSuccess.value = false;
  
  router.post(route("tutorials.resetAll"), {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      tutorialResetLoading.value = false;
      tutorialResetSuccess.value = true;
      
      // Also clear localStorage
      if (typeof window !== 'undefined') {
        localStorage.removeItem('vusa-tutorial-progress');
      }
      
      // Hide success message after 3 seconds
      setTimeout(() => {
        tutorialResetSuccess.value = false;
      }, 3000);
    },
    onError: () => {
      tutorialResetLoading.value = false;
    },
  });
};
</script>
