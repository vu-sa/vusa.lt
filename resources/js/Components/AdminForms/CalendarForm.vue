<template>
  <FormPage
    :title="isCreate ? $t('Naujas renginys') : (calendarTitle || $t('Renginys'))"
    :head-title="isCreate ? $t('Naujas renginys') : (calendarTitle || $t('Renginys'))"
    :back-href="route('calendar.index')"
    :back-label="$t('Kalendorius')"
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
      <ActivityLogSheet v-if="props.calendar?.id" subject-type="calendar" :subject-id="String(props.calendar.id)" />
    </template>

    <!-- Status Header -->
    <FormStatusHeader
      :is-published="!form.is_draft"
      :server-is-published="!props.calendar.is_draft"
      :links="statusLinks"
      :is-create
      @update:is-published="form.is_draft = !$event"
    />

    <!-- This event stands for a meeting: publishing it opens that meeting's agenda to the public. -->
    <Alert v-if="meeting" class="mb-6">
      <CalendarClock class="size-4" />
      <AlertTitle>{{ $t('meetings.announce.form_alert_title') }}</AlertTitle>
      <AlertDescription>
        <p>
          {{ form.is_draft
            ? $t('meetings.announce.form_alert_draft')
            : $t('meetings.announce.form_alert_published') }}
        </p>
        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
          <a :href="route('meetings.show', { meeting: meeting.id })" class="inline-flex items-center gap-1 font-medium underline underline-offset-2">
            {{ meeting.institution_name ?? $t('Posėdis') }}
            <ArrowUpRight class="size-3" />
          </a>
          <span class="text-muted-foreground">
            {{ meeting.agenda_items_count }} {{ $t('darbotvarkės punktai') }}
          </span>
          <span v-if="meeting.trashed" class="font-medium text-destructive">
            {{ $t('meetings.announce.form_alert_trashed') }}
          </span>
        </div>
      </AlertDescription>
    </Alert>

    <!-- Section 1: Main Info -->
    <FormSection
      data-section="1"
      :title="$t('forms.context.main_info')"
      :description="$t('Pagrindiniai renginio nustatymai')"
    >
      <div class="space-y-4">
        <!-- Title -->
        <FormFieldWrapper
          id="title"
          :label="$t('forms.fields.title')"
          required
          :hint="$t('Renginio pavadinimas abiem kalbom')"
          :error="form.errors['title.lt']"
          :validating="form.validating"
          :valid="form.valid('title.lt')"
          :invalid="form.invalid('title.lt')"
        >
          <MultiLocaleInput v-model:input="form.title" @blur="form.validate('title.lt')" />
        </FormFieldWrapper>

        <!-- Organizer & Location -->
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper
            id="organizer"
            :label="$t('Organizatorius')"
            :hint="`${$t('Kas organizuoja renginį')}. ${$t('Organizatorius')}, ${$t('jeigu neįrašytas, bus')} ${defaultOrganizer}`"
          >
            <MultiLocaleInput v-model:input="form.organizer" />
          </FormFieldWrapper>

          <FormFieldWrapper
            id="location"
            :label="$t('Renginio vieta')"
            :hint="form.is_remote ? $t('Nuotolinis renginys — nurodyta vieta nerodoma') : $t('Kuo tikslesnis adresas, tuo tiksliau renginio puslapyje bus parodytas žemėlapis')"
          >
            <MultiLocaleInput v-model:input="form.location" :disabled="form.is_remote" />
            <label class="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
              <Switch id="is_remote" v-model="form.is_remote" />
              {{ $t('Nuotolinis renginys') }}
            </label>
          </FormFieldWrapper>
        </div>

        <!-- Event type & Tenant -->
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper
            id="event_type"
            :label="$t('Renginio tipas')"
            :error="form.errors.event_type_id"
            :valid="form.valid('event_type_id')"
            :invalid="form.invalid('event_type_id')"
          >
            <Select v-model="eventTypeIdString" @update:model-value="form.validate('event_type_id')">
              <SelectTrigger id="event_type">
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
            id="tenant"
            :label="$t('Padalinys')"
            required
            :error="form.errors.tenant_id"
            :valid="form.valid('tenant_id')"
            :invalid="form.invalid('tenant_id')"
          >
            <Select v-model="tenantIdString" @update:model-value="form.validate('tenant_id')">
              <SelectTrigger id="tenant">
                <SelectValue :placeholder="$t('VU SA ...')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.shortname }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
        </div>

        <!-- Audience toggle -->
        <FormFieldWrapper
          id="audience"
          :label="$t('Viešinimo auditorija')"
          :hint="$t('Ar renginys skirtas tarptautiniams studentams')"
        >
          <div class="flex gap-2">
            <Button
              type="button"
              :variant="form.is_international ? 'default' : 'outline'"
              class="flex-1 gap-2"
              @click="form.is_international = true"
            >
              <Globe class="h-4 w-4" />
              {{ $t('Visi studentai') }}
            </Button>
            <Button
              type="button"
              :variant="form.is_international ? 'outline' : 'default'"
              class="flex-1"
              @click="form.is_international = false"
            >
              {{ $t('Tik LT') }}
            </Button>
          </div>
        </FormFieldWrapper>

        <TagMultiSelect v-model="form.tags" :available-tags="props.availableTags" />

        <!-- Hero style picker -->
        <FormFieldWrapper
          id="hero_style"
          :label="$t('Renginio vaizdas')"
          :hint="$t('Kaip renginio puslapio viršus atrodys lankytojams')"
        >
          <VisualOptionSelect v-model="heroStyle" :options="heroStyleOptions" :columns="3" icon-class="h-12 w-20" />
        </FormFieldWrapper>
      </div>
    </FormSection>

    <!-- Section 2: Date & Time -->
    <FormSection
      data-section="2"
      :title="$t('Renginio laikas')"
      :description="`${$t('Kada vyks renginys')}. ${$t('Jeigu nėra nurodytas pabaigos laikas, kalendoriuje renginys rodomas kaip 1 val. trukmės.')}`"
    >
      <div class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper
            id="date"
            :label="$t('Renginio pradžia')"
            required
            :error="form.errors.date"
            :hint="meeting ? $t('meetings.announce.timing_locked') : undefined"
            :valid="form.valid('date')"
            :invalid="form.invalid('date')"
          >
            <DateTimePicker v-model="startDate" :disabled="Boolean(meeting)" @update:model-value="form.validate('date')" />
          </FormFieldWrapper>

          <FormFieldWrapper id="end_date" :label="$t('Renginio pabaiga')" :error="form.errors.end_date">
            <DateTimePicker v-model="endDate" :disabled="Boolean(meeting)" />
          </FormFieldWrapper>
        </div>
      </div>
    </FormSection>

    <!-- Section 3: Promotion -->
    <FormSection
      data-section="3"
      :title="$t('Viešinimas')"
      :description="$t('Nuorodos ir vaizdo turinys')"
    >
      <div class="space-y-4">
        <FormFieldWrapper
          id="cto_url"
          :label="$t('Renginio nuoroda')"
          :hint="$t('Nuoroda į pagrindinį renginio puslapį arba registracijos formą')"
        >
          <MultiLocaleInput v-model:input="form.cto_url" />
        </FormFieldWrapper>

        <div class="grid gap-4 lg:grid-cols-2">
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
                placeholder="https://www.facebook.com/events/..."
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
              <Input id="video_url" v-model="form.video_url" type="text" placeholder="dQw4w9WgXcQ" class="flex-1" />
            </div>
          </FormFieldWrapper>
        </div>
      </div>
    </FormSection>

    <!-- Section 4: Main Image -->
    <FormSection
      data-section="4"
      :title="$t('Pagrindinė nuotrauka')"
      :description="$t('Rodoma renginio kortelėje ir viršuje')"
    >
      <FormFieldWrapper id="main_image" :label="$t('Pagrindinė nuotrauka')" required :error="form.errors.main_image">
        <ImageUpload
          v-model:focal-point-value="form.main_image_focal_point"
          :max="1"
          :existing-url="existingMainImageUrl"
          cropper
          compress
          focal-point
          folder="calendar"
          @update:file="handleMainImageUpdate"
        />
      </FormFieldWrapper>
    </FormSection>

    <!-- Section 5: Gallery Images -->
    <FormSection
      data-section="5"
      :title="$t('Galerijos nuotraukos')"
      :description="`${$t('Papildomos nuotraukos, rodomos galerijoje')}. ${$t('Nuotraukos optimizuojamos automatiškai prieš įkėlimą.')}`"
    >
      <ImageUpload
        v-model:files="newGalleryImages"
        :max="20"
        :existing-urls="existingGalleryImages"
        cropper
        compress
        folder="calendar"
        @remove:existing="removeExistingImage"
      />
    </FormSection>

    <!-- Section 6: Description -->
    <FormSection
      data-section="6"
      :title="$t('Aprašymas')"
      :description="$t('Detali informacija apie renginį')"
    >
      <div class="space-y-4">
        <div class="flex items-center gap-2">
          <Label class="font-medium">{{ $t('Aprašymo kalba') }}</Label>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>

        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" html />
        <TiptapEditor v-else v-model="form.description.en" preset="full" html />
      </div>
    </FormSection>

    <!-- Advanced Settings Slot -->
    <template #advanced>
      <div class="flex w-full items-center gap-3 border border-border p-3">
        <Switch id="is_all_day" v-model="form.is_all_day" @update:model-value="isAllDayTouched = true" />
        <div class="flex-1 min-w-0 flex items-center gap-2">
          <Label for="is_all_day" class="font-medium">
            {{ $t('Visos dienos renginys') }}
          </Label>
          <InfoPopover>
            {{ $t('Vietoj laiko bus rodoma „Visą dieną“ ar dienų skaičius. Kelių dienų renginiams parenkama automatiškai — čia galite pakeisti.') }}
          </InfoPopover>
        </div>
      </div>

      <!-- Permalink -->
      <div v-if="!isCreate" class="space-y-4">
        <PermalinkField
          :permalink="form.permalink?.lt ?? ''"
          :base-url="`www.vusa.test/kalendorius/${eventYear}`"
          :disabled="false"
          :label="$t('Nuoroda (LT)')"
          :validating="form.validating"
          :valid="form.valid('permalink.lt')"
          :invalid="form.invalid('permalink.lt')"
          @update:permalink="form.permalink = { ...form.permalink, lt: $event }"
          @change="form.validate('permalink.lt')"
        />
        <PermalinkField
          :permalink="form.permalink?.en ?? ''"
          :base-url="`www.vusa.test/calendar/${eventYear}`"
          :disabled="false"
          :label="$t('Nuoroda (EN)')"
          :validating="form.validating"
          :valid="form.valid('permalink.en')"
          :invalid="form.invalid('permalink.en')"
          @update:permalink="form.permalink = { ...form.permalink, en: $event }"
          @change="form.validate('permalink.en')"
        />
      </div>

      <Alert v-if="!isCreate && (legacyDateUrlLt || legacyDateUrlEn)">
        <Info class="size-4" />
        <AlertTitle>{{ $t('Sena, data pagrįsta nuoroda') }}</AlertTitle>
        <AlertDescription>
          <p>{{ $t('Ši nuoroda vis dar veikia ir nukreipia į renginį, bet yra pasenusi ir niekur nerodoma.') }}</p>
          <ul class="mt-1 space-y-0.5">
            <li v-if="legacyDateUrlLt">
              <a :href="legacyDateUrlLt" target="_blank" rel="noopener noreferrer" class="break-all underline">{{ legacyDateUrlLt }}</a>
            </li>
            <li v-if="legacyDateUrlEn">
              <a :href="legacyDateUrlEn" target="_blank" rel="noopener noreferrer" class="break-all underline">{{ legacyDateUrlEn }}</a>
            </li>
          </ul>
        </AlertDescription>
      </Alert>
    </template>

    <!-- Danger Zone Slot -->
    <template v-if="!isCreate" #danger-zone>
      <div class="space-y-6">
        <PublicUrlHistoryCard
          :urls="props.calendar.public_urls ?? []"
          :destroy-route="(id) => route('calendar.publicUrls.destroy', [props.calendar.id, id])"
        />

        <div v-if="enableDelete" class="flex items-center justify-between gap-4">
          <div>
            <h4 class="text-sm font-semibold text-destructive">
              {{ $t('Ištrinti renginį') }}
            </h4>
            <p class="text-xs text-muted-foreground">
              {{ $t('Renginys bus perkeltas į šiukšlinę.') }}
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
          :title="$t('Ištrinti renginį?')"
          :description="$t('Renginys bus perkeltas į šiukšlinę.')"
          :confirm-label="$t('Ištrinti')"
          destructive
          @confirm="emit('delete')"
        />
      </div>
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, h, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, CalendarClock, ExternalLink, Globe, Info, Trash2 } from 'lucide-vue-next';

import InfoPopover from '../Buttons/InfoPopover.vue';
import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';

import FormFieldWrapper from './FormFieldWrapper.vue';
import FormStatusHeader from './FormStatusHeader.vue';
import PermalinkField from './PermalinkField.vue';
import PublicUrlHistoryCard from './PublicUrlHistoryCard.vue';
import TagMultiSelect from './TagMultiSelect.vue';

import ISimpleIconsFacebook from '~icons/simple-icons/facebook';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { isSameDay } from '@/Utils/IntlTime';
import { localizedRoute } from '@/Utils/LocalizedRoutes';
import { generateSlug } from '@/Utils/String';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { ImageUpload } from '@/Components/ui/upload';
import DateTimePicker from '@/Components/ui/date-picker/DateTimePicker.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import VisualOptionSelect from '@/Components/FormItems/VisualOptionSelect.vue';

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
const deleteConfirmOpen = ref(false);
const locale = ref('lt');

const calendarTitle = computed(() =>
  form.title?.[locale.value] || form.title?.lt || form.title?.en || '',
);

const fieldIds: Record<string, string> = {
  'title.lt': 'title',
  'title.en': 'title',
  'tenant_id': 'tenant',
  'date': 'date',
  'event_type_id': 'event_type',
  'facebook_url': 'facebook_url',
};

// Store existing main_image URL for display in MediaUpload
const existingMainImageUrl = ref<string | null>(props.calendar.main_image_url ?? null);

// Prepare form data - main_image will be File | null for submission
const formData = {
  ...props.calendar,
  main_image: null as File | null,
  tags: props.calendar.tags ?? [],
} as unknown as Record<string, unknown>;

const form = props.rememberKey
  ? useForm(props.rememberKey, formData).withPrecognition(props.submitMethod, props.submitUrl)
  : useForm(formData).withPrecognition(props.submitMethod, props.submitUrl);

if (isCreate.value && form.tenant_id == null) {
  form.tenant_id = props.assignableTenants.find(tenant => tenant.type === 'pagrindinis')?.id
    ?? props.assignableTenants[0]?.id
    ?? null;
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

// Handle main image update explicitly
function handleMainImageUpdate(file: File | null) {
  form.main_image = file;
}

// Section completion states
const mainInfoComplete = computed(() =>
  Boolean((form.title?.lt?.length || 0) >= 3 && form.tenant_id),
);

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
    label: 'Didelė kortelė',
    description: 'Nuotrauka fone',
    icon: CardHeroIcon,
  },
  {
    value: 'split',
    label: 'Nuotrauka šalia',
    description: 'Kortelė su nuotrauka',
    icon: SplitHeroIcon,
  },
  {
    value: 'minimal',
    label: 'Minimalus',
    description: 'Be nuotraukos',
    icon: MinimalHeroIcon,
  },
];

const heroStyle = computed({
  get: () => form.hero_style ?? 'card',
  set: (val: string) => {
    form.hero_style = val as CalendarEventForm['hero_style'];
  },
});

// The year the event's URL is nested under — same value the backend derives from `date`.
const eventYear = computed(() => (form.date ? new Date(form.date).getFullYear() : new Date().getFullYear()));

// Status header links
const statusLinks = computed(() => {
  const permalink = form.permalink?.[locale.value];

  if (!props.calendar.id || !permalink) return [];

  const url = localizedRoute('calendar.show', { year: eventYear.value, permalink }, locale.value);

  return [{ url, label: 'Public' }];
});

const legacyDateUrlLt = computed(() => props.calendar.legacy_date_urls?.lt ?? null);
const legacyDateUrlEn = computed(() => props.calendar.legacy_date_urls?.en ?? null);

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

// Handle tenant_id as string for Select component
const tenantIdString = computed({
  get: () => form.tenant_id ? String(form.tenant_id) : '',
  set: (val: string) => {
    form.tenant_id = val ? parseInt(val) : null;
  },
});

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
  if (props.calendar.id) {
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
