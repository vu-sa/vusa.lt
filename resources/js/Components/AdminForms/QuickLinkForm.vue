<template>
  <FormPage
    :title="isCreate ? $t('Nauja greitoji nuoroda') : (form.text || $t('Greitoji nuoroda'))"
    :bar-title
    entity-type="quick_link"
    :required-note="true"
    :back-href="route('quickLinks.index')"
    :back-label="$t('Greitosios nuorodos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :activity-subject="quickLink?.id ? { type: 'quick_link', id: quickLink.id } : undefined"
    :created-at="isCreate ? undefined : quickLink?.created_at"
    :updated-at="isCreate ? undefined : quickLink?.updated_at"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate && form.is_important" #title-status>
      <StatusBadge :status="importantStatus" />
    </template>

    <div class="space-y-6">
      <FormFieldWrapper
        id="text"
        :label="$t('Mygtuko tekstas')"
        required
        :error="form.errors.text"
      >
        <Input
          id="text"
          v-model="form.text"
          type="text"
          :placeholder="$t('Įrašyti tekstą...')"
          :class="['h-11', fieldSurfaceClass]"
        />
      </FormFieldWrapper>

      <FormFieldWrapper id="icon" :label="$t('Ikona')" :hint="$t('Piktograma, rodoma šalia nuorodos teksto.')">
        <Suspense>
          <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
        </Suspense>
      </FormFieldWrapper>

      <LinkTargetField
        :topic-options
        :dialog-title="$t('Pasirink objektą')"
        @select="url => form.link = url"
      />

      <FormFieldWrapper
        id="link"
        :label="$t('Nuoroda')"
        required
        :error="form.errors.link"
        :hint="$t('navigation.form.link_target_manual')"
      >
        <div class="flex gap-1.5">
          <Input
            id="link"
            v-model="form.link"
            type="text"
            :placeholder="$t('Nuoroda...')"
            :class="['h-11', fieldSurfaceClass]"
          />
          <Button
            v-if="form.link"
            variant="outline"
            size="icon"
            as-child
            class="size-11 shrink-0"
          >
            <a :href="safeHref(form.link)" target="_blank" rel="noopener noreferrer" :aria-label="$t('Atidaryti nuorodą')">
              <ExternalLink class="size-4" />
            </a>
          </Button>
        </div>
      </FormFieldWrapper>
    </div>

    <template #aside>
      <FormPanel :title="$t('Kalba')" :icon="Languages">
        <FormFieldWrapper
          id="lang"
          :label="$t('Kalba')"
          required
          :error="form.errors.lang"
        >
          <FormSegmentedControl
            v-model="form.lang"
            :options="langOptions"
            :label="$t('Kalba')"
          >
            <template #option="{ option }">
              <LocaleFlag :locale="option.value" />
            </template>
          </FormSegmentedControl>
        </FormFieldWrapper>
      </FormPanel>

      <FormPanel :title="$t('Rodymo nustatymai')" :icon="LayoutGrid" flush>
        <div class="p-4">
          <TenantSelectField
            v-model="form.tenant_id"
            :tenants="tenantList"
            :error="form.errors.tenant_id"
          />
        </div>

        <FormToggleRow
          v-model="form.is_important"
          :label="$t('Svarbi nuoroda')"
          :hint="$t('Paryškinama pradiniame puslapyje.')"
          data-testid="toggle-important"
        />
      </FormPanel>
    </template>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <FormDangerZone
        :label="$t('Ištrinti greitąją nuorodą')"
        :title="$t('Ištrinti greitąją nuorodą?')"
        :description="$t('Ar tikrai nori perkelti šią greitąją nuorodą į šiukšliadėžę?')"
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { ExternalLink, Languages, LayoutGrid, Star } from '@lucide/vue';

import FluentIconSelect from '@/Components/AdminForms/FluentIconSelect.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import LinkTargetField, { type LinkTargetTopicOption } from '@/Components/AdminForms/LinkTargetField.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { FormDangerZone, FormPanel, FormSegmentedControl, FormToggleRow, StatusBadge, type FormSegmentOption } from '@/Components/Patterns';
import LocaleFlag from '@/Components/Shared/LocaleFlag.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import type { StatusPresentation } from '@/Constants/statusIcons';
import { safeHref } from '@/Utils/safeHref';

const props = withDefaults(defineProps<{
  quickLink: App.Entities.QuickLink;
  tenantOptions: Record<string, unknown>[];
  topicOptions?: LinkTargetTopicOption[];
  rememberKey?: string;
  enableDelete?: boolean;
}>(), {
  topicOptions: () => [],
  rememberKey: undefined,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => !props.quickLink?.id || props.rememberKey === 'CreateQuickLink');

const form = props.rememberKey
  ? useForm(props.rememberKey, props.quickLink)
  : useForm(props.quickLink);

const fieldIds = ['text', 'icon', 'link', 'lang', 'tenant_id', 'is_important'];

const barTitle = computed(() =>
  isCreate.value ? $t('Nauja greitoji nuoroda') : (props.quickLink.text?.trim() || $t('Greitoji nuoroda')),
);

const importantStatus: StatusPresentation = {
  label: $t('Svarbi'),
  role: 'attention',
  icon: Star,
};

const langOptions: FormSegmentOption<'lt' | 'en'>[] = [
  { value: 'lt', label: 'Lietuvių' },
  { value: 'en', label: 'English' },
];

const tenantList = computed(() => props.tenantOptions as unknown as App.Entities.Tenant[]);

defineExpose({
  form,
});
</script>
