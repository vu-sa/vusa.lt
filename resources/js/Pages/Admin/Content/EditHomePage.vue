<template>
  <FormPage
    :title="$t('Redaguoti pagrindinį puslapį')"
    :bar-title="$t('Redaguoti pagrindinį puslapį')"
    :head-title="$t('Redaguoti pagrindinį puslapį')"
    :lead="tenant.shortname"
    :back-href="route('pages.index')"
    :back-label="$t('Puslapiai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    mode="edit"
    :locale
    :available-locales="['lt', 'en']"
    :public-url
    :created-at="content?.created_at"
    :updated-at="content?.updated_at"
    :activity-subject="{ type: 'tenant', id: tenant.id }"
    @update:locale="switchLocale"
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('Turinys')"
      :description="$t('Pagrindinio puslapio informacija')"
    >
      <RichContentFormElement
        v-model="form.parts"
        :tenant-id="tenant.id"
        @save="handleFormSubmit"
      />
    </FormSection>

    <template #aside>
      <FormPanel :title="$t('Padalinys')" :icon="Building2" title-class="text-brand">
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between border-b border-border/60 pb-2">
            <span class="text-muted-foreground">{{ $t('Pavadinimas') }}</span>
            <span class="font-medium text-foreground">{{ tenant.fullname }}</span>
          </div>
          <div class="flex items-center justify-between border-b border-border/60 pb-2">
            <span class="text-muted-foreground">{{ $t('Trumpinys') }}</span>
            <span class="font-medium text-foreground">{{ tenant.shortname }}</span>
          </div>
          <div v-if="tenant.alias" class="flex items-center justify-between">
            <span class="text-muted-foreground">{{ $t('Svetainės adresas') }}</span>
            <span class="font-mono text-xs text-foreground">{{ subdomain }}.vusa.lt</span>
          </div>
        </div>
      </FormPanel>
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2 } from 'lucide-vue-next';
import { computed } from 'vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import RichContentFormElement from '@/Components/RichContent/RichContentFormElement.vue';

const props = defineProps<{
  tenant: App.Entities.Tenant;
  content: App.Entities.Content | null;
  locale: 'lt' | 'en';
}>();

const subdomain = computed(() => (props.tenant.alias === 'vusa' ? 'www' : props.tenant.alias));
const publicUrl = computed(() => {
  try {
    return route('home', { subdomain: subdomain.value, lang: props.locale });
  }
  catch {
    return undefined;
  }
});

const form = useForm({
  locale: props.locale,
  parts: props.content?.parts ?? [],
});

function switchLocale(nextLocale: string): void {
  if (!nextLocale || nextLocale === props.locale) {
    return;
  }

  form.defaults();
  router.get(route('tenants.editMainPage', props.tenant.id), { locale: nextLocale }, { preserveScroll: true });
}

function handleFormSubmit(): void {
  form.defaults();
  form.post(route('tenants.updateMainPage', props.tenant.id), {
    preserveScroll: true,
    forceFormData: true,
  });
}
</script>
