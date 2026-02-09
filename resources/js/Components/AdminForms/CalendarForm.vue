<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- Status Header -->
    <template #status-header>
      <FormStatusHeader :is-published="!form.is_draft" :server-is-published="!props.calendar.is_draft"
        :links="statusLinks" :is-create @update:is-published="form.is_draft = !$event" />
    </template>

    <!-- Section 1: Main Info -->
    <FormElement :section-number="1" :is-complete="mainInfoComplete" required>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #subtitle>
        {{ $t('Pagrindiniai renginio nustatymai') }}
      </template>
      <template #description>
        <p><strong>{{ $t('Kategorija') }}</strong> {{ $t('keičia spalvą renginių kalendoriuje.') }}</p>
        <p>
          <strong>{{ $t('Organizatorius') }}</strong>, {{ $t('jeigu neįrašytas, bus') }} <strong>{{ defaultOrganizer
          }}</strong>
        </p>
      </template>

      <div class="space-y-4">
        <!-- Title -->
        <FormFieldWrapper id="title" :label="$t('forms.fields.title')" required
          :hint="$t('Renginio pavadinimas abiem kalbom')" :error="form.errors['title.lt']" :validating="form.validating"
          :valid="form.valid('title.lt')" :invalid="form.invalid('title.lt')">
          <MultiLocaleInput v-model:input="form.title" @blur="form.validate('title.lt')" />
        </FormFieldWrapper>

        <!-- Organizer & Location -->
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="organizer" :label="$t('Organizatorius')" :hint="$t('Kas organizuoja renginį')">
            <MultiLocaleInput v-model:input="form.organizer" />
          </FormFieldWrapper>

          <FormFieldWrapper id="location" :label="$t('Renginio vieta')" :hint="$t('Fizinė arba virtuali vieta')">
            <MultiLocaleInput v-model:input="form.location" />
          </FormFieldWrapper>
        </div>

        <!-- Category & Tenant -->
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="category" :label="$t('Kategorija')" :hint="$t('Kategorija keičia spalvą kalendoriuje')">
            <Select v-model="categoryIdString">
              <SelectTrigger id="category">
                <SelectValue :placeholder="$t('Pasirinkti kategoriją...')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__none__">
                  -- {{ $t('Be kategorijos') }} --
                </SelectItem>
                <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                  {{ cat.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>

          <FormFieldWrapper id="tenant" :label="$t('Padalinys')" required :error="form.errors.tenant_id"
            :valid="form.valid('tenant_id')" :invalid="form.invalid('tenant_id')">
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
        <FormFieldWrapper id="audience" :label="$t('Viešinimo auditorija')"
          :hint="$t('Ar renginys skirtas tarptautiniams studentams')">
          <div class="flex gap-2">
            <Button type="button" :variant="form.is_international ? 'default' : 'outline'" class="flex-1 gap-2"
              @click="form.is_international = true">
              <IFluentGlobe20Regular class="h-4 w-4" />
              {{ $t('Visi studentai') }}
            </Button>
            <Button type="button" :variant="form.is_international ? 'outline' : 'default'" class="flex-1"
              @click="form.is_international = false">
              {{ $t('Tik LT') }}
            </Button>
          </div>
        </FormFieldWrapper>
      </div>
    </FormElement>

    <!-- Section 2: Date & Time -->
    <FormElement :section-number="2" :is-complete="!!form.date" required>
      <template #title>
        {{ $t('Renginio laikas') }}
      </template>
      <template #subtitle>
        {{ $t('Kada vyks renginys') }}
      </template>
      <template #description>
        <p>{{ $t('Jeigu nėra nurodytas pabaigos laikas, kalendoriuje renginys rodomas kaip 1 val. trukmės.') }}</p>
      </template>

      <div class="grid gap-4 lg:grid-cols-3">
        <FormFieldWrapper id="date" :label="$t('Renginio pradžia')" required :error="form.errors.date"
          :valid="form.valid('date')" :invalid="form.invalid('date')">
          <DateTimePicker v-model="startDate" @update:model-value="form.validate('date')" />
        </FormFieldWrapper>

        <FormFieldWrapper id="end_date" :label="$t('Renginio pabaiga')" :error="form.errors.end_date">
          <DateTimePicker v-model="endDate" />
        </FormFieldWrapper>

        <div class="flex items-end pb-2">
          <div class="flex w-full items-center gap-3 rounded-lg border p-3">
            <Switch id="is_all_day" v-model="form.is_all_day" />
            <div class="flex-1">
              <Label for="is_all_day" class="flex items-center gap-2 font-medium">
                {{ $t('Visos dienos renginys') }}
                <InfoPopover>
                  {{ $t('ICS kalendoriuje šis renginys bus žymimas kaip visos dienos renginys.') }}
                </InfoPopover>
              </Label>
            </div>
          </div>
        </div>
      </div>
    </FormElement>

    <!-- Section 3: Promotion -->
    <FormElement :section-number="3" :is-complete="!!form.cto_url?.lt || !!form.facebook_url">
      <template #title>
        {{ $t('Viešinimas') }}
      </template>
      <template #subtitle>
        {{ $t('Nuorodos ir vaizdo turinys') }}
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="cto_url" :label="$t('CTO nuoroda')"
          :hint="$t('Nuoroda į pagrindinį renginio puslapį arba registracijos formą')">
          <MultiLocaleInput v-model:input="form.cto_url" />
        </FormFieldWrapper>

        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="facebook_url" :label="$t('forms.fields.facebook_url')" :error="form.errors.facebook_url"
            :valid="form.valid('facebook_url')" :invalid="form.invalid('facebook_url')">
            <div class="flex items-center gap-2">
              <IMdiFacebook class="h-4 w-4 shrink-0 text-[#1877F2]" />
              <Input id="facebook_url" v-model="form.facebook_url" type="url"
                placeholder="https://www.facebook.com/events/..." @change="form.validate('facebook_url')" />
            </div>
          </FormFieldWrapper>

          <FormFieldWrapper id="video_url" :label="$t('Youtube video kodas')"
            :hint="$t('Tik video kodas, ne pilna nuoroda')">
            <div class="flex items-center gap-2">
              <span class="shrink-0 text-sm text-muted-foreground">youtube.com/embed/</span>
              <Input id="video_url" v-model="form.video_url" type="text" placeholder="dQw4w9WgXcQ" class="flex-1" />
            </div>
          </FormFieldWrapper>
        </div>
      </div>
    </FormElement>

    <!-- Section 4: Main Image -->
    <FormElement :section-number="4" :is-complete="hasMainImage" required>
      <template #title>
        {{ $t('Pagrindinė nuotrauka') }}
      </template>
      <template #subtitle>
        {{ $t('Rodoma renginio kortelėje ir viršuje') }}
      </template>

      <FormFieldWrapper id="main_image" :label="$t('Pagrindinė nuotrauka')" required :error="form.errors.main_image">
        <ImageUpload :max="1" :existing-url="existingMainImageUrl" cropper compress folder="calendar"
          @update:file="handleMainImageUpdate" />
      </FormFieldWrapper>
    </FormElement>

    <!-- Section 5: Gallery Images -->
    <FormElement :section-number="5" :is-complete="(form.images?.length ?? 0) > 0">
      <template #title>
        {{ $t('Galerijos nuotraukos') }}
      </template>
      <template #subtitle>
        {{ $t('Papildomos nuotraukos, rodomos galerijoje') }}
      </template>
      <template #description>
        <p>{{ $t('Nuotraukos optimizuojamos automatiškai prieš įkėlimą.') }}</p>
        <p class="text-amber-600 dark:text-amber-400">
          {{ $t('Naujos nuotraukos bus įkeltos išsaugojus formą.') }}
        </p>
      </template>

      <ImageUpload v-model:files="newGalleryImages" :max="20" :existing-urls="existingGalleryImages" cropper compress
        folder="calendar" @remove:existing="removeExistingImage" />
    </FormElement>

    <!-- Section 6: Description -->
    <FormElement :section-number="6" :is-complete="!!form.description?.lt">
      <template #title>
        {{ $t('Aprašymas') }}
      </template>
      <template #subtitle>
        {{ $t('Detali informacija apie renginį') }}
      </template>

      <div class="space-y-4">
        <div class="flex items-center gap-2">
          <Label class="font-medium">{{ $t('Aprašymo kalba') }}</Label>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>

        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

import InfoPopover from '../Buttons/InfoPopover.vue';
import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import FormStatusHeader from './FormStatusHeader.vue';
import AdminForm from './AdminForm.vue';

import { translitLithuanian } from '@/Utils/String';
import { getCalendarEvent2Route } from '@/Utils/Route';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ImageUpload } from '@/Components/ui/upload';
import DateTimePicker from '@/Components/ui/date-picker/DateTimePicker.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const props = defineProps<{
  calendar: CalendarEventForm;
  categories: App.Entities.Category[];
  assignableTenants: App.Entities.Tenant[];
  rememberKey?: string;
  submitUrl: string;
  submitMethod: 'post' | 'patch';
}>();

const isCreate = computed(() => !!props.rememberKey);
const locale = ref('lt');

// Store existing main_image URL for display in MediaUpload
const existingMainImageUrl = ref<string | null>(props.calendar.main_image_url ?? null);

// Prepare form data - main_image will be File | null for submission
const formData = {
  ...props.calendar,
  main_image: null as File | null, // Reset to null, will be set if user uploads new image
} as any;

const form = props.rememberKey
  ? useForm(props.rememberKey, formData).withPrecognition(props.submitMethod, props.submitUrl)
  : useForm(formData).withPrecognition(props.submitMethod, props.submitUrl);

// Set validation timeout
form.setValidationTimeout(500);

// Handle main image update explicitly
function handleMainImageUpdate(file: File | null) {
  form.main_image = file;
}

// Track if main image section is complete (either existing or new file selected)
const hasMainImage = computed(() => !!form.main_image || !!existingMainImageUrl.value);

// Section completion states
const mainInfoComplete = computed(() =>
  (form.title?.lt?.length || 0) >= 3 && form.tenant_id,
);

// Status header links
const statusLinks = computed(() => {
  // Need id, date, and title to construct a valid public URL
  if (!props.calendar.id || !props.calendar.date || !form.title?.lt) return [];

  const locale = usePage().props.app?.locale ?? 'lt';
  const url = getCalendarEvent2Route(
    { date: props.calendar.date, title: translitLithuanian(form.title.lt) },
    locale,
  );

  return [{ url, label: 'Public' }];
});

const defaultOrganizer = computed(() => {
  return (
    props.calendar.tenant?.shortname ?? usePage().props.auth?.user?.tenants?.[0]?.shortname
  );
});

// Handle category_id as string for Select component
const categoryIdString = computed({
  get: () => form.category_id ? String(form.category_id) : '__none__',
  set: (val: string) => {
    form.category_id = val && val !== '__none__' ? parseInt(val) : null;
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
  (props.calendar.images ?? []).map((img: any) => ({
    id: img.id,
    url: img.url || img.original_url,
    name: img.name || 'image.jpg',
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
