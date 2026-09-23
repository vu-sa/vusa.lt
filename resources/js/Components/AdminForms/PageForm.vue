<template>
  <FormPage
    :title="isCreate ? $t('Naujas puslapis') : (form.title || $t('Puslapis'))"
    :head-title="isCreate ? $t('Naujas puslapis') : (form.title || $t('Puslapis'))"
    :lead="isCreate
      ? $t('Sukurk naują VU SA svetainės puslapį. Užpildyk turinį, susiek kalbas ir pasirink, kada jį paskelbti.')
      : $t('Atnaujink turinį, struktūrą ir paskelbimo būseną.')"
    entity-type="page"
    :back-href="route('pages.index')"
    :back-label="$t('Puslapiai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :available-locales="[]"
    @submit="emit('submit:form', form)"
  >
    <template #title-status>
      <StatusBadge :status="currentStatusPresentation" />
    </template>

    <template v-if="!isCreate" #header-actions>
      <ActivityLogSheet v-if="page.id" subject-type="page" :subject-id="String(page.id)" />
      <Button v-if="fullPageUrl" as-child variant="outline" size="sm" class="hidden sm:inline-flex">
        <a :href="fullPageUrl" target="_blank" rel="noopener noreferrer">
          <Eye class="size-4" />
          {{ $t('Peržiūrėti viešai') }}
        </a>
      </Button>
    </template>

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
        :placeholder="$t('pvz. Socialinės stipendijos')"
        @change="form.validate('title')"
      />
    </FormFieldWrapper>

    <div v-if="!isCreate" class="flex flex-col gap-2">
      <PermalinkField
        :permalink="form.permalink"
        :base-url="pageBaseUrl"
        :disabled="false"
        :view-url="fullPageUrl"
        :hint="$t('Adresas, kuriuo puslapį pasieks studentai.')"
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
        :urls="page.public_urls ?? []"
        :destroy-route="(id) => route('pages.publicUrls.destroy', [page.id, id])"
      />
    </div>
    <PermalinkPreviewHint
      v-else
      :preview="permalinkPreview.preview.value"
      :is-checking="permalinkPreview.isChecking.value"
    />

    <FormFieldWrapper
      id="meta_description"
      :label="$t('Trumpas aprašymas')"
      :hint="$t('Rodomas paieškos rezultatuose ir dalinantis nuoroda.')"
      :char-count="form.meta_description?.length || 0"
      :max-length="160"
    >
      <Textarea
        id="meta_description"
        v-model="form.meta_description"
        :placeholder="$t('Vienas ar du sakiniai apie puslapio turinį…')"
        rows="2"
      />
      <details class="group">
        <summary class="u-touch inline-flex cursor-pointer items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand select-none">
          <ChevronDown class="size-3.5 transition-transform group-open:rotate-180" />
          {{ $t('Kaip atrodys paieškoje') }}
        </summary>
        <SEOPreview
          class="mt-3"
          :title="form.title"
          :description="form.meta_description"
          :url="form.permalink"
          :base-url="pageBaseUrl"
        />
      </details>
    </FormFieldWrapper>

    <FormFieldWrapper
      id="highlights"
      :label="$t('Svarbiausi punktai')"
      :hint="$t('Iki trijų svarbiausių minčių, rodomų puslapio viršuje.')"
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

    <FormFieldWrapper id="content" :label="$t('Turinys')">
      <RichContentFormElement
        v-model="form.content.parts"
        :tenant-id="page.tenant_id"
        @save="$emit('submit:form', form)"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="featured_image"
      :label="`${$t('Viršelio nuotrauka')} (${$t('neprivaloma')})`"
      :hint="$t('Naudojama dalinantis socialiniuose tinkluose.')"
    >
      <ImageUpload
        v-model:url="form.featured_image"
        mode="immediate"
        folder="pages"
        cropper
        full-width
        :existing-url="page.featured_image"
      />
    </FormFieldWrapper>

    <template #aside>
      <FormPanel :title="$t('Paskelbimas')" :icon="Send">
        <FormFieldWrapper id="is_active" :label="$t('Būsena')">
          <div class="grid h-11 w-full grid-cols-2 border border-border bg-background p-0.5" role="group" :aria-label="$t('Būsena')">
            <button
              v-for="option in visibilityOptions"
              :key="String(option.value)"
              type="button"
              :class="[
                segmentVariants({ active: Boolean(form.is_active) === option.value }),
                'w-full',
              ]"
              :aria-pressed="Boolean(form.is_active) === option.value"
              :data-testid="`page-status-${option.value ? 'published' : 'draft'}`"
              @click="form.is_active = option.value"
            >
              <component :is="option.icon" class="size-4 shrink-0" aria-hidden="true" />
              <span>{{ option.label }}</span>
            </button>
          </div>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="publish_time"
          :label="$t('Paskelbimo laikas')"
          :hint="$t('Nuo šio laiko puslapis rodomas paieškoje ir RSS sraute.')"
        >
          <DateTimePicker
            v-model="publishTimeDate"
            variant="popover"
            clearable
            :placeholder="$t('Pasirinkti paskelbimo laiką...')"
          />
        </FormFieldWrapper>

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
            <SelectTrigger id="tenant" class="h-11 w-full">
              <SelectValue :placeholder="$t('forms.placeholders.select_tenant')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <div
          class="flex items-start gap-2 border border-border bg-secondary/40 p-3 text-xs leading-relaxed text-muted-foreground"
          data-testid="page-status-callout"
        >
          <Info class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" />
          <span>{{ statusCallout }}</span>
        </div>
      </FormPanel>

      <FormPanel :title="$t('Struktūra')" :icon="ListTree">
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
              <Button id="parent_page" type="button" variant="outline" voice="plain" class="w-full justify-between bg-background font-normal">
                <span class="truncate" :class="{ 'text-muted-foreground': !form.parent_id }">
                  {{ selectedParentLabel }}
                </span>
                <ChevronDown class="size-4 opacity-50" />
              </Button>
            </template>
          </CollectionSelectDialog>
        </FormFieldWrapper>

        <TagMultiSelect v-model="form.tags" :available-tags="props.availableTags" :hint="$t('Temos, pagal kurias puslapį galima rasti.')" />
      </FormPanel>

      <FormPanel :title="$t('Kalba')" :icon="Languages">
        <FormFieldWrapper
          id="lang"
          :label="$t('Puslapio kalba')"
          required
          :error="form.errors.lang"
          :valid="form.valid('lang')"
          :invalid="form.invalid('lang')"
        >
          <div class="grid h-11 w-full grid-cols-2 border border-border bg-background p-0.5" role="group" :aria-label="$t('Puslapio kalba')">
            <button
              v-for="option in langOptions"
              :key="option.value"
              type="button"
              :class="[
                segmentVariants({ active: form.lang === option.value }),
                'w-full',
              ]"
              :aria-pressed="form.lang === option.value"
              :data-testid="`page-lang-${option.value}`"
              @click="setLang(option.value)"
            >
              <LocaleFlag :locale="option.value" />
              <span>{{ option.label }}</span>
            </button>
          </div>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="other_lang"
          :label="form.lang === 'lt' ? $t('Puslapis anglų kalba') : $t('Puslapis lietuvių kalba')"
          :hint="isCreate
            ? $t('Susiesi išsaugojęs puslapį.')
            : $t('Susieja tą patį turinį kita kalba.')"
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
              <Button id="other_lang" type="button" variant="outline" voice="plain" class="w-full justify-between bg-background font-normal">
                <span class="truncate" :class="{ 'text-muted-foreground': !form.other_lang_id }">
                  {{ selectedOtherLangPage.label }}
                </span>
                <ChevronDown class="size-4 opacity-50" />
              </Button>
            </template>
          </CollectionSelectDialog>
          <Button v-else id="other_lang" type="button" variant="outline" voice="plain" class="w-full justify-between font-normal" disabled>
            <span class="text-muted-foreground">{{ $t('Pasirinkti kitos kalbos puslapį...') }}</span>
            <ChevronDown class="size-4 opacity-50" />
          </Button>
        </FormFieldWrapper>
      </FormPanel>

      <FormPanel :title="$t('Rodymo nustatymai')" :icon="LayoutTemplate" flush>
        <div class="flex flex-col gap-2 border-b border-border p-4">
          <Label class="text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">{{ $t('Išdėstymas') }}</Label>
          <VisualOptionSelect v-model="form.layout" :options="layoutOptions" :columns="3" />
        </div>

        <FormToggleRow
          v-model="form.show_breadcrumbs"
          :label="$t('Rodyti puslapio kelią')"
          :hint="$t('„Pradžia / … / Puslapis“ navigacija viršuje.')"
          data-testid="toggle-breadcrumbs"
        />
        <FormToggleRow
          v-model="form.show_title"
          :label="$t('Rodyti puslapio antraštę')"
          :hint="$t('Pavadinimas ir atnaujinimo laikas puslapio viršuje.')"
          data-testid="toggle-title"
        />
        <FormToggleRow
          v-if="form.layout === 'default'"
          v-model="form.show_table_of_contents"
          :label="$t('Rodyti turinio lentelę')"
          :hint="$t('Automatinis skyrių sąrašas šoninėje juostoje.')"
          data-testid="toggle-toc"
        />

        <div v-if="showTocOverlapWarning" class="border-t border-border p-4">
          <Alert class="border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] text-[var(--status-attention)]">
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
        </div>
      </FormPanel>

      <template v-if="!isCreate">
        <ContentAnalyticsCard
          v-if="page.id"
          :id="page.id"
          type="page"
          :content-date="page.publish_time ?? page.created_at"
        />

        <dl class="border border-border bg-background text-sm" data-testid="page-meta">
          <div v-if="page.created_at" class="flex items-center justify-between gap-4 border-b border-border px-4 py-3 last:border-b-0">
            <dt class="text-muted-foreground">
              {{ $t('Sukurta') }}
            </dt>
            <dd class="font-bold text-foreground">
              {{ formatDate(page.created_at) }}
            </dd>
          </div>
          <div v-if="lastEditedAt" class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-muted-foreground">
              {{ $t('Atnaujinta') }}
            </dt>
            <dd class="font-bold text-foreground">
              {{ formatDate(lastEditedAt) }}
            </dd>
          </div>
        </dl>

        <template v-if="enableDelete">
          <Button
            type="button"
            variant="outline"
            class="border-destructive/40 text-destructive hover:bg-destructive hover:text-destructive-foreground"
            @click="deleteConfirmOpen = true"
          >
            <Trash2 class="size-4" />
            {{ $t('Ištrinti puslapį') }}
          </Button>

          <ConfirmDialog
            v-model:open="deleteConfirmOpen"
            :title="$t('Ištrinti puslapį?')"
            :description="$t('Puslapis bus perkeltas į šiukšlinę.')"
            :confirm-label="$t('Ištrinti')"
            destructive
            @confirm="emit('delete')"
          />
        </template>
      </template>
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref, h } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, ChevronDown, Eye, FilePenLine, Info, Languages, LayoutTemplate, ListTree, Send, Trash2 } from 'lucide-vue-next';

import RichContentFormElement from '../RichContent/RichContentFormElement.vue';
import { getContentType, type BlockWidth } from '../RichContent/Types';
import VisualOptionSelect from '../FormItems/VisualOptionSelect.vue';

import FormFieldWrapper from './FormFieldWrapper.vue';
import PermalinkField from './PermalinkField.vue';
import PermalinkPreviewHint from './PermalinkPreviewHint.vue';
import PublicUrlHistoryCard from './PublicUrlHistoryCard.vue';
import SEOPreview from './SEOPreview.vue';
import TagMultiSelect from './TagMultiSelect.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormToggleRow, StatusBadge } from '@/Components/Patterns';
import ContentAnalyticsCard from '@/Components/Analytics/ContentAnalyticsCard.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { segmentVariants } from '@/Components/ui/control';
import { contentStatuses, type StatusPresentation } from '@/Constants/statuses';
import DateTimePicker from '@/Components/ui/date-picker/DateTimePicker.vue';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { OrderedListInput } from '@/Components/ui/ordered-list-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { usePermalinkPreview } from '@/Composables/usePermalinkPreview';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { Textarea } from '@/Components/ui/textarea';
import { ImageUpload } from '@/Components/ui/upload';
import { formatDate, formatDateTime } from '@/Utils/dateTime';

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

// The redirect note is a consequence of an edit, so it appears only once there is one.
const permalinkChanged = computed(() => form.permalink !== props.page.permalink);

const lastEditedAt = computed(() => props.page.last_edited_at ?? props.page.updated_at);

const filledHighlightCount = computed(() => (form.highlights as string[]).filter(item => item?.trim()).length);

const visibilityOptions = computed(() => [
  { value: false, label: $t('Juodraštis'), icon: FilePenLine },
  { value: true, label: $t('Paskelbta'), icon: Eye },
]);

const currentStatusPresentation = computed<StatusPresentation>(() =>
  form.is_active ? contentStatuses.published : contentStatuses.draft,
);

const langOptions = [
  { value: 'lt', label: 'Lietuvių' },
  { value: 'en', label: 'English' },
] as const;

function setLang(lang: 'lt' | 'en') {
  form.lang = lang;
  form.validate('lang');
}

// PublicPageController gates on is_active alone; a future publish_time only keeps the page out
// of search and RSS until then, so the callout must not promise more than that.
const statusCallout = computed(() => {
  if (!form.is_active) {
    return $t('Juodraštis matomas tik sistemoje — svetainės lankytojai jo nemato.');
  }

  const publishTime = form.publish_time ? new Date(form.publish_time as string) : null;

  if (publishTime && publishTime.getTime() > Date.now()) {
    return $t('Puslapis pasiekiamas pagal nuorodą, o paieškoje pasirodys nuo :date.', {
      date: formatDateTime(publishTime),
    });
  }

  return $t('Paskelbtas puslapis iškart matomas visiems svetainės lankytojams.');
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
  set: (val: Date | null | undefined) => {
    form.publish_time = val ? val.toISOString() : null;
  },
});
</script>
