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
import { router, useForm } from "@inertiajs/vue3";

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

const props = defineProps<{
  user: App.Entities.User;
}>();

const loading = ref(false);
const passwordLoading = ref(false);
const tutorialResetLoading = ref(false);
const tutorialResetSuccess = ref(false);

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
