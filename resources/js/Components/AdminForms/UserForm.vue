<template>
  <FormPage
    :title="isCreating ? $t('Naujas narys (-ė)') : userTitle"
    :bar-title="isCreating ? undefined : userTitle"
    :head-title="isCreating ? $t('Naujas narys (-ė)') : userTitle"
    :lead="isCreating ? $t('Sukurk profilį ir iškart priskirk bent vieną pareigybę — kitas galėsi pridėti asmens puslapyje.') : undefined"
    :entity-type="ModelEnum.USER"
    :back-href="isCreating ? route('users.index') : route('users.show', user.id)"
    :back-label="isCreating ? $t('Nariai') : $t('Į profilį')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreating ? 'create' : 'edit'"
    :available-locales="[]"
    :activity-subject="user.id ? { type: 'user', id: user.id } : undefined"
    :created-at="user?.created_at"
    :updated-at="user?.updated_at"
    @submit="emit('submit:form', form)"
  >
    <FormSection
      :title="$t('Kas tai?')"
      :description="$t('Dažniausiai tai studentas, VU SA narys. Naudotojai iš vusa.lt/mano netrinami, o esamų vardų pavardžių keisti negalima.')"
    >
      <FormFieldWrapper
        id="user-name"
        :label="$t('forms.fields.name_and_surname')"
        required
        :error="form.errors.name"
      >
        <Input
          id="user-name"
          v-model="form.name"
          :disabled="(user.name !== '' && !isSuperAdmin) || !canUpdateIdentity"
          type="text"
          placeholder="Vardas Pavardė"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="user-email"
        :label="$t('El. paštas')"
        required
        :error="form.errors.email"
      >
        <Input
          id="user-email"
          v-model="form.email"
          :disabled="!canUpdateIdentity"
          placeholder="vardas.pavarde@stud.vu.lt"
        />
        <p v-if="!canUpdateIdentity" class="flex items-start gap-1.5 text-xs text-muted-foreground">
          <Lock class="mt-0.5 size-3 shrink-0" aria-hidden="true" />
          {{ $t('users.identity_locked_hint') }}
        </p>
        <p v-else-if="isUserEmailMaybeDutyEmail" class="text-xs text-status-attention" data-testid="duty-email-hint">
          {{ $t('Šis el. paštas baigiasi @vusa.lt, kuris dažniausiai naudojamas pareigybėms. Geriau naudoti studentinį ar kitą VU paštą.') }}
        </p>
        <DuplicateUserWarning v-if="isCreating" :matches="duplicateMatches" class="mt-2" />
        <div v-if="currentDutiesWithVusaEmail.length > 0" class="space-y-1 pt-1 text-xs text-muted-foreground">
          <p class="font-medium text-foreground">
            {{ $t('Šie pareigybiniai el. paštai taip pat leidžia prisijungti prie sistemos:') }}
          </p>
          <ul class="space-y-0.5">
            <li v-for="duty in currentDutiesWithVusaEmail" :key="duty.id" class="flex items-center gap-1.5">
              <span class="truncate font-medium text-foreground">{{ duty.name }}</span>
              <span aria-hidden="true">→</span>
              <code class="text-[12px]">{{ duty.email }}</code>
            </li>
          </ul>
        </div>
      </FormFieldWrapper>
    </FormSection>

    <FormSection
      :title="$t('Kaip su juo susisiekti?')"
      :description="$t('Nuotrauka ir kontaktai rodomi viešame vusa.lt puslapyje, kai asmuo turi pareigybę.')"
      :badge="$t('Matoma vusa.lt')"
      public-marker
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormFieldWrapper
          id="user-phone"
          :label="`${$t('forms.fields.phone')} (${$t('neprivaloma')})`"
          :error="form.errors.phone"
        >
          <Input id="user-phone" v-model="form.phone" placeholder="+370 612 34 567" />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="user-facebook"
          :label="`${$t('forms.fields.facebook_url')} (${$t('neprivaloma')})`"
          :error="form.errors.facebook_url"
        >
          <Input id="user-facebook" v-model="form.facebook_url" placeholder="https://www.facebook.com/..." />
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper
        id="user-picture"
        :label="`${$t('forms.fields.picture')} (${$t('neprivaloma')})`"
      >
        <ImageUpload
          v-model:url="form.profile_photo_path"
          v-model:focal-point-value="form.profile_photo_focal_point"
          mode="immediate"
          folder="contacts"
          cropper
          focal-point
          preview-aspect="4/3"
          :existing-url="user?.profile_photo_path"
        />
      </FormFieldWrapper>
    </FormSection>

    <template #aside>
      <FormPanel
        v-if="isCreating"
        :title="$t('Pareigybės ir rolės')"
        :icon="Briefcase"
        title-class="text-brand"
      >
        <FormFieldWrapper
          id="user-duties"
          :label="$t('Pareigybės')"
          required
          :hint="$t('Profilis be pareigybės niekam nematomas. Pareigybių sąrašą ir datas vėliau tvarkysi asmens puslapyje.')"
          :error="form.errors.current_duties"
        >
          <MultiSelect
            id="user-duties"
            v-model="selectedDuties"
            :options="dutyOptions"
            label-field="label"
            value-field="value"
            :placeholder="$t('Pasirinkite pareigybes…')"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          v-if="isSuperAdmin"
          id="user-roles"
          :label="`${$t('forms.fields.admin_role')} (superadmin)`"
        >
          <MultiSelect
            id="user-roles"
            v-model="selectedRoles"
            :options="rolesOptions"
            label-field="label"
            value-field="value"
            :placeholder="$t('Be rolės...')"
          />
        </FormFieldWrapper>
      </FormPanel>

      <FormPanel
        :title="$t('Kreipinys ir įvardžiai')"
        :icon="UserCheck"
        title-class="text-brand"
      >
        <FormFieldWrapper
          id="user-pronouns"
          :label="`${$t('forms.fields.pronouns')} (${$t('neprivaloma')})`"
          :hint="$t('Nurodžius įvardį, pareigybės pavadinimo galūnė keičiasi automatiškai.')"
          :error="form.errors.pronouns || form.errors['pronouns.lt'] || form.errors['pronouns.en']"
        >
          <MultiLocaleInput
            id="user-pronouns"
            v-model:input="form.pronouns"
            :placeholder="{ lt: 'Jie/jų', en: 'They/them' }"
          />
        </FormFieldWrapper>

        <div class="flex items-start gap-2.5 pt-1">
          <Checkbox
            id="user-show-pronouns"
            class="mt-0.5"
            :model-value="Boolean(form.show_pronouns)"
            :disabled="!hasPronouns"
            @update:model-value="form.show_pronouns = $event === true"
          />
          <div class="space-y-0.5">
            <Label for="user-show-pronouns" class="cursor-pointer text-sm font-normal">
              {{ $t('forms.fields.show_pronouns') }}
            </Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('Matoma vusa.lt') }} · {{ $t('Įvardžiai rodomi prie asmens vardo ir pavardės.') }}
            </p>
          </div>
        </div>
      </FormPanel>

      <FormPanel
        v-if="!isCreating && user.last_action"
        :title="$t('Paskutinis veiksmas')"
        :icon="Clock"
        title-class="text-brand"
      >
        <p class="text-xs text-muted-foreground">
          {{ $t('Paskutinį kartą prisijungė') }} {{ formatStaticTime(user.last_action) }}
        </p>
      </FormPanel>
    </template>

    <template v-if="!isCreating && user.last_action" #footer-extra>
      <span class="text-xs text-muted-foreground">
        {{ $t('Paskutinį kartą prisijungė') }} {{ formatStaticTime(user.last_action) }}
      </span>
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Briefcase, Clock, Lock, UserCheck } from 'lucide-vue-next';

import DuplicateUserWarning from './DuplicateUserWarning.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';

import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { FormPanel } from '@/Components/Patterns';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import { ImageUpload } from '@/Components/ui/upload';
import { useDuplicateUserCheck } from '@/Composables/useDuplicateUserCheck';
import { ModelEnum } from '@/Types/enums';
import { formatStaticTime } from '@/Utils/IntlTime';

const props = withDefaults(defineProps<{
  user: App.Entities.User;
  /** Create only: a super admin may give the new person roles. */
  roles?: App.Entities.Role[];
  /** Create only: the duties a new person can start with. */
  tenantsWithDuties?: App.Entities.Tenant[];
  permissableTenants?: App.Entities.Tenant[];
  /** Create mode when set: keeps the draft across a failed submit. */
  rememberKey?: 'CreateUser';
  /**
   * Whether the acting admin may change this person's login email. Existing names
   * are independently restricted to super administrators.
   */
  canUpdateIdentity?: boolean;
}>(), {
  roles: () => [],
  tenantsWithDuties: () => [],
  permissableTenants: () => [],
  canUpdateIdentity: true,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
}>();

const isCreating = computed(() => !props.user.id);
const isSuperAdmin = computed(() => usePage().props.auth?.user?.isSuperAdmin ?? false);

const userTitle = computed(() => props.user.name);

// Duties and roles of an existing person are edited on the record, so only the fields the form
// owns are sent; a create adds the first duties (and, for a super admin, roles).
const initial = () => ({
  name: props.user.name ?? '',
  email: props.user.email ?? '',
  phone: (props.user.phone ?? null) as string | null,
  facebook_url: (props.user.facebook_url ?? null) as string | null,
  profile_photo_path: (props.user.profile_photo_path ?? null) as string | null,
  profile_photo_focal_point: ((props.user as unknown as Record<string, unknown>).profile_photo_focal_point ?? null) as string | null,
  pronouns: (Array.isArray(props.user.pronouns) || !props.user.pronouns
    ? { lt: '', en: '' }
    : { lt: '', en: '', ...(props.user.pronouns as Record<string, string>) }) as Record<'lt' | 'en', string>,
  show_pronouns: Boolean(props.user.show_pronouns),
  ...(isCreating.value ? { current_duties: [] as string[], roles: [] as number[] } : {}),
});

const form = props.rememberKey ? useForm(props.rememberKey, initial()) : useForm(initial());

// Error keys that are not the id of the field they belong to.
const fieldIds = {
  name: 'user-name',
  email: 'user-email',
  phone: 'user-phone',
  facebook_url: 'user-facebook',
  current_duties: 'user-duties',
  roles: 'user-roles',
};

const hasPronouns = computed(() => Boolean(form.pronouns?.lt || form.pronouns?.en));

// --- Create only: the first duties and, for a super admin, roles -------------------------------

const rolesOptions = computed(() => props.roles.map(role => ({ label: role.name, value: role.id })));

const selectedRoles = computed({
  get: () => rolesOptions.value.filter(option => (form as unknown as { roles: number[] }).roles?.includes(option.value)),
  set: (items: { label: string; value: number }[]) => {
    (form as unknown as { roles: number[] }).roles = items.map(item => item.value);
  },
});

/** Only the tenants the actor may create people in, flattened so a duty is named with its institution. */
const dutyOptions = computed(() => props.tenantsWithDuties
  .filter(tenant => props.permissableTenants.some(permissable => permissable.id === tenant.id))
  .flatMap(tenant => (tenant.institutions ?? []).flatMap(institution =>
    (institution.duties ?? []).map(duty => ({
      label: `${duty.name} · ${institution.name} (${tenant.shortname})`,
      value: String(duty.id),
    })))));

const selectedDuties = computed({
  get: () => dutyOptions.value.filter(option => (form as unknown as { current_duties: string[] }).current_duties?.includes(option.value)),
  set: (items: { label: string; value: string }[]) => {
    (form as unknown as { current_duties: string[] }).current_duties = items.map(item => item.value);
  },
});

// Only the create form can produce a duplicate; on edit the record already exists.
const { matches: duplicateMatches } = useDuplicateUserCheck(
  () => (isCreating.value ? String(form.name ?? '') : ''),
  () => (isCreating.value ? String(form.email ?? '') : ''),
);

// A @vusa.lt address usually belongs to a duty, not a person.
const isUserEmailMaybeDutyEmail = computed(() => (props.user.email ?? '').toLowerCase().endsWith('@vusa.lt'));

const currentDutiesWithVusaEmail = computed(() =>
  props.user.current_duties?.filter(duty => duty.email?.toLowerCase().endsWith('@vusa.lt')) ?? []);
</script>
