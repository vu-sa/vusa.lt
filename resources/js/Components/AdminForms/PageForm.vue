<template>
  <FormPage
    :title="isCreate ? $t('Naujas puslapis') : (form.title || $t('Puslapis'))"
    :head-title="isCreate ? $t('Naujas puslapis') : (form.title || $t('Puslapis'))"
    :back-href="route('pages.index')"
    :back-label="$t('Puslapiai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    max-width="4xl"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate" #header-actions>
      <Button v-if="fullPageUrl" as-child variant="outline" size="sm">
        <a :href="fullPageUrl" target="_blank" rel="noopener noreferrer">
          <ExternalLink class="mr-1.5 size-4" />
          {{ $t('Peržiūrėti viešai') }}
        </a>
      </Button>
      <ActivityLogSheet v-if="page.id" subject-type="page" :subject-id="String(page.id)" />
    </template>

    <!-- Status Header -->
    <FormStatusHeader
      :is-published="Boolean(form.is_active)"
      :server-is-published="props.page.is_active"
      :links="statusLinks"
      :is-create
      show-publish-time
      :publish-time="publishTimeDate"
      @update:is-published="form.is_active = $event"
      @update:publish-time="publishTimeDate = $event"
    />

    <ContentAnalyticsCard
      v-if="!isCreate && page.id"
      :id="page.id"
      type="page"
      :content-date="page.publish_time ?? page.created_at"
      class="mb-6"
    />

    <!-- Section 1: Title & Essential Info -->
    <FormSection
      :title="$t('forms.fields.title')"
      :description="$t('Puslapio antraštė ir pagrindiniai nustatymai')"
    >
      <div class="space-y-4">
        <!-- Title with character counter -->
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
              <img src="https://hatscripts.github.io/circle-flags/flags/lt.svg" class="h-4 w-4" alt="">
              Lietuvių
            </ToggleGroupItem>
            <ToggleGroupItem value="en" class="gap-2">
              <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" class="h-4 w-4" alt="">
              English
            </ToggleGroupItem>
          </ToggleGroup>
        </FormFieldWrapper>

        <!-- Parent page -->
        <FormFieldWrapper
          id="parent_page"
          :label="$t('Tėvinis puslapis')"
          :error="form.errors.parent_id"
          :hint="$t('Puslapio vieta struktūroje — neturi įtakos nuorodai')"
          :valid="form.valid('parent_id')"
          :invalid="form.invalid('parent_id')"
        >
          <CollectionSelectDialog
            v-model:open="parentDialogOpen"
            collection="pages"
            allow-empty
            :base-filter-by="parentBaseFilterBy"
            :disabled-ids="parentDisabledIds"
            :initial-hits="parentInitialHits"
            :title="$t('Tėvinis puslapis')"
            :confirm-label="$t('Pasirinkti')"
            :search-placeholder="$t('Ieškoti puslapio pagal pavadinimą...')"
            :empty-message="$t('Puslapių nerasta')"
            @confirm="onParentConfirm"
          >
            <template #trigger>
              <Button type="button" variant="outline" class="w-full justify-between font-normal">
                <span class="truncate" :class="{ 'text-muted-foreground': !form.parent_id }">
                  {{ selectedParentLabel }}
                </span>
                <ChevronDown class="size-4 opacity-50" />
              </Button>
            </template>
          </CollectionSelectDialog>
        </FormFieldWrapper>

        <TagMultiSelect v-model="form.tags" :available-tags="props.availableTags" />

        <!-- Other Language Page -->
        <FormFieldWrapper
          id="other_lang"
          :label="$t('Kitos kalbos puslapis')"
          :hint="$t('Susieti su to paties turinio puslapiu kita kalba')"
        >
          <CollectionSelectDialog
            v-if="!isCreate"
            v-model:open="otherLangDialogOpen"
            collection="pages"
            allow-empty
            :base-filter-by="otherLangBaseFilterBy"
            :initial-hits="otherLangInitialHits"
            :title="$t('Kitos kalbos puslapis')"
            :confirm-label="$t('Pasirinkti')"
            :search-placeholder="$t('Ieškoti puslapio pagal pavadinimą...')"
            :empty-message="$t('Puslapių nerasta')"
            @confirm="onOtherLangPageConfirm"
          >
            <template #trigger>
              <Button type="button" variant="outline" class="w-full justify-between font-normal">
                <span class="truncate" :class="{ 'text-muted-foreground': !form.other_lang_id }">
                  {{ selectedOtherLangPage.label }}
                </span>
                <ChevronDown class="size-4 opacity-50" />
              </Button>
            </template>
          </CollectionSelectDialog>
          <Button v-else type="button" variant="outline" class="w-full justify-between font-normal" disabled>
            <span class="text-muted-foreground">{{ $t('Pasirinkti kitos kalbos puslapį...') }}</span>
            <ChevronDown class="size-4 opacity-50" />
          </Button>
        </FormFieldWrapper>
      </div>
    </FormSection>

    <!-- Section 2: Content (Main editing area) -->
    <FormSection
      :title="$t('Turinys')"
      :description="$t('Pagrindinė puslapio informacija')"
    >
      <RichContentFormElement
        v-model="form.content.parts"
        :tenant-id="page.tenant_id"
        @save="$emit('submit:form', form)"
      />
    </FormSection>

    <!-- Section 3: Highlights -->
    <FormSection
      :title="$t('Svarbiausi punktai')"
      :description="$t('Iki 3 pagrindinių minčių, kurios bus išskirtos puslapyje.')"
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
    </FormSection>

    <!-- Advanced Settings Slot -->
    <template #advanced>
      <!-- Layout Selection -->
      <div class="space-y-2">
        <Label class="block text-sm font-medium">{{ $t('Išdėstymas') }}</Label>
        <VisualOptionSelect v-model="form.layout" :options="layoutOptions" :columns="3" icon-class="h-12 w-20" />
      </div>

      <!-- Table of contents toggle -->
      <div v-if="form.layout === 'default'" class="flex items-center gap-3">
        <Switch v-model="form.show_table_of_contents" />
        <span class="text-sm text-foreground">
          {{ $t('Rodyti turinio lentelę šoninėje juostoje') }}
        </span>
      </div>

      <!-- Page header toggle -->
      <div class="flex items-center gap-3">
        <Switch v-model="form.show_title" />
        <span class="text-sm text-foreground">
          {{ $t('Rodyti puslapio pavadinimą ir atnaujinimo laiką') }}
        </span>
      </div>

      <!-- Breadcrumbs toggle -->
      <div class="flex items-center gap-3">
        <Switch v-model="form.show_breadcrumbs" />
        <span class="text-sm text-foreground">
          {{ $t('Rodyti puslapio kelią (breadcrumbs)') }}
        </span>
      </div>

      <!-- ToC overlap warning -->
      <Alert
        v-if="showTocOverlapWarning"
        class="border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] text-[var(--status-attention)]"
      >
        <AlertTriangle class="size-4" />
        <AlertTitle>{{ $t('Turinio lentelė gali persidengti su turiniu') }}</AlertTitle>
        <AlertDescription>
          <p>{{ $t('Šiame puslapyje yra platus arba per visą pločio blokas, kuris bus suspaustas iki turinio stulpelio pločio, kol rodoma turinio lentelė.') }}</p>
          <Button
            type="button"
            variant="link"
            size="sm"
            class="h-auto p-0 text-inherit underline"
            @click="form.show_table_of_contents = false"
          >
            {{ $t('Išjungti turinio lentelę') }}
          </Button>
        </AlertDescription>
      </Alert>

      <!-- Permalink -->
      <PermalinkField
        v-if="!isCreate"
        :permalink="form.permalink"
        :base-url="pageBaseUrl"
        :disabled="false"
        :view-url="fullPageUrl"
        :warning="$t('Pakeitus nuorodą, sena nuoroda ir toliau nukreips į šį puslapį — nebereikalingas senas nuorodas galėsite ištrinti.')"
        :validating="form.validating"
        :valid="form.valid('permalink')"
        :invalid="form.invalid('permalink')"
        @update:permalink="form.permalink = $event"
        @change="form.validate('permalink')"
      />

      <!-- SEO Section -->
      <div class="space-y-4 border-t border-border pt-4">
        <h4 class="text-sm font-semibold">
          {{ $t('SEO ir metaduomenys') }}
        </h4>

        <!-- SEO Preview -->
        <SEOPreview
          :title="form.title"
          :description="form.meta_description"
          :url="form.permalink"
          :base-url="seoBaseUrl"
        />

        <!-- Meta description -->
        <FormFieldWrapper
          id="meta_description"
          :label="$t('Meta aprašymas')"
          :hint="$t('Trumpas puslapio aprašymas, rodomas paieškos rezultatuose')"
          :char-count="form.meta_description?.length || 0"
          :max-length="160"
        >
          <Textarea
            id="meta_description"
            v-model="form.meta_description"
            :placeholder="$t('Trumpas puslapio aprašymas paieškos rezultatams...')"
            rows="3"
            :class="metaDescriptionClass"
          />
        </FormFieldWrapper>

        <!-- Featured image -->
        <FormFieldWrapper
          id="featured_image"
          :label="$t('Pagrindinė nuotrauka')"
          :hint="$t('Nuotrauka naudojama dalinantis socialiniuose tinkluose')"
        >
          <ImageUpload
            v-model:url="form.featured_image"
            mode="immediate"
            folder="pages"
            cropper
            :existing-url="page.featured_image"
          />
        </FormFieldWrapper>
      </div>
    </template>

    <!-- Danger Zone Slot -->
    <template v-if="!isCreate" #danger-zone>
      <div class="space-y-6">
        <PublicUrlHistoryCard
          :urls="page.public_urls ?? []"
          :destroy-route="(id) => route('pages.publicUrls.destroy', [page.id, id])"
        />

        <div v-if="enableDelete" class="flex items-center justify-between gap-4">
          <div>
            <h4 class="text-sm font-semibold text-destructive">
              {{ $t('Ištrinti puslapį') }}
            </h4>
            <p class="text-xs text-muted-foreground">
              {{ $t('Puslapis bus perkeltas į šiukšlinę.') }}
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
          :title="$t('Ištrinti puslapį?')"
          :description="$t('Puslapis bus perkeltas į šiukšlinę.')"
          :confirm-label="$t('Ištrinti')"
          destructive
          @confirm="emit('delete')"
        />
      </div>
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref, h } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, ChevronDown, ExternalLink, Trash2 } from 'lucide-vue-next';

import RichContentFormElement from '../RichContent/RichContentFormElement.vue';
import { getContentType, type BlockWidth } from '../RichContent/Types';
import VisualOptionSelect from '../FormItems/VisualOptionSelect.vue';

import FormFieldWrapper from './FormFieldWrapper.vue';
import FormStatusHeader from './FormStatusHeader.vue';
import PermalinkField from './PermalinkField.vue';
import PermalinkPreviewHint from './PermalinkPreviewHint.vue';
import PublicUrlHistoryCard from './PublicUrlHistoryCard.vue';
import SEOPreview from './SEOPreview.vue';
import TagMultiSelect from './TagMultiSelect.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import ContentAnalyticsCard from '@/Components/Analytics/ContentAnalyticsCard.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { OrderedListInput } from '@/Components/ui/ordered-list-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { usePermalinkPreview } from '@/Composables/usePermalinkPreview';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { Textarea } from '@/Components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { ImageUpload } from '@/Components/ui/upload';

const props = withDefaults(defineProps<{
  // `descendant_ids` is server-computed (Page::descendantIds()), not a real relation —
  // it disables invalid parent-picker options and has no model-typer equivalent.
  page: App.Entities.Page & { descendant_ids?: number[] };
  otherLangPages?: App.Entities.Page[];
  availableTags?: App.Entities.Tag[];
  /** Tenants the user may create pages in — only meaningful (and rendered) on create. */
  assignableTenants?: App.Entities.Tenant[];
  rememberKey?: 'CreatePage';
  submitUrl: string;
  submitMethod: 'post' | 'patch';
  enableDelete?: boolean;
}>(), {
  otherLangPages: () => [],
  availableTags: () => [],
  assignableTenants: () => [],
  rememberKey: undefined,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => props.rememberKey === 'CreatePage');
const deleteConfirmOpen = ref(false);

const fieldIds: Record<string, string> = {
  title: 'title',
  tenant_id: 'tenant',
  lang: 'lang',
  parent_id: 'parent_page',
  permalink: 'permalink',
};

// Initialize form with page data
const formData = {
  ...props.page,
  layout: props.page.layout || 'default',
  show_table_of_contents: props.page.show_table_of_contents ?? true,
  show_title: props.page.show_title ?? true,
  show_breadcrumbs: props.page.show_breadcrumbs ?? true,
  highlights: props.page.highlights || [],
  meta_description: props.page.meta_description || '',
  featured_image: props.page.featured_image || '',
  publish_time: props.page.publish_time || null,
} as unknown as Record<string, unknown>;

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
const permalinkPreview = usePermalinkPreview('page', () => (isCreate.value ? form.title ?? '' : ''), () => form.lang ?? 'lt');

// Ensure highlights is always an array
if (!Array.isArray(form.highlights)) {
  form.highlights = [];
}

// URL helpers - use the page's tenant and app URL from config
const pageBaseUrl = computed(() => {
  const appUrl = usePage().props.app?.url ?? 'https://vusa.lt';
  // app.url is itself the main tenant's own URL (e.g. "https://www.vusa.test"), so strip a
  // leading "www." before prefixing the resolved subdomain — otherwise the main tenant doubles
  // up into "vusa.www.vusa.test".
  const rootDomain = appUrl.replace(/^https?:\/\//, '').replace(/^www\./, '');

  return `${resolveTenantSubdomain(props.page.tenant?.id)}.${rootDomain}`;
});

const seoBaseUrl = computed(() => pageBaseUrl.value);

// Construct full page URL using route helper
const fullPageUrl = computed(() => {
  if (!props.page.id || !form.permalink || !props.page.tenant) return undefined;

  const pageLang = form.lang ?? 'lt';
  return route('page', {
    subdomain: resolveTenantSubdomain(props.page.tenant.id),
    lang: pageLang,
    permalink: form.permalink,
  });
});

// Status header links
const statusLinks = computed(() => {
  if (!fullPageUrl.value) return [];
  return [{ url: fullPageUrl.value, label: 'Public' }];
});

// A full/wide content block can't reach its intended width while the `default`
// layout's ToC sidebar is present — it gets clipped to the content column instead
// (see .rc-shell in app.css). Surfaced as a warning rather than a silent layout quirk.
const hasWideOrFullBlock = computed(() => {
  const parts = (form.content?.parts ?? []) as { type: string; options?: Record<string, unknown> }[];
  return parts.some((part) => {
    const contentType = getContentType(part.type);
    const width = (part.options?.width as BlockWidth | undefined) ?? contentType.defaultWidth;
    return width === 'full' || width === 'wide';
  });
});

const showTocOverlapWarning = computed(() =>
  form.layout === 'default' && form.show_table_of_contents && hasWideOrFullBlock.value,
);

// Meta description styling based on length
const metaDescriptionClass = computed(() => {
  const len = form.meta_description?.length || 0;
  if (len > 160) return 'border-destructive focus:border-destructive';
  if (len >= 120 && len <= 160) return 'border-[var(--status-success)] focus:border-[var(--status-success)]';
  return '';
});

// Layout icons as simple SVG representations
const DefaultLayoutIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 8, y: 8, width: 80, height: 12, rx: 2 }),
  h('rect', { x: 8, y: 24, width: 56, height: 32, rx: 2 }),
  h('rect', { x: 68, y: 24, width: 20, height: 32, rx: 2 }),
]);

const WideLayoutIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 4, y: 8, width: 88, height: 12, rx: 2 }),
  h('rect', { x: 4, y: 24, width: 88, height: 32, rx: 2 }),
]);

const FocusedLayoutIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 16, y: 8, width: 64, height: 12, rx: 2 }),
  h('rect', { x: 16, y: 24, width: 64, height: 32, rx: 2 }),
]);

const layoutOptions = [
  {
    value: 'default',
    label: 'Standartinis',
    description: 'Su šonine juosta',
    icon: DefaultLayoutIcon,
  },
  {
    value: 'wide',
    label: 'Platus',
    description: 'Visas plotis',
    icon: WideLayoutIcon,
  },
  {
    value: 'focused',
    label: 'Susikaupęs',
    description: 'Centruotas',
    icon: FocusedLayoutIcon,
  },
];

const otherPageOptions = computed(() => {
  if (isCreate.value) {
    return [];
  }

  if (props.otherLangPages === undefined) {
    return [];
  }

  return props.otherLangPages
    .map(page => ({
      value: page.id,
      label: `${page.title} (${page.tenant?.shortname})`,
    }))
    .reverse();
});

const otherLangPageOptions = computed(() => [
  { value: '__none__', label: `-- ${$t('Nepasirinkta')} --` },
  ...otherPageOptions.value,
]);

// Bridge: the dialog stores other_lang_id; this computed drives the trigger label.
const selectedOtherLangPage = computed(
  () => otherLangPageOptions.value.find(p => String(p.value) === String(form.other_lang_id ?? '__none__')) ?? otherLangPageOptions.value[0],
);

const otherLangDialogOpen = ref(false);

// Opposite language of the page being edited (only two locales exist).
const otherLang = computed(() => (form.lang === 'lt' ? 'en' : 'lt'));

// Scope the pages search to the opposite-language pages of the candidate tenants
// — exactly reproducing the `otherLangPages` prop.
const otherLangBaseFilterBy = computed(() => {
  const tenantIds = [
    ...new Set((props.otherLangPages ?? []).map(p => p.tenant?.id).filter((id): id is number => id != null)),
  ];
  const parts: string[] = [];
  if (tenantIds.length > 0) {
    parts.push(`tenant_ids:[${tenantIds.join(',')}]`);
  }
  parts.push(`lang:=${otherLang.value}`);
  return parts.join(' && ');
});

const otherLangInitialHits = computed<NormalizedSearchHit[]>(() => {
  if (!form.other_lang_id) {
    return [];
  }
  const page = (props.otherLangPages ?? []).find(p => String(p.id) === String(form.other_lang_id));
  if (!page) {
    return [];
  }
  return [normalizeHit('pages', {
    id: page.id,
    title: page.title,
    tenant_name: page.tenant?.shortname,
    lang: otherLang.value,
  })];
});

function onOtherLangPageConfirm(hits: NormalizedSearchHit[]) {
  form.other_lang_id = hits[0] ? Number(hits[0].recordId) : null;
}

const parentDialogOpen = ref(false);

// Bridge: the dialog stores parent_id; this local label tracks the current selection
// without needing a full candidate-page list from the server (unlike other_lang_id
// above — a picker scoped by the form's own reactive lang/tenant needs no such list).
const parentLabel = ref<string | null>(props.page.parent?.title ?? null);

const selectedParentLabel = computed(() => parentLabel.value ?? `-- ${$t('Nepasirinkta')} --`);

// Same lang + tenant as this page — ValidPageParent enforces this server-side too.
const parentBaseFilterBy = computed(() => {
  const parts: string[] = [];
  if (form.tenant_id) {
    parts.push(`tenant_ids:[${form.tenant_id}]`);
  }
  parts.push(`lang:=${form.lang}`);
  return parts.join(' && ');
});

// A page cannot become its own parent, nor hang off one of its own descendants
// (a cycle) — also enforced server-side, this just keeps the picker from offering
// an option that would only bounce back as a validation error.
const parentDisabledIds = computed<Set<string>>(() => {
  const ids = [props.page.id, ...(props.page.descendant_ids ?? [])].filter((id): id is number => id != null);
  return new Set(ids.map(id => `pages-${id}`));
});

const parentInitialHits = computed<NormalizedSearchHit[]>(() => {
  if (!form.parent_id || !props.page.parent) {
    return [];
  }
  return [normalizeHit('pages', {
    id: props.page.parent.id,
    title: props.page.parent.title,
    tenant_name: props.page.tenant?.shortname,
  })];
});

function onParentConfirm(hits: NormalizedSearchHit[]) {
  form.parent_id = hits[0] ? Number(hits[0].recordId) : null;
  parentLabel.value = hits[0]?.title ?? null;
}

// Date/time picker compatibility
const publishTimeDate = computed({
  get: () => form.publish_time ? new Date(form.publish_time) : undefined,
  set: (val: Date | undefined) => {
    form.publish_time = val ? val.toISOString() : null;
  },
});
</script>
