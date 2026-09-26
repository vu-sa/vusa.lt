<template>
  <div class="space-y-6">
    <!-- Selected institution header -->
    <div class="flex items-center gap-3 p-3.5 sm:p-4 border border-border bg-muted/40">
      <div class="flex size-9 items-center justify-center border border-border bg-background shrink-0">
        <InstitutionIcon class="size-4 text-foreground" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
          {{ $t('Pasirinkta institucija') }}
        </p>
        <p class="font-semibold text-sm text-foreground truncate">
          {{ wizard.state.institution?.name }}
        </p>
      </div>
      <Button variant="outline" size="sm" @click="wizard.previousStep()">
        <Edit3 class="size-3.5 mr-1.5" />
        {{ $t('Keisti') }}
      </Button>
    </div>

    <!-- Create Duty Form -->
    <SectionCard
      v-if="showCreateForm"
      :title="$t('Nauja pareigybė')"
      :icon="Plus"
      class="border-border"
    >
      <div class="space-y-4">
        <p class="text-xs text-muted-foreground">
          {{ $t('Sukurti naują pareigybę institucijai') }}: <span class="font-medium text-foreground">{{ wizard.state.institution?.name }}</span>
        </p>

        <!-- Duty Name -->
        <div class="space-y-1.5">
          <Label class="text-xs font-semibold">{{ $t('Pavadinimas') }} *</Label>
          <Input
            v-model="http.name.lt"
            :placeholder="$t('Pareigybės pavadinimas lietuvių kalba')"
            :class="{ 'border-destructive': createErrors['name.lt'] }"
          />
          <p v-if="createErrors['name.lt']" class="text-xs text-destructive">
            {{ createErrors['name.lt'][0] }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ $t('forms.helpers.duty_name_inflected_hint') }}
          </p>
          <DuplicateDutyWarning :matches="duplicateMatches" />
        </div>

        <!-- Places to Occupy -->
        <div class="space-y-1.5">
          <Label class="text-xs font-semibold">{{ $t('Kiek vietų') }}</Label>
          <Input
            v-model.number="http.places_to_occupy"
            type="number"
            min="1"
            :placeholder="$t('1')"
          />
          <p class="text-xs text-muted-foreground">
            {{ $t('Kiek žmonių gali užimti šią pareigybę') }}
          </p>
        </div>

        <!-- Contacts Grouping -->
        <div class="space-y-1.5">
          <Label class="text-xs font-semibold">{{ $t('Kontaktų grupavimas') }} *</Label>
          <Select v-model="http.contacts_grouping">
            <SelectTrigger :class="{ 'border-destructive': createErrors['contacts_grouping'] }">
              <SelectValue :placeholder="$t('Pasirinkite grupavimą')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">
                {{ $t('Jokio') }}
              </SelectItem>
              <SelectItem value="study_program">
                {{ $t('Pagal studijų programą') }}
              </SelectItem>
              <SelectItem value="tenant">
                {{ $t('Pagal padalinį') }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="createErrors['contacts_grouping']" class="text-xs text-destructive">
            {{ createErrors['contacts_grouping'][0] }}
          </p>
        </div>

        <!-- Types (if available) -->
        <div class="space-y-2">
          <Label class="text-xs font-semibold">{{ $t('Tipai') }}</Label>
          <div v-if="isDutyTypesLoading" class="flex items-center gap-2 text-xs text-muted-foreground">
            <Loader2 class="size-3.5 animate-spin" />
            {{ $t('Kraunami tipai...') }}
          </div>
          <div v-else-if="dutyTypes.length > 0" class="flex flex-wrap gap-2">
            <button
              v-for="type in dutyTypes"
              :key="type.id"
              type="button"
              class="inline-flex items-center px-3 py-1.5 text-xs font-semibold border transition-colors"
              :class="http.types.includes(String(type.id))
                ? 'border-brand-fill bg-brand-fill text-brand-foreground'
                : 'border-border bg-background hover:bg-accent text-foreground'"
              @click="toggleType(String(type.id))"
            >
              {{ type.title }}
            </button>
          </div>
          <p v-else class="text-xs text-muted-foreground">
            {{ $t('Nėra galimų tipų') }}
          </p>
        </div>

        <!-- Extra fields (collapsible) -->
        <Collapsible v-model:open="showExtraFields" class="border border-border">
          <CollapsibleTrigger as-child>
            <Button variant="ghost" class="w-full justify-between h-10 px-3 font-medium">
              <span class="text-xs">{{ $t('Papildomi laukai') }}</span>
              <ChevronDown class="size-4 transition-transform" :class="{ 'rotate-180': showExtraFields }" />
            </Button>
          </CollapsibleTrigger>
          <CollapsibleContent class="p-3 pt-0 space-y-4 border-t border-border mt-1">
            <!-- English Name -->
            <div class="space-y-1.5 pt-2">
              <Label class="text-xs font-semibold">{{ $t('Pavadinimas anglų kalba') }}</Label>
              <Input
                v-model="http.name.en"
                :placeholder="$t('Duty name in English')"
              />
            </div>

            <!-- Email -->
            <div class="space-y-1.5">
              <Label class="text-xs font-semibold">{{ $t('El. paštas') }}</Label>
              <Input
                v-model="http.email"
                type="email"
                :placeholder="$t('pareigybe@vusa.lt')"
                :class="{ 'border-destructive': createErrors['email'] }"
              />
              <p v-if="createErrors['email']" class="text-xs text-destructive">
                {{ createErrors['email'][0] }}
              </p>
            </div>

            <!-- Description -->
            <div class="space-y-1.5">
              <Label class="text-xs font-semibold">{{ $t('Aprašymas') }}</Label>
              <Input
                v-model="http.description.lt"
                :placeholder="$t('Pareigybės aprašymas')"
              />
            </div>
          </CollapsibleContent>
        </Collapsible>

        <!-- Form actions -->
        <div class="flex justify-end gap-2 pt-2 border-t border-border">
          <Button variant="ghost" :disabled="http.processing" @click="cancelCreate">
            {{ $t('Atšaukti') }}
          </Button>
          <Button variant="brand" :disabled="http.processing || !http.name.lt" @click="createDuty">
            <Plus v-if="!http.processing" class="size-4 mr-1.5" />
            <Loader2 v-else class="size-4 mr-1.5 animate-spin" />
            {{ http.processing ? $t('Kuriama...') : $t('Sukurti pareigybę') }}
          </Button>
        </div>
      </div>
    </SectionCard>

    <!-- Regular duty selection view -->
    <template v-else>
      <!-- Search input -->
      <div class="relative">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" />
        <Input
          v-model="searchQuery"
          :placeholder="$t('Ieškoti pareigybės...')"
          class="pl-9 pr-9 h-10"
        />
        <Button
          v-if="searchQuery"
          variant="ghost"
          size="icon"
          class="absolute right-1 top-1/2 -translate-y-1/2 size-8"
          @click="clearSearch"
        >
          <X class="size-3.5" />
        </Button>
      </div>

      <!-- Create duty button -->
      <Button
        v-if="canCreateDuty"
        variant="outline"
        class="w-full border-dashed"
        @click="openCreateForm"
      >
        <Plus class="size-4 mr-2" />
        {{ $t('Sukurti naują pareigybę') }}
      </Button>

      <!-- Duties list -->
      <ScrollArea class="h-[320px] sm:h-[360px] pr-2">
        <div class="space-y-2">
          <button
            v-for="duty in sortedDuties"
            :key="duty.id"
            type="button"
            class="group w-full text-left border border-border bg-card p-3.5 sm:p-4 transition-colors hover:border-foreground/30 hover:bg-muted/40 pointer-coarse:py-4"
            @click="selectDuty(duty)"
          >
            <div class="flex items-start gap-3.5">
              <!-- Icon plate -->
              <div
                class="size-10 flex items-center justify-center border shrink-0 transition-colors"
                :class="{
                  'border-border bg-muted text-muted-foreground': getDutyStatus(duty).type === 'unknown',
                  'border-status-attention-border bg-status-attention-surface text-status-attention': getDutyStatus(duty).type === 'empty',
                  'border-border bg-muted text-foreground': getDutyStatus(duty).type === 'partial',
                  'border-status-success-border bg-status-success-surface text-status-success': getDutyStatus(duty).type === 'full',
                  'border-destructive/30 bg-destructive/10 text-destructive': getDutyStatus(duty).type === 'over'
                }"
              >
                <DutyIcon class="size-5" />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <p class="min-w-0 font-semibold text-sm sm:text-base text-foreground group-hover:text-brand transition-colors">
                    <InflectedDutyName :name="duty.name" />
                  </p>
                  <span
                    class="shrink-0 text-[11px] font-bold px-2 py-0.5 border"
                    :class="{
                      'border-border bg-muted text-muted-foreground': getDutyStatus(duty).type === 'unknown',
                      'border-status-attention-border bg-status-attention-surface text-status-attention': getDutyStatus(duty).type === 'empty',
                      'border-border bg-muted text-foreground': getDutyStatus(duty).type === 'partial',
                      'border-status-success-border bg-status-success-surface text-status-success': getDutyStatus(duty).type === 'full',
                      'border-destructive/30 bg-destructive/10 text-destructive': getDutyStatus(duty).type === 'over'
                    }"
                  >
                    {{ getDutyStatus(duty).label }}
                  </span>
                </div>

                <!-- Email -->
                <p v-if="duty.email" class="text-xs text-muted-foreground mt-0.5 truncate">
                  {{ duty.email }}
                </p>

                <!-- Capacity indicator -->
                <div class="flex items-center gap-3 mt-2">
                  <div class="flex items-center gap-1.5 text-xs">
                    <Users class="size-3.5 text-muted-foreground" />
                    <span class="font-semibold text-foreground">
                      {{ duty.current_users?.length || 0 }}
                      <span class="text-muted-foreground font-normal">/ {{ duty.places_to_occupy || '?' }}</span>
                    </span>
                  </div>

                  <!-- Capacity bar -->
                  <div class="flex-1 h-1.5 bg-muted border border-border/50 overflow-hidden max-w-28">
                    <div
                      class="h-full transition-all"
                      :class="{
                        'bg-muted-foreground': getDutyStatus(duty).type === 'unknown',
                        'bg-status-attention': getDutyStatus(duty).type === 'empty',
                        'bg-foreground/70': getDutyStatus(duty).type === 'partial',
                        'bg-status-success': getDutyStatus(duty).type === 'full',
                        'bg-destructive': getDutyStatus(duty).type === 'over'
                      }"
                      :style="{
                        width: duty.places_to_occupy
                          ? `${Math.min(100, ((duty.current_users?.length || 0) / duty.places_to_occupy) * 100)}%`
                          : '0%'
                      }"
                    />
                  </div>

                  <!-- Current users preview -->
                  <div v-if="duty.current_users?.length" class="flex -space-x-1.5">
                    <div
                      v-for="user in duty.current_users.slice(0, 3)"
                      :key="user.id"
                      class="size-6 border border-background bg-muted flex items-center justify-center text-[10px] font-bold overflow-hidden"
                      :title="user.name"
                    >
                      <img
                        v-if="user.profile_photo_path"
                        :src="user.profile_photo_path"
                        :alt="user.name"
                        class="size-full object-cover"
                      >
                      <span v-else>{{ user.name?.charAt(0) }}</span>
                    </div>
                    <div
                      v-if="duty.current_users.length > 3"
                      class="size-6 border border-background bg-muted flex items-center justify-center text-[10px] font-bold"
                    >
                      +{{ duty.current_users.length - 3 }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Arrow -->
              <ChevronRight class="size-4 text-muted-foreground group-hover:text-foreground group-hover:translate-x-0.5 transition-all shrink-0 mt-1" />
            </div>
          </button>

          <!-- Empty state -->
          <EmptyState
            v-if="sortedDuties.length === 0"
            :mode="searchQuery ? 'no-results' : 'empty'"
            :icon="DutyIcon"
            :title="searchQuery ? $t('Nerasta pareigybių pagal paiešką') : $t('Ši institucija neturi pareigybių')"
            :clear-label="searchQuery ? $t('Išvalyti paiešką') : undefined"
            :action-label="!searchQuery && canCreateDuty ? $t('Sukurti pirmą pareigybę') : undefined"
            @clear="clearSearch"
            @action="openCreateForm"
          />
        </div>
      </ScrollArea>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, inject, type ComputedRef } from 'vue';
import { usePage, useHttp } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Search,
  Users,
  ChevronRight,
  X,
  Edit3,
  Plus,
  ChevronDown,
  Loader2,
} from 'lucide-vue-next';

import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { SectionCard, EmptyState } from '@/Components/Patterns';
import type { useDutyUserWizard } from '@/Composables/useDutyUserWizard';
import { useDuplicateDutyCheck } from '@/Composables/useDuplicateDutyCheck';
import { DutyIcon, InstitutionIcon } from '@/Components/icons';
import DuplicateDutyWarning from '@/Components/AdminForms/DuplicateDutyWarning.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';

const wizard = inject<ReturnType<typeof useDutyUserWizard>>('dutyUserWizard')!;
const dutyTypesRef = inject<ComputedRef<App.Entities.Type[]> | App.Entities.Type[]>('dutyTypes', []);

const dutyTypes = computed(() =>
  'value' in dutyTypesRef ? dutyTypesRef.value : dutyTypesRef,
);

const page = usePage();
const auth = page.props.auth as any;
const canCreateDuty = computed(() => Boolean(auth?.can?.create?.duty) && !(wizard.state.institution as { is_external?: boolean } | undefined)?.is_external);

const isDutyTypesLoading = computed(() => !dutyTypes.value || dutyTypes.value.length === 0);

const openCreateForm = () => {
  wizard.loadDutyTypes();
  showCreateForm.value = true;
};

const searchQuery = ref('');
const showCreateForm = ref(false);
const showExtraFields = ref(false);

const http = useHttp({
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  email: '',
  institution_id: '',
  places_to_occupy: 1,
  contacts_grouping: 'none',
  types: [] as string[],
});

const createErrors = computed<Record<string, string[]>>(() => {
  const errs: Record<string, string[]> = {};
  for (const [key, val] of Object.entries(http.errors)) {
    errs[key] = Array.isArray(val) ? val : [val];
  }
  return errs;
});

const resetForm = () => {
  http.reset();
  showExtraFields.value = false;
};

const { matches: duplicateMatches } = useDuplicateDutyCheck(
  () => http.name.lt,
  () => wizard.state.institution?.id ?? null,
);

const cancelCreate = () => {
  showCreateForm.value = false;
  resetForm();
};

const createDuty = () => {
  if (!wizard.state.institution?.id) return;

  http.post(route('duties.store'), {
    onSuccess: (response: any) => {
      if (response?.duty) {
        const newDutyData = response.duty as App.Entities.Duty;

        if (wizard.state.institution) {
          const updatedInstitution = {
            ...wizard.state.institution,
            duties: [...(wizard.state.institution.duties || []), newDutyData],
          } as App.Entities.Institution;
          wizard.setInstitution(updatedInstitution);
        }
        toast.success($t('Pareigybė sėkmingai sukurta'));
        cancelCreate();
      }
    },
    onError: () => {
      toast.error($t('Patikrinkite formos laukus'));
    },
    onNetworkError: () => {
      toast.error($t('Nepavyko sukurti pareigybės'));
    },
  });
};

const duties = computed(() => {
  return wizard.state.institution?.duties || [];
});

const filteredDuties = computed(() => {
  if (!searchQuery.value) return duties.value;
  const query = searchQuery.value.toLowerCase();
  return duties.value.filter(d =>
    d.name?.toString().toLowerCase().includes(query)
    || d.email?.toLowerCase().includes(query),
  );
});

const getDutyStatus = (duty: any) => {
  const currentCount = duty.current_users?.length || 0;
  const maxCount = duty.places_to_occupy || 0;

  if (maxCount === 0) return { type: 'unknown', label: $t('Nenurodyta') };
  if (currentCount === 0) return { type: 'empty', label: $t('Neužimta') };
  if (currentCount < maxCount) return { type: 'partial', label: $t('Dalinai užimta') };
  if (currentCount === maxCount) return { type: 'full', label: $t('Pilnai užimta') };
  return { type: 'over', label: $t('Viršija limitą') };
};

const sortedDuties = computed(() => {
  return [...filteredDuties.value].sort((a, b) => {
    const aStatus = getDutyStatus(a);
    const bStatus = getDutyStatus(b);

    const priority: Record<string, number> = { empty: 0, partial: 1, unknown: 2, full: 3, over: 4 };
    const aPriority = priority[aStatus.type] ?? 5;
    const bPriority = priority[bStatus.type] ?? 5;

    if (aPriority !== bPriority) return aPriority - bPriority;

    return (a.name?.toString() || '').localeCompare(b.name?.toString() || '');
  });
});

const selectDuty = (duty: any) => {
  wizard.setDuty(duty as App.Entities.Duty);
  wizard.nextStep();
};

const clearSearch = () => {
  searchQuery.value = '';
};

const toggleType = (typeId: string) => {
  const idx = http.types.indexOf(typeId);
  if (idx === -1) {
    http.types.push(typeId);
  }
  else {
    http.types.splice(idx, 1);
  }
};
</script>
