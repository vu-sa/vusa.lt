<template>
  <FormPage
    :title="isCreate ? $t('Naujas baneris') : (form.title || $t('Baneris'))"
    :head-title="isCreate ? $t('Naujas baneris') : (form.title || $t('Baneris'))"
    :back-href="route('banners.index')"
    :back-label="$t('Baneriai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :mode="isCreate ? 'create' : 'edit'"
    max-width="2xl"
    @submit="$emit('submit:form', form)"
  >
    <template v-if="!isCreate" #header-actions>
      <Button v-if="form.link_url" as-child variant="outline" size="sm">
        <a :href="form.link_url" target="_blank" rel="noopener noreferrer">
          <ExternalLink class="mr-1.5 size-4" />
          {{ $t('Atidaryti nuorodą') }}
        </a>
      </Button>
      <ActivityLogSheet v-if="banner?.id" subject-type="banner" :subject-id="String(banner.id)" />
    </template>

    <FormSection
      :title="$t('forms.fields.title')"
      :description="$t('Pagrindiniai banerio nustatymai ir nuoroda')"
    >
      <div class="space-y-4">
        <FormFieldWrapper
          id="title"
          :label="$t('forms.fields.title')"
          required
          :error="form.errors.title"
        >
          <Input
            id="title"
            v-model="form.title"
            type="text"
            :placeholder="$t('forms.placeholders.enter_title')"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="link_url"
          :label="$t('forms.fields.banner_link_url')"
          :error="form.errors.link_url"
        >
          <Input
            id="link_url"
            v-model="form.link_url"
            type="text"
            placeholder="https://vu.lt"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="is_active"
          :label="$t('forms.fields.is_active')"
          :hint="$t('Ar baneris šiuo metu rodomas viešoje svetainėje')"
        >
          <div class="flex items-center gap-3">
            <Switch
              id="is_active"
              :model-value="!!form.is_active"
              @update:model-value="val => form.is_active = val ? 1 : 0"
            />
            <span class="text-sm font-medium">
              {{ form.is_active ? $t('Aktyvus') : $t('Neaktyvus') }}
            </span>
          </div>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="image_url"
          :label="$t('forms.fields.banner_logo')"
          :hint="$t('forms.fields.banner_logo_help')"
          :error="form.errors.image_url"
        >
          <ImageUpload
            v-model:url="form.image_url"
            mode="immediate"
            folder="banners"
            cropper
            :existing-url="banner?.image_url"
          />
        </FormFieldWrapper>
      </div>
    </FormSection>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <div class="flex items-center justify-between border border-destructive/20 bg-destructive/5 p-4">
        <div>
          <h3 class="text-sm font-semibold text-destructive">
            {{ $t('Ištrinti banerį') }}
          </h3>
          <p class="text-xs text-muted-foreground">
            {{ $t('Baneris bus perkeltas į šiukšliadėžę.') }}
          </p>
        </div>
        <Button variant="destructive" size="sm" type="button" @click="isDeleteDialogOpen = true">
          {{ $t('Ištrinti') }}
        </Button>
      </div>
    </template>
  </FormPage>

  <ConfirmDialog
    :open="isDeleteDialogOpen"
    :title="$t('Ištrinti banerį?')"
    :description="$t('Ar tikrai norite perkelti šį banerį į šiukšliadėžę?')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="isDeleteDialogOpen = $event"
    @confirm="emit('delete')"
  />
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ExternalLink } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { ImageUpload } from '@/Components/ui/upload';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';

const props = withDefaults(defineProps<{
  banner: Partial<App.Entities.Banner> & Record<string, unknown>;
  rememberKey?: string;
  enableDelete?: boolean;
}>(), {
  rememberKey: undefined,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => !props.banner?.id || props.rememberKey === 'CreateBanner');
const isDeleteDialogOpen = ref(false);

const form = props.rememberKey
  ? useForm(props.rememberKey, props.banner as Record<string, unknown>)
  : useForm(props.banner as Record<string, unknown>);

defineExpose({
  form,
});
</script>
