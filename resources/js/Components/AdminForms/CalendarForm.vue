<template>
  <FormPage
    :title="isCreate ? $t('Naujas renginys') : (calendarTitle || $t('Renginys'))"
    :bar-title
    entity-type="calendar"
    :back-href="route('calendar.index')"
    :back-label="$t('Kalendorius')"
    :processing="form.processing"
    :disabled="readOnly"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="readOnly ? 'view' : isCreate ? 'create' : 'edit'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    :public-url="publicCalendarUrl"
    :activity-subject="calendar?.id ? { type: 'calendar', id: calendar.id } : undefined"
    :created-at="isCreate ? undefined : calendar?.created_at"
    :updated-at="isCreate ? undefined : calendar?.updated_at"
    @update:locale="activeLocale = $event as 'lt' | 'en'"
    @submit="!readOnly && emit('submit:form', form)"
  >
    <template v-if="!isCreate" #title-status>
      <StatusBadge :status="calendar.is_draft ? contentStatuses.draft : contentStatuses.published" />
    </template>

    <template v-if="!isCreate && (meeting || canCreateMeeting)" #locale-addon>
      <a
        v-if="meeting"
        :href="route('meetings.show', { meeting: meeting.id })"
        target="_blank"
        rel="noopener noreferrer"
        :class="[
          'inline-flex h-11 items-center gap-1.5 border border-border bg-background px-3',
          'text-xs font-semibold text-foreground transition-colors hover:border-brand hover:text-brand',
        ]"
        :title="$t('Peržiūrėti posėdį naujame lange')"
      >
        <CalendarClock class="size-3.5 text-brand" />
        <span>{{ $t('Susietas su posėdžiu') }}</span>
        <ArrowUpRight class="size-3 text-muted-foreground" />
      </a>
      <Button
        v-if="!meeting && canCreateMeeting"
        type="button"
        variant="outline"
        class="min-h-11"
        @click="openMeetingCreation"
      >
        <CalendarClock class="size-4" />
        {{ $t('meetings.announce.create_from_event') }}
      </Button>
    </template>

    <!-- Title -->
    <FormFieldWrapper
      id="title"
      :label="`${$t('forms.fields.title')} (${activeLocale.toUpperCase()})`"
      :required="activeLocale === 'lt'"
      :char-count="form.title?.[activeLocale]?.length || 0"
      :error="form.errors[`title.${activeLocale}`]"
      :validating="form.validating"
      :valid="form.valid(`title.${activeLocale}`)"
      :invalid="form.invalid(`title.${activeLocale}`)"
    >
      <Input
        id="title"
        v-model="form.title[activeLocale]"
        type="text"
        :placeholder="$t('Įrašyti renginio pavadinimą...')"
        :class="['h-11', fieldSurfaceClass]"
        :disabled="readOnly"
        @change="form.validate(`title.${activeLocale}`)"
      />
    </FormFieldWrapper>

    <!-- Permalink & URL history -->
    <div v-if="!isCreate" class="flex flex-col gap-2">
      <PermalinkField
        :permalink="form.permalink?.[activeLocale]"
        :base-url="calendarBaseUrl"
        :view-url="publicCalendarUrl"
        :label="`${$t('Nuoroda')} (${activeLocale.toUpperCase()})`"
        :disabled="readOnly"
        :warning="permalinkChanged
          ? $t('Pakeitus nuorodą, sena nuoroda ir toliau nukreips į šį puslapį — nebereikalingas senas nuorodas galėsite ištrinti.')
          : undefined"
        :validating="form.validating"
        :valid="form.valid(`permalink.${activeLocale}`)"
        :invalid="form.invalid(`permalink.${activeLocale}`)"
        @update:permalink="form.permalink = { ...form.permalink, [activeLocale]: $event }"
        @change="form.validate(`permalink.${activeLocale}`)"
      />
      <PublicUrlHistoryCard
        :urls="combinedPublicUrls"
        :destroy-route="destroyPublicUrl"
      />
    </div>
    <PermalinkPreviewHint
      v-else
      :preview="permalinkPreview"
    />

    <!-- Date & Time + All-day toggle -->
    <div class="flex flex-col gap-3">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormFieldWrapper
          id="date"
          :label="$t('Renginio pradžia')"
          required
          :error="form.errors.date"
          :hint="meeting ? $t('meetings.announce.timing_locked') : undefined"
          :valid="form.valid('date')"
          :invalid="form.invalid('date')"
        >
          <DateTimePicker
            v-model="startDate"
            variant="popover"
            :disabled="readOnly || Boolean(meeting)"
            @update:model-value="form.validate('date')"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="end_date"
          :label="$t('Renginio pabaiga')"
          :error="form.errors.end_date"
          :hint="$t('Jeigu nenurodytas, renginys rodomas kaip 1 val. trukmės.')"
        >
          <DateTimePicker
            v-model="endDate"
            variant="popover"
            clearable
            :disabled="readOnly || Boolean(meeting)"
          />
        </FormFieldWrapper>
      </div>

      <label class="flex items-center gap-2 text-sm text-muted-foreground select-none">
        <Switch
          id="is_all_day"
          v-model="form.is_all_day"
          :disabled="readOnly"
          @update:model-value="isAllDayTouched = true"
        />
        <span>{{ $t('Visos dienos renginys') }}</span>
      </label>
    </div>

    <!-- Location & Remote toggle -->
    <FormFieldWrapper
      id="location"
      :label="`${$t('Renginio vieta')} (${activeLocale.toUpperCase()})`"
      :hint="form.is_remote
        ? $t('Nuotolinis renginys — nurodyta vieta nerodoma')
        : $t('Kuo tikslesnis adresas, tuo tiksliau renginio puslapyje bus parodytas žemėlapis')"
    >
      <Input
        id="location"
        v-model="form.location[activeLocale]"
        type="text"
        :placeholder="$t('pvz. Universiteto g. 3, Vilnius arba Teatro salė')"
        :class="['h-11', fieldSurfaceClass]"
        :disabled="readOnly || form.is_remote"
      />
      <label class="mt-2 flex items-center gap-2 text-sm text-muted-foreground select-none">
        <Switch id="is_remote" v-model="form.is_remote" :disabled="readOnly" />
        <span>{{ $t('Nuotolinis renginys') }}</span>
      </label>
    </FormFieldWrapper>

    <!-- Description -->
    <FormFieldWrapper
      id="description"
      :label="`${$t('Aprašymas')} (${activeLocale.toUpperCase()})`"
    >
      <template v-if="readOnly">
        <!-- eslint-disable-next-line vue/no-v-html -- calendar rich text is sanitized when written -->
        <div class="rc-prose tracking-normal" v-html="form.description?.[activeLocale]" />
      </template>
      <TiptapEditor
        v-else
        :key="activeLocale"
        v-model="form.description[activeLocale]"
        preset="full"
        html
        framed
      />
    </FormFieldWrapper>

    <!-- Photos section -->
    <FormPanel :title="$t('Nuotraukos')" :icon="Image" title-class="text-brand">
      <!-- Main Image -->
      <FormFieldWrapper
        id="main_image"
        :label="$t('Pagrindinė nuotrauka')"
        required
        :hint="$t('Rodoma renginio kortelėje ir viršuje')"
        :error="form.errors.main_image"
      >
        <ImageUpload
          v-if="!readOnly"
          v-model:focal-point-value="form.main_image_focal_point"
          :max="1"
          :existing-url="existingMainImageUrl"
          cropper
          compress
          focal-point
          folder="calendar"
          @update:file="handleMainImageUpdate"
        />
        <img
          v-else-if="existingMainImageUrl"
          :src="existingMainImageUrl"
          :alt="$t('Pagrindinė nuotrauka')"
          class="aspect-video w-full border border-border object-cover"
        >
      </FormFieldWrapper>

      <!-- Gallery Images -->
      <FormFieldWrapper
        id="images"
        :label="$t('Galerijos nuotraukos')"
        :hint="`${$t('Papildomos nuotraukos, rodomos galerijoje')}. ${$t('Nuotraukos optimizuojamos automatiškai prieš įkėlimą.')}`"
      >
        <ImageUpload
          v-if="!readOnly"
          v-model:files="newGalleryImages"
          :max="20"
          :existing-urls="existingGalleryImages"
          cropper
          compress
          folder="calendar"
          @remove:existing="removeExistingImage"
        />
        <div v-else-if="existingGalleryImages.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <img
            v-for="image in existingGalleryImages"
            :key="image.id"
            :src="image.url"
            :alt="image.name"
            class="aspect-video w-full border border-border object-cover"
          >
        </div>
      </FormFieldWrapper>
    </FormPanel>

    <!-- Links and Promotion section -->
    <FormPanel :title="$t('Nuorodos ir viešinimas')" :icon="Link2" title-class="text-brand">
      <FormFieldWrapper
        id="cto_url"
        :label="`${$t('Renginio nuoroda')} (${activeLocale.toUpperCase()})`"
        :hint="$t('Nuoroda į pagrindinį renginio puslapį arba registracijos formą')"
      >
        <Input
          id="cto_url"
          v-model="form.cto_url[activeLocale]"
          type="url"
          placeholder="https://..."
          :class="['h-11', fieldSurfaceClass]"
          :disabled="readOnly"
        />
      </FormFieldWrapper>

      <div class="grid gap-4 sm:grid-cols-2">
        <FormFieldWrapper
          id="facebook_url"
          :label="$t('forms.fields.facebook_url')"
          :error="form.errors.facebook_url"
          :valid="form.valid('facebook_url')"
          :invalid="form.invalid('facebook_url')"
        >
          <div class="flex items-center gap-2">
            <ISimpleIconsFacebook class="h-4 w-4 shrink-0 text-[#1877F2]" />
            <Input
              id="facebook_url"
              v-model="form.facebook_url"
              type="url"
              :disabled="readOnly"
              placeholder="https://www.facebook.com/events/..."
              :class="['h-11', fieldSurfaceClass]"
              @change="form.validate('facebook_url')"
            />
          </div>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="video_url"
          :label="$t('Youtube video kodas')"
          :hint="$t('Tik video kodas, ne pilna nuoroda')"
        >
          <div class="flex items-center gap-2">
            <span class="shrink-0 text-sm text-muted-foreground">youtube.com/embed/</span>
            <Input
              id="video_url"
              v-model="form.video_url"
              type="text"
              placeholder="dQw4w9WgXcQ"
              :class="['h-11 flex-1', fieldSurfaceClass]"
              :disabled="readOnly"
            />
          </div>
        </FormFieldWrapper>
      </div>
    </FormPanel>

    <!-- Aside column -->
    <template #aside>
      <ContentPublishPanel
        :published="!form.is_draft"
        hide-publish-time
        :callout="statusCallout"
        test-id-prefix="calendar"
        @update:published="form.is_draft = !$event"
      >
        <TenantSelectField
          v-if="isCreate || assignableTenants.length > 1"
          v-model="form.tenant_id"
          :tenants="assignableTenants"
          :error="form.errors.tenant_id"
          :valid="form.valid('tenant_id')"
          :invalid="form.invalid('tenant_id')"
          :disabled="readOnly"
          @update:model-value="form.validate('tenant_id')"
        />
      </ContentPublishPanel>

      <!-- Related meeting panel in modern admin style -->
      <FormPanel v-if="meeting" :title="$t('Susietas posėdis')" :icon="CalendarClock" title-class="text-brand">
        <div class="flex flex-col gap-3 text-xs">
          <div class="flex items-start justify-between gap-2">
            <a
              :href="route('meetings.show', { meeting: meeting.id })"
              target="_blank"
              rel="noopener noreferrer"
              class="group inline-flex items-center gap-1 font-semibold text-foreground hover:text-brand hover:underline"
            >
              <span>{{ meeting.institution_name ?? $t('Posėdis') }}</span>
              <ArrowUpRight class="size-3.5 text-muted-foreground group-hover:text-brand" />
            </a>
            <span v-if="meeting.trashed" class="border border-destructive/30 bg-destructive/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-destructive">
              {{ $t('meetings.announce.form_alert_trashed') }}
            </span>
          </div>

          <div class="flex items-center gap-2 text-muted-foreground">
            <span>{{ meeting.agenda_items_count }} {{ $t('darbotvarkės punktai') }}</span>
          </div>

          <div class="border-t border-border pt-2 leading-relaxed text-muted-foreground">
            {{ form.is_draft
              ? $t('meetings.announce.form_alert_draft')
              : $t('meetings.announce.form_alert_published') }}
          </div>

          <div class="text-[11px] italic text-muted-foreground/80">
            {{ $t('meetings.announce.timing_locked') }}
          </div>
        </div>
      </FormPanel>

      <FormPanel :title="$t('Renginio nustatymai')" :icon="SlidersHorizontal" title-class="text-brand">
        <FormFieldWrapper
          id="event_type"
          :label="$t('Renginio tipas')"
          :error="form.errors.event_type_id"
          :valid="form.valid('event_type_id')"
          :invalid="form.invalid('event_type_id')"
        >
          <Select v-model="eventTypeIdString" :disabled="readOnly" @update:model-value="form.validate('event_type_id')">
            <SelectTrigger id="event_type" :class="['h-11 w-full', fieldSurfaceClass]" :disabled="readOnly">
              <SelectValue :placeholder="$t('Pasirinkti renginio tipą...')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="NO_EVENT_TYPE_VALUE">
                {{ $t('Nenurodyta') }}
              </SelectItem>
              <SelectItem v-for="type in eventTypes" :key="type.id" :value="String(type.id)">
                {{ type.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="organizer"
          :label="`${$t('Organizatorius')} (${activeLocale.toUpperCase()})`"
          :hint="`${$t('Organizatorius')}, ${$t('jeigu neįrašytas, bus')} ${defaultOrganizer}`"
        >
          <Input
            id="organizer"
            v-model="form.organizer[activeLocale]"
            type="text"
            :placeholder="defaultOrganizer"
            :class="['h-11', fieldSurfaceClass]"
            :disabled="readOnly"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="audience"
          :label="$t('Viešinimo auditorija')"
          :hint="$t('Ar renginys skirtas tarptautiniams studentams')"
        >
          <FormSegmentedControl
            v-model="form.is_international"
            :options="audienceOptions"
            :disabled="readOnly"
            :aria-label="$t('Viešinimo auditorija')"
            test-id-prefix="calendar-audience"
          />
        </FormFieldWrapper>
      </FormPanel>

      <FormPanel :title="$t('Temos')" :icon="Tags" title-class="text-brand">
        <TagMultiSelect
          v-model="form.tags"
          :available-tags="props.availableTags"
          :disabled="readOnly"
        />
      </FormPanel>

      <FormPanel :title="$t('Rodymo nustatymai')" :icon="LayoutTemplate" title-class="text-brand" flush>
        <div class="flex flex-col gap-2 p-4" data-slot="form-field">
          <Label class="text-[11px] font-bold uppercase tracking-[0.18em] text-foreground">{{ $t('Renginio vaizdas') }}</Label>
          <VisualOptionSelect
            v-model="heroStyle"
            :options="heroStyleOptions"
            :columns="3"
            :disabled="readOnly"
          />
        </div>
      </FormPanel>
    </template>

    <!-- Danger Zone Slot -->
    <template v-if="!isCreate && enableDelete && !readOnly" #danger-zone>
      <Button
        type="button"
        variant="outline"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground"
        @click="deleteConfirmOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Ištrinti renginį') }}
      </Button>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti renginį?')"
        :description="$t('Renginys bus perkeltas į šiukšlinę.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, h, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  ArrowUpRight,
  CalendarClock,
  Globe,
  Image,
  LayoutTemplate,
  Link2,
  SlidersHorizontal,
  Tags,
  Trash2,
} from 'lucide-vue-next';

import ContentPublishPanel from './ContentPublishPanel.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import PermalinkField from './PermalinkField.vue';
import PermalinkPreviewHint from './PermalinkPreviewHint.vue';
import PublicUrlHistoryCard, { type PublicUrlRow } from './PublicUrlHistoryCard.vue';
import TagMultiSelect from './TagMultiSelect.vue';
import TenantSelectField, { pickDefaultTenantId } from './TenantSelectField.vue';

import ISimpleIconsFacebook from '~icons/simple-icons/facebook';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormSegmentedControl, StatusBadge, type FormSegmentOption } from '@/Components/Patterns';
import { isSameDay } from '@/Utils/IntlTime';
import { localizedRoute, localizedSlug } from '@/Utils/LocalizedRoutes';
import { generateSlug } from '@/Utils/String';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { ImageUpload } from '@/Components/ui/upload';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import VisualOptionSelect from '@/Components/FormItems/VisualOptionSelect.vue';
import { contentStatuses } from '@/Constants/statuses';
import { useActionWindow } from '@/Composables/useActionWindow';
import { resolveTenantPublicHost } from '@/Composables/useTenantSubdomain';

const props = withDefaults(defineProps<{
  calendar: CalendarEventForm;
  eventTypes: App.Entities.EventType[];
  availableTags?: App.Entities.Tag[];
  assignableTenants: App.Entities.Tenant[];
  /** Set when this event is the public announcement of a meeting. */
  meeting?: {
    id: string;
    start_time: string;
    title: string;
    trashed: boolean;
    agenda_items_count: number;
    institution_name: string | null;
  } | null;
  rememberKey?: string;
  submitUrl: string;
  submitMethod: 'post' | 'patch';
  enableDelete?: boolean;
  readOnly?: boolean;
}>(), {
  availableTags: () => [],
  meeting: null,
  rememberKey: undefined,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => !!props.rememberKey);
const canCreateMeeting = computed(() => Boolean(usePage().props.auth?.can?.create?.meeting));
const { open: openActionWindow } = useActionWindow();

function openMeetingCreation(): void {
  openActionWindow({
    flow: 'meeting.create',
    calendarEvent: { id: Number(props.calendar.id), title: calendarTitle.value, date: String(props.calendar.date) },
  });
}

const deleteConfirmOpen = ref(false);
const activeLocale = ref<'lt' | 'en'>('lt');

const fieldIds: Record<string, string> = {
  'title.lt': 'title',
  'title.en': 'title',
  'tenant_id': 'tenant',
  'date': 'date',
  'end_date': 'end_date',
  'event_type_id': 'event_type',
  'facebook_url': 'facebook_url',
  'permalink.lt': 'permalink',
  'permalink.en': 'permalink',
};

// Store existing main_image URL for display in MediaUpload
const existingMainImageUrl = ref<string | null>(props.calendar.main_image_url ?? null);

// Prepare form data - main_image will be File | null for submission
const formData = {
  ...props.calendar,
  title: {
    lt: props.calendar.title?.lt ?? '',
    en: props.calendar.title?.en ?? '',
  },
  location: {
    lt: props.calendar.location?.lt ?? '',
    en: props.calendar.location?.en ?? '',
  },
  organizer: {
    lt: props.calendar.organizer?.lt ?? '',
    en: props.calendar.organizer?.en ?? '',
  },
  cto_url: {
    lt: props.calendar.cto_url?.lt ?? '',
    en: props.calendar.cto_url?.en ?? '',
  },
  description: {
    lt: props.calendar.description?.lt ?? '',
    en: props.calendar.description?.en ?? '',
  },
  permalink: {
    lt: props.calendar.permalink?.lt ?? '',
    en: props.calendar.permalink?.en ?? '',
  },
  main_image: null as File | null,
  tags: props.calendar.tags ?? [],
} as unknown as Record<string, unknown>;

const form = props.rememberKey
  ? useForm(props.rememberKey, formData).withPrecognition(props.submitMethod, props.submitUrl)
  : useForm(formData).withPrecognition(props.submitMethod, props.submitUrl);

if (isCreate.value && form.tenant_id == null) {
  form.tenant_id = pickDefaultTenantId(props.assignableTenants);
}

// Set validation timeout
form.setValidationTimeout(500);

// Auto-fill the permalink from the title as the user types, for a new event only.
if (isCreate.value) {
  watch(() => form.title?.lt, (title) => {
    form.permalink = { ...form.permalink, lt: generateSlug(String(title || '')) };
  });
  watch(() => form.title?.en, (title) => {
    form.permalink = { ...form.permalink, en: generateSlug(String(title || '')) };
  });
}

// Default "all day" for the common case (a multi-day event, where clock times aren't the
// point) without making the admin think about the flag. Once they touch the switch directly,
// their choice wins from then on. An existing event's flag already reflects a deliberate past
// choice, so editing starts "touched" — nudging the dates a little must not silently flip it.
const isAllDayTouched = ref(!isCreate.value);

watch([() => form.date, () => form.end_date], () => {
  if (isAllDayTouched.value || !form.date || !form.end_date) {
    return;
  }

  form.is_all_day = !isSameDay(new Date(form.date), new Date(form.end_date));
});

const missingLocaleCounts = computed(() => ({
  lt: !form.title?.lt ? 1 : 0,
  en: !form.title?.en ? 1 : 0,
}));

const calendarTitle = computed(() =>
  form.title?.[activeLocale.value] || form.title?.lt || form.title?.en || '',
);

const savedTitle = computed(() =>
  props.calendar.title?.[activeLocale.value] || props.calendar.title?.lt || props.calendar.title?.en || '',
);

// The bar states what is saved; the heading and fields follow the edit. Props refresh after each save.
const barTitle = computed(() => (isCreate.value ? $t('Naujas renginys') : (savedTitle.value || $t('Renginys'))));

// The year the event's URL is nested under — same value the backend derives from `date`.
const eventYear = computed(() => (form.date ? new Date(form.date).getFullYear() : new Date().getFullYear()));

const calendarBaseUrl = computed(() => {
  const lang = activeLocale.value;
  return `${resolveTenantPublicHost(props.calendar?.tenant?.id)}/${lang}/${localizedSlug('calendarString', lang)}/${eventYear.value}`;
});

const publicCalendarUrl = computed(() => {
  if (!props.calendar?.id || props.calendar.is_draft) return undefined;
  const permalink = form.permalink?.[activeLocale.value];
  if (!permalink) return undefined;

  return localizedRoute('calendar.show', { year: eventYear.value, permalink }, activeLocale.value);
});

// The redirect note is a consequence of an edit, so it appears only once there is one.
const permalinkChanged = computed(() => form.permalink?.[activeLocale.value] !== props.calendar?.permalink?.[activeLocale.value]);

const permalinkPreview = computed(() => {
  const slug = form.permalink?.[activeLocale.value];
  if (!slug) return null;
  return {
    permalink: slug,
    url: `${calendarBaseUrl.value}/${slug}`,
  };
});

const legacyDateUrlLt = computed(() => props.calendar.legacy_date_urls?.lt ?? null);
const legacyDateUrlEn = computed(() => props.calendar.legacy_date_urls?.en ?? null);

const combinedPublicUrls = computed<PublicUrlRow[]>(() => {
  const urls: PublicUrlRow[] = [
    ...(props.calendar.public_urls ?? []),
  ];
  if (legacyDateUrlLt.value) {
    urls.push({ url: legacyDateUrlLt.value, locale: 'lt' });
  }
  if (legacyDateUrlEn.value) {
    urls.push({ url: legacyDateUrlEn.value, locale: 'en' });
  }
  return urls;
});

const destroyPublicUrl = (id: number) => (
  props.calendar.id ? route('calendar.publicUrls.destroy', [props.calendar.id, id]) : ''
);

const statusCallout = computed(() => {
  if (form.is_draft) {
    return $t('Juodraštis matomas tik sistemoje — svetainės lankytojai jo nemato.');
  }

  return $t('Paskelbtas renginys matomas viešame kalendoriuje.');
});

// Handle main image update explicitly
function handleMainImageUpdate(file: File | null) {
  form.main_image = file;
}

// Hero style icons as simple SVG representations
const CardHeroIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 4, y: 4, width: 88, height: 56, rx: 6 }),
  h('path', { d: 'M4 40 h88' }),
  h('path', { d: 'M12 48 h20' }),
  h('path', { d: 'M12 30 l10 -8 l12 10 l8 -6 l14 10' }),
  h('circle', { cx: 74, cy: 16, r: 4 }),
]);

const SplitHeroIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 4, y: 4, width: 88, height: 56, rx: 6 }),
  h('path', { d: 'M40 4 v56' }),
  h('path', { d: 'M48 16 h32 M48 26 h24 M48 36 h32' }),
]);

const MinimalHeroIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('path', { d: 'M8 20 h80' }),
  h('path', { d: 'M8 32 h56' }),
  h('path', { d: 'M8 44 h32' }),
  h('circle', { cx: 82, cy: 44, r: 6 }),
]);

const heroStyleOptions = [
  {
    value: 'card',
    label: $t('Didelė kortelė'),
    description: $t('Nuotrauka fone'),
    icon: CardHeroIcon,
  },
  {
    value: 'split',
    label: $t('Nuotrauka šalia'),
    description: $t('Kortelė su nuotrauka'),
    icon: SplitHeroIcon,
  },
  {
    value: 'minimal',
    label: $t('Minimalus'),
    description: $t('Be nuotraukos'),
    icon: MinimalHeroIcon,
  },
];

const heroStyle = computed({
  get: () => form.hero_style ?? 'card',
  set: (val: string) => {
    form.hero_style = val as CalendarEventForm['hero_style'];
  },
});

const defaultOrganizer = computed(() => {
  return (
    props.calendar.tenant?.shortname ?? usePage().props.auth?.user?.tenants?.[0]?.shortname
  );
});

const NO_EVENT_TYPE_VALUE = '__none__';

// Handle event_type_id as string for Select component
const eventTypeIdString = computed({
  get: () => form.event_type_id ? String(form.event_type_id) : NO_EVENT_TYPE_VALUE,
  set: (val: string) => {
    form.event_type_id = val === NO_EVENT_TYPE_VALUE ? null : parseInt(val);
  },
});

const audienceOptions = computed<FormSegmentOption<boolean>[]>(() => [
  { value: true, label: $t('Visi studentai'), icon: Globe, testId: 'audience-all' },
  { value: false, label: $t('Tik LT'), testId: 'audience-lt' },
]);

// Date pickers compatibility - convert string to Date
const startDate = computed({
  get: () => form.date ? new Date(form.date) : undefined,
  set: (val: Date | undefined) => {
    if (!val) {
      form.date = null;
      return;
    }
    // Format in local timezone to avoid UTC conversion
    const localISOString = new Date(val.getTime() - (val.getTimezoneOffset() * 60000))
      .toISOString()
      .slice(0, 19)
      .replace('T', ' ');
    form.date = localISOString;
  },
});

const endDate = computed({
  get: () => form.end_date ? new Date(form.end_date) : undefined,
  set: (val: Date | undefined) => {
    if (!val) {
      form.end_date = null;
      return;
    }
    // Format in local timezone to avoid UTC conversion
    const localISOString = new Date(val.getTime() - (val.getTimezoneOffset() * 60000))
      .toISOString()
      .slice(0, 19)
      .replace('T', ' ');
    form.end_date = localISOString;
  },
});

// Gallery images handling
interface ExistingImage {
  id: string | number;
  url: string;
  name: string;
}

// Existing gallery images from the server
const existingGalleryImages = ref<ExistingImage[]>(
  (props.calendar.images ?? []).map((img: Record<string, unknown>) => ({
    id: img.id as string | number,
    url: (img.url ?? img.original_url ?? '') as string,
    name: (img.name ?? 'image.jpg') as string,
  })),
);

// New gallery images to be uploaded
const newGalleryImages = ref<File[]>([]);

// Sync new gallery images to form.images for submission
watch(newGalleryImages, (files) => {
  form.images = files;
}, { deep: true });

// Remove existing gallery image
function removeExistingImage(img: { id: string | number; url: string }) {
  if (!props.readOnly && props.calendar.id) {
    router.post(
      route('calendar.destroyMedia', {
        calendar: props.calendar.id,
        media: img.id,
      }),
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          existingGalleryImages.value = existingGalleryImages.value.filter(i => i.id !== img.id);
        },
      },
    );
  }
}
</script>
