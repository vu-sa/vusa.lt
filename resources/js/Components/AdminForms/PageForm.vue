<template>
  <FormPage
    :title="isCreate ? $t('Naujas puslapis') : (form.title || $t('Puslapis'))"
    :bar-title
    entity-type="page"
    :back-href="route('pages.index')"
    :back-label="$t('Puslapiai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :available-locales="[]"
    :public-url="fullPageUrl"
    :activity-subject="page.id ? { type: 'page', id: page.id } : undefined"
    :created-at="isCreate ? undefined : page.created_at"
    :updated-at="isCreate ? undefined : lastEditedAt"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate" #title-status>
      <StatusBadge :status="currentStatusPresentation" />
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
        :placeholder="$t('pvz. Socialinės stipendijos')"
        :class="['h-11', fieldSurfaceClass]"
        @change="form.validate('title')"
      />
    </FormFieldWrapper>

    <div v-if="!isCreate" class="flex flex-col gap-2">
      <PermalinkField
        :permalink="form.permalink"
        :base-url="pageBaseUrl"
        :disabled="false"
        :view-url="fullPageUrl"
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
        :class="fieldSurfaceClass"
      />
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
      <ContentPublishPanel
        :published="Boolean(form.is_active)"
        hide-publish-time
        :callout="statusCallout"
        test-id-prefix="page"
        @update:published="form.is_active = $event"
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

      <FormPanel :title="$t('Struktūra')" :icon="ListTree" title-class="text-brand">
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
              <Button
                id="parent_page"
                type="button"
                variant="outline"
                voice="plain"
                :class="['h-11 w-full justify-between font-normal hover:bg-secondary/80', fieldSurfaceClass]"
              >
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

      <ContentLanguagePanel
        v-model:lang="form.lang"
        v-model:other-lang-id="form.other_lang_id"
        collection="pages"
        :candidates="otherLangPages"
        :is-create
        :labels="languageLabels"
        :lang-error="form.errors.lang"
        :lang-valid="form.valid('lang')"
        :lang-invalid="form.invalid('lang')"
        test-id-prefix="page"
        @update:lang="form.validate('lang')"
      />

      <FormPanel :title="$t('Rodymo nustatymai')" :icon="LayoutTemplate" title-class="text-brand" flush>
        <div class="flex flex-col gap-2 border-b border-border p-4" data-slot="form-field">
          <Label class="text-sm font-bold text-foreground">{{ $t('Išdėstymas') }}</Label>
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

      <ContentAnalyticsCard
        v-if="!isCreate && page.id"
        :id="page.id"
        type="page"
        :content-date="page.created_at"
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
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref, h } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, ChevronDown, LayoutTemplate, ListTree, Trash2 } from 'lucide-vue-next';

import RichContentFormElement from '../RichContent/RichContentFormElement.vue';
import { getContentType, type BlockWidth } from '../RichContent/Types';
import VisualOptionSelect from '../FormItems/VisualOptionSelect.vue';

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
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { contentStatuses, type StatusPresentation } from '@/Constants/statuses';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { OrderedListInput } from '@/Components/ui/ordered-list-input';
import { resolveTenantPublicHost, resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { usePermalinkPreview } from '@/Composables/usePermalinkPreview';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { Textarea } from '@/Components/ui/textarea';
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
} as unknown as Record<string, unknown>;

const form = props.rememberKey
  ? useForm(props.rememberKey, formData).withPrecognition(props.submitMethod, props.submitUrl)
  : useForm(formData).withPrecognition(props.submitMethod, props.submitUrl);

// Default to the sole assignable tenant; a multi-tenant actor (e.g. super admin) picks explicitly.
if (isCreate.value && form.tenant_id == null) {
  form.tenant_id = pickDefaultTenantId(props.assignableTenants);
}

// Set validation timeout to 500ms for faster feedback
form.setValidationTimeout(500);

// Preview-only: the actual permalink is generated server-side on create (GenerateUniqueSlug).
// Title getter returns '' outside create mode so the composable's own length guard no-ops it —
// there is nothing to preview once the record exists and the real permalink is editable.
const permalinkPreview = usePermalinkPreview('page', () => (isCreate.value ? form.title ?? '' : ''), () => form.lang ?? 'lt');

// Ensure highlights is always an array
if (!Array.isArray(form.highlights)) {
  form.highlights = [];
}

const pageBaseUrl = computed(() => resolveTenantPublicHost(props.page.tenant?.id));

// The bar states what is saved; the heading and fields follow the edit. Props refresh after each save.
const barTitle = computed(() => (isCreate.value ? $t('Naujas puslapis') : (props.page.title || $t('Puslapis'))));

const currentStatusPresentation = computed<StatusPresentation>(() =>
  props.page.is_active ? contentStatuses.published : contentStatuses.draft,
);

// Construct full page URL using route helper
const fullPageUrl = computed(() => {
  // A saved inactive page 404s publicly, so there is nothing to open yet.
  if (!props.page.id || !props.page.is_active || !form.permalink || !props.page.tenant) return undefined;

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

const statusCallout = computed(() => (form.is_active
  ? $t('Paskelbtas puslapis iškart matomas visiems svetainės lankytojams.')
  : $t('Juodraštis matomas tik sistemoje — svetainės lankytojai jo nemato.')));

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

const languageLabels = computed(() => ({
  lang: $t('Puslapio kalba'),
  otherLangLt: $t('Puslapis lietuvių kalba'),
  otherLangEn: $t('Puslapis anglų kalba'),
  createHint: $t('Susiesi išsaugojęs puslapį.'),
  editHint: $t('Susieja tą patį turinį kita kalba.'),
  createPlaceholder: $t('Pasirinkti kitos kalbos puslapį...'),
  dialogTitle: $t('Kitos kalbos puslapis'),
  searchPlaceholder: $t('Ieškoti puslapio pagal pavadinimą...'),
  emptyMessage: $t('Puslapių nerasta'),
}));

const parentDialogOpen = ref(false);

// Bridge: the dialog stores parent_id; this local label tracks the current selection
// without needing a full candidate-page list from the server (unlike ContentLanguagePanel
// — a picker scoped by the form's own reactive lang/tenant needs no such list).
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
</script>
