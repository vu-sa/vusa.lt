<template>
  <FormPage
    :title="isCreate ? $t('Naujas baneris') : (form.title || $t('Baneris'))"
    :bar-title
    entity-type="banner"
    :back-href="route('banners.index')"
    :back-label="$t('Baneriai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :activity-subject="banner?.id ? { type: 'banner', id: banner.id } : undefined"
    :created-at="isCreate ? undefined : (banner?.created_at as string | undefined)"
    :updated-at="isCreate ? undefined : (banner?.updated_at as string | undefined)"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate" #title-status>
      <StatusBadge :status="currentStatusPresentation" />
    </template>

    <div class="space-y-6">
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
          :class="['h-11', fieldSurfaceClass]"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="link_url"
        :label="$t('forms.fields.banner_link_url')"
        :hint="$t('Nuoroda, kuri bus atidaroma paspaudus ant banerio.')"
        :error="form.errors.link_url"
      >
        <div class="flex gap-1.5">
          <Input
            id="link_url"
            v-model="form.link_url"
            type="text"
            placeholder="https://vu.lt"
            :class="['h-11', fieldSurfaceClass]"
          />
          <Button
            v-if="form.link_url"
            variant="outline"
            size="icon"
            as-child
            class="size-11 shrink-0"
          >
            <a :href="form.link_url" target="_blank" rel="noopener noreferrer" :aria-label="$t('Atidaryti nuorodą')">
              <ExternalLink class="size-4" />
            </a>
          </Button>
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
          full-width
          :existing-url="typeof banner?.image_url === 'string' ? banner.image_url : undefined"
        />
      </FormFieldWrapper>
    </div>

    <template #aside>
      <ContentPublishPanel
        :published="Boolean(form.is_active)"
        hide-publish-time
        :callout="statusCallout"
        test-id-prefix="banner"
        @update:published="form.is_active = $event ? 1 : 0"
      />
    </template>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <Button
        type="button"
        variant="outline"
        class="border-destructive/40 text-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="isDeleteDialogOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Ištrinti banerį') }}
      </Button>
    </template>
  </FormPage>

  <ConfirmDialog
    v-model:open="isDeleteDialogOpen"
    :title="$t('Ištrinti banerį?')"
    :description="$t('Ar tikrai norite perkelti šį banerį į šiukšliadėžę?')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="emit('delete')"
  />
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { ExternalLink, Trash2 } from 'lucide-vue-next';

import ContentPublishPanel from '@/Components/AdminForms/ContentPublishPanel.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { ImageUpload } from '@/Components/ui/upload';
import { contentStatuses, type StatusPresentation } from '@/Constants/statuses';

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

const fieldIds = ['title', 'link_url', 'is_active', 'image_url'];

const barTitle = computed(() =>
  form.title?.trim() || (isCreate.value ? $t('Naujas baneris') : $t('Baneris')),
);

const currentStatusPresentation = computed<StatusPresentation>(() =>
  form.is_active ? contentStatuses.published : contentStatuses.draft,
);

const statusCallout = computed(() =>
  form.is_active
    ? $t('Aktyvus baneris rodomas viešoje svetainėje.')
    : $t('Neaktyvus baneris paslėptas nuo lankytojų.'),
);

defineExpose({
  form,
});
</script>
