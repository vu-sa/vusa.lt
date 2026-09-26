<template>
  <FormPage
    :title="$t('settings.pages.site.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('settings.site_settings.privacy_page_title')"
      :description="$t('settings.site_settings.privacy_page_description')"
    >
      <FormFieldWrapper
        v-for="locale in LOCALES"
        :id="`privacy_page_id_${locale}`"
        :key="locale"
        :label="`${$t('settings.site_settings.privacy_page_label')} · ${locale.toUpperCase()}`"
        :error="form.errors[`privacy_page_id_${locale}`]"
      >
        <CollectionSelectDialog
          v-model:open="dialogOpen[locale]"
          collection="pages"
          allow-empty
          :base-filter-by="baseFilterBy(locale)"
          :initial-hits="initialHits(selected[locale])"
          :title="$t('settings.site_settings.privacy_page_label')"
          :confirm-label="$t('Pasirinkti')"
          :search-placeholder="$t('settings.site_settings.privacy_page_search_placeholder')"
          :empty-message="$t('settings.site_settings.privacy_page_empty')"
          @confirm="hits => onConfirm(locale, hits)"
        >
          <template #trigger>
            <Button :id="`privacy_page_id_${locale}`" type="button" variant="outline" class="w-full justify-between font-normal">
              <span class="truncate" :class="{ 'text-muted-foreground': !selected[locale] }">
                {{ triggerLabel(selected[locale]) }}
              </span>
              <ChevronDown class="size-4 opacity-50" />
            </Button>
          </template>
        </CollectionSelectDialog>
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

interface SelectedPage {
  id: string;
  title: string;
  lang: string;
  tenant_shortname?: string | null;
}

type LocaleCode = 'lt' | 'en';

const props = defineProps<{
  selectedPages: { lt: SelectedPage | null; en: SelectedPage | null };
}>();

const LOCALES: LocaleCode[] = ['lt', 'en'];

const selected = reactive<{ lt: SelectedPage | null; en: SelectedPage | null }>({
  lt: props.selectedPages.lt,
  en: props.selectedPages.en,
});

const dialogOpen = reactive<{ lt: boolean; en: boolean }>({ lt: false, en: false });

// The form seeds from the summaries, not the raw setting ids: a configured page that has
// since been deactivated or deleted shows as "not set" and saving clears the stale id.
const form = useForm({
  privacy_page_id_lt: props.selectedPages.lt?.id ?? null,
  privacy_page_id_en: props.selectedPages.en?.id ?? null,
});

const baseFilterBy = (locale: LocaleCode): string => `is_active:=true && lang:=${locale}`;

const initialHits = (page: SelectedPage | null): NormalizedSearchHit[] => page
  ? [normalizeHit('pages', {
      id: page.id,
      title: page.title,
      tenant_name: page.tenant_shortname,
      lang: page.lang,
    })]
  : [];

const triggerLabel = (page: SelectedPage | null): string => page
  ? `${page.title}${page.tenant_shortname ? ` (${page.tenant_shortname})` : ''}`
  : $t('settings.site_settings.privacy_page_placeholder');

function onConfirm(locale: LocaleCode, hits: NormalizedSearchHit[]): void {
  selected[locale] = hits[0]
    ? { id: hits[0].recordId, title: hits[0].title, lang: locale, tenant_shortname: hits[0].badge }
    : null;
  form[`privacy_page_id_${locale}`] = hits[0]?.recordId ?? null;
}

const handleFormSubmit = () => {
  form.post(route('settings.site.update'));
};
</script>
