<template>
  <FormPage
    :title="isCreate ? $t('Nauja naujiena') : (form.title || $t('Naujiena'))"
    :head-title="isCreate ? $t('Nauja naujiena') : (form.title || $t('Naujiena'))"
    :back-href="route('news.index')"
    :back-label="$t('Naujienos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    max-width="4xl"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate" #header-actions>
      <Button v-if="statusLinks.length > 0" as-child variant="outline" size="sm">
        <a :href="statusLinks[0].url" target="_blank" rel="noopener noreferrer">
          <ExternalLink class="mr-1.5 size-4" />
          {{ $t('Peržiūrėti viešai') }}
        </a>
      </Button>
      <ActivityLogSheet v-if="props.news?.id" subject-type="news" :subject-id="String(props.news.id)" />
    </template>

    <!-- Status Header -->
    <FormStatusHeader
      :is-published="!form.draft"
      :server-is-published="props.news ? !props.news.draft : undefined"
      :publish-time="publishTimeDate"
      :links="statusLinks"
      :is-create
      show-publish-time
      @update:is-published="form.draft = !$event"
      @update:publish-time="handlePublishTimeUpdate"
    />

    <ContentAnalyticsCard
      v-if="!isCreate && props.news?.id"
      :id="props.news.id"
      type="news"
      :content-date="props.news.publish_time ?? props.news.created_at"
      class="mb-6"
    />

    <!-- Section 1: Title (Essential - the identity of the news) -->
    <FormSection
      data-section="1"
      :title="$t('forms.fields.title')"
      :description="$t('Naujienos antraštė')"
    >
      <div class="space-y-4">
        <FormFieldWrapper
          id="title"
          :label="$t('forms.fields.title')"
          required
          :hint="$t('Pavadinimas bus rodomas naršyklės skirtuke ir paieškos rezultatuose')"
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
            class="text-lg"
            @change="form.validate('title')"
          />
        </FormFieldWrapper>

        <PermalinkPreviewHint
          v-if="isCreate"
          :preview="permalinkPreview.preview.value"
          :is-checking="permalinkPreview.isChecking.value"
        />

        <FormFieldWrapper
          v-if="isCreate"
          id="tenant"
          :label="$t('forms.fields.tenant')"
          required
          :error="form.errors.tenant_id"
          :valid="form.valid('tenant_id')"
          :invalid="form.invalid('tenant_id')"
        >
          <Select v-model="tenantIdString" @update:model-value="form.validate('tenant_id')">
            <SelectTrigger id="tenant">
              <SelectValue :placeholder="$t('forms.placeholders.select_tenant')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <!-- Language selector inline with title -->
        <div class="grid gap-4 sm:grid-cols-2">
          <FormFieldWrapper
            id="lang"
            :label="$t('Kalba')"
            required
            :error="form.errors.lang"
            :valid="form.valid('lang')"
            :invalid="form.invalid('lang')"
          >
            <ToggleGroup
              v-model="form.lang"
              type="single"
              class="justify-start"
              @update:model-value="form.validate('lang')"
            >
              <ToggleGroupItem value="lt" class="gap-2">
                <img src="https://hatscripts.github.io/circle-flags/flags/lt.svg" alt="" class="h-4 w-4">
                Lietuvių
              </ToggleGroupItem>
              <ToggleGroupItem value="en" class="gap-2">
                <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" alt="" class="h-4 w-4">
                English
              </ToggleGroupItem>
            </ToggleGroup>
          </FormFieldWrapper>

          <TagMultiSelect v-model="form.tags" :available-tags="props.availableTags" />
        </div>

        <!-- Other Language News -->
        <FormFieldWrapper
          id="other_lang"
          :label="$t('Kitos kalbos naujiena')"
          :hint="$t('Susieti su ta pačia naujiena kita kalba')"
        >
          <CollectionSelectDialog
            v-if="!isCreate"
            v-model:open="otherLangDialogOpen"
            collection="news"
            allow-empty
            :base-filter-by="otherLangBaseFilterBy"
            :initial-hits="otherLangInitialHits"
            :title="$t('Kitos kalbos naujiena')"
            :confirm-label="$t('Pasirinkti')"
            :search-placeholder="$t('Ieškoti naujienos pagal pavadinimą...')"
            :empty-message="$t('Naujienų nerasta')"
            @confirm="onOtherLangNewsConfirm"
          >
            <template #trigger>
              <Button type="button" variant="outline" class="w-full justify-between font-normal">
                <span class="truncate" :class="{ 'text-muted-foreground': !form.other_lang_id }">
                  {{ selectedOtherLangNewsLabel }}
                </span>
                <ChevronDown class="size-4 opacity-50" />
              </Button>
            </template>
          </CollectionSelectDialog>
          <Button v-else type="button" variant="outline" class="w-full justify-between font-normal" disabled>
            <span class="text-muted-foreground">{{ $t('Pasirinkti kitos kalbos naujieną...') }}</span>
            <ChevronDown class="size-4 opacity-50" />
          </Button>
        </FormFieldWrapper>
      </div>
    </FormSection>

    <!-- Section 2: Image (Visual identity) -->
    <FormSection
      data-section="2"
      :title="$t('Nuotrauka')"
      :description="$t('Pagrindinė naujienos nuotrauka')"
    >
      <div class="space-y-4">
        <FormFieldWrapper
          id="image"
          :label="$t('Nuotrauka')"
          :error="form.errors.image"
          :valid="form.valid('image')"
          :invalid="form.invalid('image')"
        >
          <ImageUpload
            v-model:url="form.image"
            mode="immediate"
            folder="news"
            cropper
            :existing-url="news?.image"
            @update:url="form.validate('image')"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="image_author"
          :label="$t('Nuotraukos autorius')"
          :hint="$t('Žmogus arba organizacija, kurie sukūrė nuotrauką')"
        >
          <Input
            id="image_author"
            v-model="form.image_author"
            type="text"
            :placeholder="$t('Žmogus arba organizacija...')"
          />
        </FormFieldWrapper>
      </div>
    </FormSection>

    <!-- Section 3: Short description / Intro text -->
    <FormSection
      data-section="3"
      :title="$t('Įvadinis tekstas')"
      :description="`${$t('Naudojamas naujienos įvade ir paieškos rezultatuose (SEO).')} ${$t('Maksimalus ženklų skaičius')}: 200.`"
    >
      <TiptapEditor v-model="form.short" preset="marks" disable-links :max-characters="200" html />
    </FormSection>

    <!-- Section 4: Content (Main editing area) -->
    <FormSection
      data-section="4"
      :title="$t('Turinys')"
      :description="$t('Pagrindinė naujienos informacija')"
    >
      <RichContentFormElement
        v-model="form.content.parts"
        :tenant-id="news?.tenant_id"
        @save="$emit('submit:form', form)"
      />
    </FormSection>

    <!-- Section 5: Highlights (Optional but prominent) -->
    <FormSection
      data-section="5"
      :title="$t('Akcentai')"
      :description="$t('Iki 3 pagrindinių minčių, kurios bus išskirtos naujienos puslapyje.')"
    >
      <OrderedListInput
        v-model="form.highlights"
        :max="3"
        :placeholder="`${$t('Akcentas')} {n}...`"
        :empty-text="$t('Dar nepridėta jokių akcentų')"
        :add-first-text="$t('Pridėti pirmą akcentą')"
        :add-text="$t('Pridėti akcentą')"
      />
    </FormSection>

    <!-- Advanced Settings Slot -->
    <template #advanced>
      <!-- Breadcrumbs toggle -->
      <div class="flex items-center gap-3">
        <Switch v-model="form.show_breadcrumbs" />
        <span class="text-sm text-foreground">
          {{ $t('Rodyti naujienos kelią (breadcrumbs)') }}
        </span>
      </div>

      <!-- Permalink -->
      <template v-if="!isCreate">
        <FormFieldWrapper
          id="permalink"
          :label="$t('Nuoroda')"
          :error="form.errors.permalink"
          :valid="form.valid('permalink')"
          :invalid="form.invalid('permalink')"
        >
          <div class="flex items-center gap-2">
            <Link2 class="h-4 w-4 shrink-0 text-muted-foreground" />
            <Input
              id="permalink"
              v-model="form.permalink"
              type="text"
              :placeholder="$t('Sugeneruojama nuoroda')"
              @change="form.validate('permalink')"
            />
          </div>
        </FormFieldWrapper>

        <Alert class="border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] text-[var(--status-attention)]">
          <AlertTriangle class="size-4" />
          <AlertTitle>{{ $t('Dėmesio') }}</AlertTitle>
          <AlertDescription>
            {{ $t('Pakeitus nuorodą, sena nuoroda ir toliau nukreips į šį puslapį — nebereikalingas senas nuorodas galėsite ištrinti.') }}
          </AlertDescription>
        </Alert>
      </template>
    </template>

    <!-- Danger Zone Slot -->
    <template v-if="!isCreate && props.news" #danger-zone>
      <div class="space-y-6">
        <PublicUrlHistoryCard
          :urls="props.news.public_urls ?? []"
          :destroy-route="(id) => route('news.publicUrls.destroy', [props.news!.id, id])"
        />

        <div v-if="enableDelete" class="flex items-center justify-between gap-4">
          <div>
            <h4 class="text-sm font-semibold text-destructive">
              {{ $t('Ištrinti naujieną') }}
            </h4>
            <p class="text-xs text-muted-foreground">
              {{ $t('Naujiena bus perkelta į šiukšlinę.') }}
            </p>
          </div>
          <Button
            type="button"
            variant="destructive"
            size="sm"
            class="u-touch shrink-0"
            @click="deleteConfirmOpen = true"
          >
            <Trash2 class="mr-1.5 size-4" />
            {{ $t('Ištrinti') }}
          </Button>
        </div>

        <ConfirmDialog
          v-model:open="deleteConfirmOpen"
          :title="$t('Ištrinti naujieną?')"
          :description="$t('Naujiena bus perkelta į šiukšlinę.')"
          :confirm-label="$t('Ištrinti')"
          destructive
          @confirm="emit('delete')"
        />
      </div>
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, ChevronDown, ExternalLink, Link2, Trash2 } from 'lucide-vue-next';

import RichContentFormElement from '../RichContent/RichContentFormElement.vue';

import FormFieldWrapper from './FormFieldWrapper.vue';
import FormStatusHeader from './FormStatusHeader.vue';
import PermalinkPreviewHint from './PermalinkPreviewHint.vue';
import PublicUrlHistoryCard from './PublicUrlHistoryCard.vue';
import TagMultiSelect from './TagMultiSelect.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import ContentAnalyticsCard from '@/Components/Analytics/ContentAnalyticsCard.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { localizedRoute } from '@/Utils/LocalizedRoutes';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { OrderedListInput } from '@/Components/ui/ordered-list-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { usePermalinkPreview } from '@/Composables/usePermalinkPreview';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { ImageUpload } from '@/Components/ui/upload';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { newsTemplate } from '@/Types/formTemplates';

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
  form.tenant_id = props.assignableTenants?.find(tenant => tenant.type === 'pagrindinis')?.id
    ?? props.assignableTenants?.[0]?.id
    ?? null;
}

// Set validation timeout to 500ms for faster feedback
form.setValidationTimeout(500);

// Handle tenant_id as string for the Select component
const tenantIdString = computed({
  get: () => form.tenant_id ? String(form.tenant_id) : '',
  set: (val: string) => {
    form.tenant_id = val ? Number(val) : null;
  },
});

// Preview-only: the actual permalink is generated server-side on create (GenerateUniqueSlug).
// Title getter returns '' outside create mode so the composable's own length guard no-ops it —
// there is nothing to preview once the record exists and the real permalink is editable.
const permalinkPreview = usePermalinkPreview('news', () => (isCreate.value ? form.title ?? '' : ''), () => form.lang ?? 'lt');

// Ensure highlights is always an array
if (!Array.isArray(form.highlights)) {
  form.highlights = [];
}

// Status header links
const statusLinks = computed(() => {
  // Need permalink and tenant to construct a valid public URL
  if (!form.permalink || !props.news?.tenant) return [];

  const newsLang = form.lang ?? 'lt';
  const url = localizedRoute('news', {
    subdomain: resolveTenantSubdomain(props.news.tenant.id),
    news: form.permalink,
  }, newsLang);

  return [{ url, label: 'Public' }];
});

// Date picker compatibility - convert string to Date
const publishTimeDate = computed({
  get: () => form.publish_time ? new Date(form.publish_time) : undefined,
  set: (val: Date | undefined) => {
    form.publish_time = val ? val.toISOString() : null;
  },
});

// Handle publish time update from status header
function handlePublishTimeUpdate(val: Date | null) {
  form.publish_time = val ? val.toISOString() : null;
  if (val) {
    form.validate('publish_time');
  }
}

const otherLangDialogOpen = ref(false);

// Opposite language of the news being edited (only two locales exist).
const otherLang = computed(() => (form.lang === 'lt' ? 'en' : 'lt'));

// Scope the news search to the opposite-language news of the candidate tenants
// — exactly reproducing the `otherLangNews` prop.
const otherLangBaseFilterBy = computed(() => {
  const tenantIds = [
    ...new Set((props.otherLangNews ?? []).map(n => n.tenant?.id).filter((id): id is number => id != null)),
  ];
  const parts: string[] = [];
  if (tenantIds.length > 0) {
    parts.push(`tenant_ids:[${tenantIds.join(',')}]`);
  }
  parts.push(`lang:=${otherLang.value}`);
  return parts.join(' && ');
});

const selectedOtherLangNewsLabel = computed(() => {
  const current = (props.otherLangNews ?? []).find(n => String(n.id) === String(form.other_lang_id));
  return current ? `${current.title} (${current.tenant?.shortname})` : `-- ${$t('Nepasirinkta')} --`;
});

const otherLangInitialHits = computed<NormalizedSearchHit[]>(() => {
  const current = (props.otherLangNews ?? []).find(n => String(n.id) === String(form.other_lang_id));
  if (!current) {
    return [];
  }
  return [normalizeHit('news', {
    id: current.id,
    title: current.title,
    tenant_name: current.tenant?.shortname,
    lang: otherLang.value,
  })];
});

function onOtherLangNewsConfirm(hits: NormalizedSearchHit[]) {
  form.other_lang_id = hits[0] ? Number(hits[0].recordId) : null;
}
</script>
