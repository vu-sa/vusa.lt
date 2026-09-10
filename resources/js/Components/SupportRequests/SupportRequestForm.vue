<template>
  <AdminForm :model="form" :is-create-form="!isEditing">
    <FormElement :section-number="1" :is-complete="Boolean(form.title && form.description)">
      <template #title>
        {{ $t('Pranešimo informacija') }}
      </template>
      <template #description>
        {{ $t('Aprašykite problemą ar idėją taip, kad ją būtų galima suprasti ir įvertinti.') }}
      </template>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormFieldWrapper id="support_request_type_id" :label="$t('Tipas')" required :error="form.errors.support_request_type_id">
          <Select v-model="form.support_request_type_id">
            <SelectTrigger id="support_request_type_id">
              <SelectValue :placeholder="$t('Pasirinkti tipą')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="t in typeOptions" :key="t.value" :value="t.value">
                {{ t.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper id="support_request_area_id" :label="$t('Sritis')" required :error="form.errors.support_request_area_id">
          <Select v-model="form.support_request_area_id">
            <SelectTrigger id="support_request_area_id">
              <SelectValue :placeholder="$t('Pasirinkti sritį')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="a in areaOptions" :key="a.value" :value="a.value">
                {{ a.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper id="title" :label="$t('Pavadinimas')" required :error="form.errors.title">
        <Input
          id="title"
          v-model="form.title"
          :placeholder="$t('Trumpai nusakyk problemą ar idėją...')"
        />
      </FormFieldWrapper>

      <FormFieldWrapper id="description" :label="$t('Aprašymas')" required :error="form.errors.description">
        <Textarea
          id="description"
          v-model="form.description"
          rows="5"
          :placeholder="$t('Išsamiai aprašyk, kas nutiko, kaip atkartoti problemą arba ką siūlai patobulinti...')"
        />
      </FormFieldWrapper>

      <div>
        <FormFieldWrapper id="context_url" :label="$t('Susijęs puslapis (URL)')" :error="form.errors.context_url">
          <Input
            id="context_url"
            v-model="form.context_url"
            type="url"
            placeholder="https://vusa.lt/..."
          />
        </FormFieldWrapper>
      </div>
    </FormElement>

    <FormElement :section-number="2" :is-complete="form.visibility !== 'roles' || form.roles.length > 0">
      <template #title>
        {{ $t('Matomumas') }}
      </template>
      <template #description>
        {{ $t('Nurodykite, kas gali matyti ir aptarti šį pranešimą.') }}
      </template>
      <div class="space-y-3 rounded-lg border bg-muted/20 p-4">
        <FormFieldWrapper id="visibility" :label="$t('Matomumas')" required :error="form.errors.visibility">
          <RadioGroup v-model="form.visibility" class="grid gap-2.5 sm:grid-cols-3">
            <label
              v-for="opt in visibilityOptions"
              :key="opt.value"
              class="flex items-start gap-3 rounded-lg border p-3.5 transition-all"
              :class="[
                opt.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                form.visibility === opt.value
                  ? 'border-primary bg-background shadow-xs ring-1 ring-primary'
                  : 'border-border bg-card hover:bg-muted/40 hover:border-muted-foreground/40',
              ]"
            >
              <RadioGroupItem :id="`visibility-${opt.value}`" :value="opt.value" :disabled="opt.disabled" class="mt-0.5" />
              <div class="flex-1 select-none">
                <div class="flex items-center gap-1.5">
                  <component :is="opt.icon" class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm font-medium text-foreground">{{ opt.label }}</span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground leading-relaxed">
                  {{ opt.description }}
                </p>
              </div>
            </label>
          </RadioGroup>

          <!-- Informational note why visibility is selected (for colleagues to see and expect a possible regression) -->
          <div class="mt-3 flex items-start gap-2 rounded-md bg-muted/50 p-2.5 text-xs text-muted-foreground border">
            <Info class="h-4 w-4 shrink-0 mt-0.5 text-primary" />
            <span class="leading-relaxed">
              <!-- eslint-disable-next-line max-len -->
              {{ $t('Viešesnis matomumas (rolėms arba visiems nariams) leidžia kitiems matyti žinomas problemas, išvengti pasikartojančių pranešimų ir numatyti galimus sistemos sutrikimus (regresijas).') }}
            </span>
          </div>
        </FormFieldWrapper>

        <!-- Roles selection and dynamic user list preview -->
        <div v-if="form.visibility === 'roles'" class="space-y-4 pt-2 border-t">
          <div>
            <Label class="text-sm font-medium mb-2 block">{{ $t('Pasirinkti roles:') }}</Label>
            <div v-if="roles.length > 0" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
              <label
                v-for="role in roles"
                :key="role.id"
                class="flex items-center gap-2 rounded-md border p-2.5 text-sm cursor-pointer hover:bg-muted/50 transition-colors"
              >
                <Checkbox
                  :model-value="form.roles.includes(role.id)"
                  @update:model-value="(checked: boolean) => toggleRole(role.id, checked)"
                />
                <span class="truncate font-medium">{{ role.name }}</span>
              </label>
            </div>
            <p v-else class="text-xs text-muted-foreground italic">
              {{ $t('Neturite priskirtų rolių, kurioms galėtumėte suteikti prieigą.') }}
            </p>
            <p v-if="form.errors.roles" class="text-xs text-red-600 mt-1">
              {{ form.errors.roles }}
            </p>
          </div>

          <!-- Dynamic User List Preview -->
          <div class="rounded-lg border bg-background p-3.5 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                {{ $t('Nariai, kurie galės matyti ir komentuoti šį pranešimą') }}
              </span>
              <span class="text-xs font-medium text-muted-foreground">
                {{ authorizedUsers.length }} {{ $t('nariai') }}
              </span>
            </div>

            <div v-if="authorizedUsers.length > 0" class="flex flex-wrap items-center gap-2 pt-1">
              <UsersAvatarGroup :users="authorizedUsers" :max="8" :size="28" />
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="u in authorizedUsers.slice(0, 5)"
                  :key="u.id"
                  class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground"
                >
                  {{ u.name }}
                </span>
                <span v-if="authorizedUsers.length > 5" class="text-xs text-muted-foreground self-center">
                  +{{ authorizedUsers.length - 5 }} {{ $t('kitų') }}
                </span>
              </div>
            </div>
            <p v-else class="text-xs text-muted-foreground italic">
              {{ $t('Pasirink bent vieną rolę, kad pamatytum narius, turėsiančius prieigą.') }}
            </p>
          </div>
        </div>
      </div>
    </FormElement>

    <FormElement :section-number="3">
      <template #title>
        {{ $t('Ekrano nuotraukos ir failai') }}
      </template>
      <template #description>
        {{ $t('Pridėkite vaizdų, kurie padėtų greičiau suprasti pranešimą.') }}
      </template>
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <Label class="text-sm font-medium">{{ $t('Ekrano nuotraukos ir failai') }}</Label>
          <span class="text-xs text-muted-foreground">
            {{ $t('Iki 5 failų (JPG, PNG, WebP)') }}
          </span>
        </div>

        <!-- Upload Dropzone Area -->
        <div
          :class="[
            'relative flex flex-col items-center justify-center rounded-lg',
            'border-2 border-dashed border-zinc-200 dark:border-zinc-800',
            'bg-zinc-50/50 dark:bg-zinc-900/50 p-6 text-center select-none cursor-pointer transition-colors',
            'hover:border-primary/50 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/50',
            { 'border-primary bg-primary/5 dark:bg-primary/10': isDragging },
          ]"
          role="button"
          tabindex="0"
          @click="triggerFileInput"
          @keydown.enter="triggerFileInput"
          @keydown.space.prevent="triggerFileInput"
          @dragenter.prevent="isDragging = true"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
        >
          <input
            ref="fileInputRef"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            multiple
            class="hidden"
            @change="handleFileChange"
          >
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-muted-foreground mb-2.5 transition-colors">
            <Upload class="h-5 w-5" />
          </div>
          <div class="text-sm font-medium text-foreground">
            {{ isDragging ? $t('Paleisk failus čia') : $t('Spustelėk arba įtempk ekrano nuotraukas') }}
          </div>
          <p class="text-xs text-muted-foreground mt-1">
            {{ $t('PNG, JPG arba WebP iki 10 MB') }}
          </p>
        </div>

        <!-- Existing media if editing -->
        <div v-if="existingMedia.length > 0" class="space-y-2 pt-1">
          <span class="text-xs font-medium text-muted-foreground">{{ $t('Prisegti failai:') }}</span>
          <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div
              v-for="m in existingMedia"
              :key="m.id"
              class="relative group overflow-hidden rounded-lg border bg-muted/30 p-1"
            >
              <img :src="m.thumb_url || m.original_url" :alt="m.name" class="h-24 w-full object-cover rounded">
              <Button
                type="button"
                variant="destructive"
                size="icon"
                class="absolute top-2 right-2 h-6 w-6 opacity-80 group-hover:opacity-100 shadow-xs"
                @click="removeExistingMedia(m.id)"
              >
                <X class="h-3.5 w-3.5" />
              </Button>
              <div class="p-1 text-[11px] text-muted-foreground truncate">
                {{ m.file_name }}
              </div>
            </div>
          </div>
        </div>

        <!-- New file uploads preview -->
        <div v-if="previewUrls.length > 0" class="space-y-2 pt-1">
          <span class="text-xs font-medium text-muted-foreground">{{ $t('Naujai pridedami failai:') }}</span>
          <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div
              v-for="(url, index) in previewUrls"
              :key="index"
              class="relative group overflow-hidden rounded-lg border bg-muted/30 p-1"
            >
              <img :src="url" alt="Preview" class="h-24 w-full object-cover rounded">
              <Button
                type="button"
                variant="destructive"
                size="icon"
                class="absolute top-2 right-2 h-6 w-6 opacity-80 group-hover:opacity-100 shadow-xs"
                @click="removeNewFile(index)"
              >
                <X class="h-3.5 w-3.5" />
              </Button>
              <div class="p-1 text-[11px] text-muted-foreground truncate">
                {{ form.images[index]?.name }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </FormElement>

    <template #buttons>
      <Button
        v-if="showCancel"
        type="button"
        variant="outline"
        @click="emit('cancel')"
      >
        {{ $t('Atšaukti') }}
      </Button>

      <Button
        v-else-if="backUrl"
        type="button"
        variant="outline"
        as-child
      >
        <Link :href="backUrl">
          {{ $t('Atšaukti') }}
        </Link>
      </Button>

      <Button type="button" :disabled="form.processing" @click="submit">
        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
        {{ isEditing ? $t('Išsaugoti pakeitimus') : $t('Siųsti pranešimą') }}
      </Button>
    </template>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, ref, onBeforeUnmount } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import { Globe, Info, Loader2, Lock, Upload, Users, X } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type {
  SupportRequestItem,
  SupportRequestMediaFile,
  SupportRequestRoleOption,
  SupportRequestTaxonomyItem,
  SupportRequestUser,
} from '@/Types/supportRequests';

const props = withDefaults(defineProps<{
  types: SupportRequestTaxonomyItem[];
  areas: SupportRequestTaxonomyItem[];
  roles: SupportRequestRoleOption[];
  supportRequest?: SupportRequestItem | null;
  backUrl?: string;
  showCancel?: boolean;
}>(), {
  supportRequest: null,
  backUrl: undefined,
});

const emit = defineEmits<{
  (e: 'cancel'): void;
  (e: 'success'): void;
}>();

const page = usePage();
const currentLocale = computed(() => (page.props as { app?: { locale?: string } }).app?.locale || 'lt');

const resolveName = (name: string | Record<string, string> | undefined): string => {
  return getTranslatedValue(name, currentLocale.value, '—');
};

// Mirrors InstitutionForm options pattern so taxonomy items are translated by locale
const typeOptions = computed(() =>
  props.types.map(t => ({
    label: resolveName(t.name),
    value: String(t.id),
  })),
);

const areaOptions = computed(() =>
  props.areas.map(a => ({
    label: resolveName(a.name),
    value: String(a.id),
  })),
);

const visibilityOptions = computed(() => [
  {
    value: 'private',
    label: $t('Privatu'),
    description: $t('Matoma tik tau ir sistemos administratoriams.'),
    icon: Lock,
    disabled: false,
  },
  {
    value: 'roles',
    label: $t('Pasirinktoms rolėms'),
    description: props.roles.length === 0
      ? $t('Neturite priskirtų rolių, kurioms galėtumėte suteikti prieigą.')
      : $t('Matoma nurodytų rolių nariams ir administratoriams.'),
    icon: Users,
    disabled: props.roles.length === 0,
  },
  {
    value: 'public',
    label: $t('Visiems prisijungusiems'),
    description: $t('Matoma visiems prisijungusiems organizacijos nariams.'),
    icon: Globe,
    disabled: false,
  },
]);

const isEditing = computed(() => !!props.supportRequest?.id);

const visibilityValue = computed(() => {
  const vis = props.supportRequest?.visibility;
  if (typeof vis === 'object' && vis !== null && 'value' in vis) {
    return vis.value;
  }
  return typeof vis === 'string' ? vis : 'private';
});

const form = useForm({
  support_request_type_id: props.supportRequest?.support_request_type_id
    ? String(props.supportRequest.support_request_type_id)
    : (props.types[0]?.id ? String(props.types[0].id) : ''),
  support_request_area_id: props.supportRequest?.support_request_area_id
    ? String(props.supportRequest.support_request_area_id)
    : (props.areas[0]?.id ? String(props.areas[0].id) : ''),
  visibility: visibilityValue.value,
  roles: props.supportRequest?.roles?.map(r => r.id) ?? ([] as string[]),
  title: props.supportRequest?.title ?? '',
  description: props.supportRequest?.description ?? '',
  context_url: props.supportRequest?.context_url ?? '',
  images: [] as File[],
  deleted_media_ids: [] as number[],
});

// Dynamic role users calculation
const authorizedUsers = computed(() => {
  if (form.visibility !== 'roles' || form.roles.length === 0) {
    return [];
  }

  const userMap = new Map<string, SupportRequestUser>();
  for (const roleId of form.roles) {
    const role = props.roles.find(r => r.id === roleId);
    if (role && Array.isArray(role.users)) {
      for (const u of role.users) {
        userMap.set(u.id, u);
      }
    }
  }

  return Array.from(userMap.values());
});

function toggleRole(roleId: string, checked: boolean) {
  if (checked) {
    if (!form.roles.includes(roleId)) {
      form.roles.push(roleId);
    }
  }
  else {
    form.roles = form.roles.filter((id: string) => id !== roleId);
  }
}

// Media handling
const existingMedia = ref<SupportRequestMediaFile[]>(props.supportRequest?.media ?? []);

function removeExistingMedia(id: number) {
  existingMedia.value = existingMedia.value.filter(m => m.id !== id);
  if (!form.deleted_media_ids.includes(id)) {
    form.deleted_media_ids.push(id);
  }
}

// Drag & drop and file input handling
const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const previewUrls = ref<string[]>([]);

function triggerFileInput() {
  fileInputRef.value?.click();
}

function handleDrop(event: DragEvent) {
  isDragging.value = false;
  const droppedFiles = Array.from(event.dataTransfer?.files ?? []).filter(file =>
    ['image/jpeg', 'image/png', 'image/webp'].includes(file.type),
  );
  if (droppedFiles.length > 0) {
    appendFiles(droppedFiles);
  }
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  const files = Array.from(target.files ?? []);
  appendFiles(files);
  target.value = '';
}

function appendFiles(newFiles: File[]) {
  const combined = [...form.images, ...newFiles].slice(0, 5);
  previewUrls.value.forEach(url => URL.revokeObjectURL(url));
  form.images = combined;
  previewUrls.value = combined.map(f => URL.createObjectURL(f));
}

function removeNewFile(index: number) {
  URL.revokeObjectURL(previewUrls.value[index]);
  previewUrls.value.splice(index, 1);
  form.images.splice(index, 1);
}

onBeforeUnmount(() => {
  previewUrls.value.forEach(url => URL.revokeObjectURL(url));
});

function submit() {
  form.defaults();

  if (isEditing.value && props.supportRequest?.id) {
    router.post(route('supportRequests.update', props.supportRequest.id), {
      ...form.data(),
      _method: 'PUT',
    }, {
      forceFormData: true,
      onSuccess: () => {
        form.defaults();
        emit('success');
      },
    });
  }
  else {
    form.post(route('mySupportRequests.store'), {
      forceFormData: true,
      onSuccess: () => {
        form.defaults();
        emit('success');
      },
    });
  }
}
</script>
