<template>
  <FormPage
    :title="isCreate ? $t('Nauja naujiena') : (form.title || $t('Naujiena'))"
    :bar-title
    entity-type="news"
    :back-href="route('news.index')"
    :back-label="$t('Naujienos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :available-locales="[]"
    :public-url="publicNewsUrl"
    :activity-subject="news?.id ? { type: 'news', id: news.id } : undefined"
    :created-at="isCreate ? undefined : news?.created_at"
    :updated-at="isCreate ? undefined : news?.updated_at"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate && news" #title-status>
      <StatusBadge :status="news.draft ? contentStatuses.draft : contentStatuses.published" />
    </template>

    <FormFieldWrapper
      id="title"
      :label="$t('forms.fields.title')"
      required
      :char-count="form.title?.length || 0"
      :max-length="60"
      :error="form.errors.title"
      :validating="form.validating"
      :valid="form.valid('title')"
      :invalid="form.invalid('title')"
    >
      <Input
        id="title"
        v-model="form.title"
        type="text"
        :placeholder="$t('Įrašyti pavadinimą...')"
        :class="['h-11', fieldSurfaceClass]"
        @change="form.validate('title')"
      />
    </FormFieldWrapper>

    <div v-if="!isCreate && news" class="flex flex-col gap-2">
      <PermalinkField
        :permalink="form.permalink"
        :base-url="newsBaseUrl"
        :view-url="publicNewsUrl"
        :warning="permalinkChanged
          ? $t('Pakeitus nuorodą, sena nuoroda ir toliau nukreips į šį puslapį — nebereikalingas senas nuorodas galėsite ištrinti.')
          : undefined"
        :validating="form.validating"
        :valid="form.valid('permalink')"
        :invalid="form.invalid('permalink')"
        @update:permalink="form.permalink = $event"
        @change="form.validate('permalink')"
      />
      <PublicUrlHistoryCard
        :urls="news.public_urls ?? []"
        :destroy-route="(id) => route('news.publicUrls.destroy', [news!.id, id])"
      />
    </div>
    <PermalinkPreviewHint
      v-else
      :preview="permalinkPreview.preview.value"
      :is-checking="permalinkPreview.isChecking.value"
    />

    <FormFieldWrapper
      id="short"
      :label="$t('Įvadinis tekstas')"
      :required="isCreate"
      :hint="$t('Rodomas naujienos pradžioje, naujienų sąraše ir paieškos rezultatuose.')"
      :char-count="shortPlainText.length"
      :max-length="200"
      :error="form.errors.short"
    >
      <TiptapEditor v-model="form.short" preset="marks" disable-links :max-characters="200" html framed />
      <details class="group">
        <summary
          :class="[
            'u-touch inline-flex cursor-pointer items-center gap-1.5',
            'text-xs font-bold uppercase tracking-wide text-foreground/80 hover:text-foreground transition-colors select-none',
          ]"
        >
          <ChevronDown class="size-3.5 transition-transform group-open:rotate-180" />
          {{ $t('Kaip atrodys paieškoje') }}
        </summary>
        <SEOPreview
          class="mt-3"
          :title="form.title"
          :description="shortPlainText"
          :url="form.permalink"
          :base-url="newsBaseUrl"
        />
      </details>
    </FormFieldWrapper>

    <FormFieldWrapper id="content" :label="$t('Turinys')">
      <RichContentFormElement
        v-model="form.content.parts"
        :tenant-id="news?.tenant_id"
        @save="$emit('submit:form', form)"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="highlights"
      :label="$t('Svarbiausi punktai')"
      :hint="$t('Iki trijų svarbiausių minčių, išskiriamų naujienos puslapyje.')"
      :char-count="filledHighlightCount"
      :max-length="3"
    >
      <OrderedListInput
        v-model="form.highlights"
        :max="3"
        input-type="textarea"
        :placeholder="$t('Įveskite svarbų punktą...')"
        :empty-text="$t('Dar nepridėta jokių punktų')"
        :add-first-text="$t('Pridėti pirmą punktą')"
        :add-text="$t('Pridėti punktą')"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="image"
      :label="$t('Nuotrauka')"
      :hint="$t('Rodoma naujienos viršuje, naujienų sąraše ir dalinantis nuoroda.')"
      :error="form.errors.image"
      :valid="form.valid('image')"
      :invalid="form.invalid('image')"
    >
      <ImageUpload
        v-model:url="form.image"
        mode="immediate"
        folder="news"
        cropper
        full-width
        :existing-url="news?.image"
        @update:url="form.validate('image')"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="image_author"
      :label="`${$t('Nuotraukos autorius')} (${$t('neprivaloma')})`"
      :hint="$t('Žmogus arba organizacija, kurie sukūrė nuotrauką')"
    >
      <Input
        id="image_author"
        v-model="form.image_author"
        type="text"
        :placeholder="$t('Žmogus arba organizacija...')"
        :class="['h-11', fieldSurfaceClass]"
      />
    </FormFieldWrapper>

    <template #aside>
      <ContentPublishPanel
        :published="!form.draft"
        :publish-time="form.publish_time"
        :time-hint="$t('Nuo šio laiko naujiena rodoma paieškoje ir naujienų sąrašuose.')"
        :publish-time-error="form.errors.publish_time"
        :callout="statusCallout"
        test-id-prefix="news"
        @update:published="form.draft = !$event"
        @update:publish-time="updatePublishTime"
      >
        <TenantSelectField
          v-if="isCreate"
          v-model="form.tenant_id"
          :tenants="assignableTenants"
          :error="form.errors.tenant_id"
          :valid="form.valid('tenant_id')"
          :invalid="form.invalid('tenant_id')"
          @update:model-value="form.validate('tenant_id')"
        />
      </ContentPublishPanel>

      <FormPanel :title="$t('Temos')" :icon="Tags" title-class="text-brand">
        <TagMultiSelect v-model="form.tags" :available-tags="props.availableTags" :hint="$t('Temos, pagal kurias naujieną galima rasti.')" />
      </FormPanel>

      <ContentLanguagePanel
        v-model:lang="form.lang"
        v-model:other-lang-id="form.other_lang_id"
        collection="news"
        :candidates="otherLangNews"
        :is-create
        :labels="languageLabels"
        :lang-error="form.errors.lang"
        :lang-valid="form.valid('lang')"
        :lang-invalid="form.invalid('lang')"
        test-id-prefix="news"
        @update:lang="form.validate('lang')"
      />

      <FormPanel :title="$t('Rodymo nustatymai')" :icon="LayoutTemplate" title-class="text-brand" flush>
        <FormToggleRow
          v-model="form.show_breadcrumbs"
          :label="$t('Rodyti naujienos kelią')"
          :hint="$t('„Pradžia / Naujienos / …“ navigacija viršuje.')"
          data-testid="toggle-breadcrumbs"
        />
      </FormPanel>

      <ContentAnalyticsCard
        v-if="!isCreate && news?.id"
        :id="news.id"
        type="news"
        :content-date="news.publish_time ?? news.created_at"
      />
    </template>

    <template v-if="!isCreate && enableDelete" #danger-zone>
      <Button
        type="button"
        variant="outline"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground"
        @click="deleteConfirmOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Ištrinti naujieną') }}
      </Button>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti naujieną?')"
        :description="$t('Naujiena bus perkelta į šiukšlinę.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, LayoutTemplate, Tags, Trash2 } from 'lucide-vue-next';

import RichContentFormElement from '../RichContent/RichContentFormElement.vue';

import ContentLanguagePanel from './ContentLanguagePanel.vue';
import ContentPublishPanel from './ContentPublishPanel.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import PermalinkField from './PermalinkField.vue';
import PermalinkPreviewHint from './PermalinkPreviewHint.vue';
import PublicUrlHistoryCard from './PublicUrlHistoryCard.vue';
import SEOPreview from './SEOPreview.vue';
import TagMultiSelect from './TagMultiSelect.vue';
import TenantSelectField, { pickDefaultTenantId } from './TenantSelectField.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormToggleRow, StatusBadge } from '@/Components/Patterns';
import ContentAnalyticsCard from '@/Components/Analytics/ContentAnalyticsCard.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { OrderedListInput } from '@/Components/ui/ordered-list-input';
import { ImageUpload } from '@/Components/ui/upload';
import { contentStatuses } from '@/Constants/statuses';
import { resolveTenantPublicHost, resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { usePermalinkPreview } from '@/Composables/usePermalinkPreview';
import { newsTemplate } from '@/Types/formTemplates';
import { formatDateTime } from '@/Utils/dateTime';
import { localizedRoute, localizedSlug } from '@/Utils/LocalizedRoutes';

const props = withDefaults(defineProps<{
  news?: App.Entities.News;
  otherLangNews?: App.Entities.News[];
  availableTags?: App.Entities.Tag[];
  /** Tenants the user may create news in — only meaningful (and rendered) on create. */
  assignableTenants?: App.Entities.Tenant[];
  rememberKey?: 'CreateNews';
  submitUrl: string;
  submitMethod: 'post' | 'patch';
  enableDelete?: boolean;
}>(), {
  news: undefined,
  otherLangNews: () => [],
  availableTags: () => [],
  assignableTenants: () => [],
  rememberKey: undefined,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => props.rememberKey === 'CreateNews');
const deleteConfirmOpen = ref(false);

const fieldIds: Record<string, string> = {
  title: 'title',
  tenant_id: 'tenant',
  lang: 'lang',
  permalink: 'permalink',
};

const formData = {
  ...newsTemplate,
  ...props.news,
  show_breadcrumbs: props.news?.show_breadcrumbs ?? true,
  highlights: props.news?.highlights || [],
};

const form = props.rememberKey
  ? useForm(props.rememberKey, formData).withPrecognition(props.submitMethod, props.submitUrl)
  : useForm(formData).withPrecognition(props.submitMethod, props.submitUrl);

// Default to the sole assignable tenant; a multi-tenant actor (e.g. super admin) picks explicitly.
if (isCreate.value && form.tenant_id == null) {
  form.tenant_id = pickDefaultTenantId(props.assignableTenants);
}

form.setValidationTimeout(500);

// Preview-only: the actual permalink is generated server-side on create (GenerateUniqueSlug).
// Title getter returns '' outside create mode so the composable's own length guard no-ops it.
const permalinkPreview = usePermalinkPreview('news', () => (isCreate.value ? form.title ?? '' : ''), () => form.lang ?? 'lt');

if (!Array.isArray(form.highlights)) {
  form.highlights = [];
}

const newsBaseUrl = computed(() => {
  const lang = form.lang ?? 'lt';

  return `${resolveTenantPublicHost(props.news?.tenant?.id)}/${lang}/${localizedSlug('newsString', lang)}`;
});

const publicNewsUrl = computed(() => {
  // A saved draft 404s publicly, so there is nothing to open yet.
  if (!props.news?.id || props.news.draft || !form.permalink || !props.news.tenant) return undefined;

  return localizedRoute('news', {
    subdomain: resolveTenantSubdomain(props.news.tenant.id),
    news: form.permalink,
  }, form.lang ?? 'lt');
});

// The bar states what is saved; the heading and fields follow the edit. Props refresh after each save.
const barTitle = computed(() => (isCreate.value ? $t('Nauja naujiena') : (props.news?.title || $t('Naujiena'))));

// The redirect note is a consequence of an edit, so it appears only once there is one.
const permalinkChanged = computed(() => form.permalink !== props.news?.permalink);

const shortPlainText = computed(() => (form.short ?? '').replace(/<[^>]*>/g, '').trim());

const filledHighlightCount = computed(() => (form.highlights as string[]).filter(item => item?.trim()).length);

function updatePublishTime(value: string | null | undefined) {
  form.publish_time = value ?? null;
  if (value) {
    form.validate('publish_time');
  }
}

// A future publish_time only keeps the article out of search and listings; its link already works.
const statusCallout = computed(() => {
  if (form.draft) {
    return $t('Juodraštis matomas tik sistemoje — svetainės lankytojai jo nemato.');
  }

  const publishTime = form.publish_time ? new Date(form.publish_time as string) : null;

  if (publishTime && publishTime.getTime() > Date.now()) {
    return $t('Naujiena jau pasiekiama pagal nuorodą, o paieškoje ir naujienų sąrašuose pasirodys nuo :date.', {
      date: formatDateTime(publishTime),
    });
  }

  return $t('Paskelbta naujiena matoma visiems svetainės lankytojams.');
});

const languageLabels = computed(() => ({
  lang: $t('Naujienos kalba'),
  otherLangLt: $t('Naujiena lietuvių kalba'),
  otherLangEn: $t('Naujiena anglų kalba'),
  createHint: $t('Susiesi išsaugojęs naujieną.'),
  editHint: $t('Susieja tą patį turinį kita kalba.'),
  createPlaceholder: $t('Pasirinkti kitos kalbos naujieną...'),
  dialogTitle: $t('Kitos kalbos naujiena'),
  searchPlaceholder: $t('Ieškoti naujienos pagal pavadinimą...'),
  emptyMessage: $t('Naujienų nerasta'),
}));
</script>
