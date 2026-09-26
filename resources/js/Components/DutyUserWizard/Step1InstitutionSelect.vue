<template>
  <div class="space-y-6">
    <!-- Create Institution Form -->
    <Transition name="fade" mode="out-in">
      <div v-if="showCreateForm" class="space-y-6 border border-border bg-card p-4 sm:p-6">
        <div class="flex items-center gap-3 border-b border-border pb-4">
          <div class="flex size-9 items-center justify-center border border-border bg-muted">
            <Building2 class="size-4 text-foreground" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-foreground">
              {{ $t('Nauja institucija') }}
            </h3>
            <p class="text-xs text-muted-foreground">
              {{ $t('Užpildykite informaciją apie naują instituciją') }}
            </p>
          </div>
        </div>

        <div class="space-y-4">
          <!-- Name (required) -->
          <div class="space-y-1.5">
            <Label :class="{ 'text-destructive': createErrors['name.lt'] }">
              {{ $t('Pavadinimas') }} *
            </Label>
            <Input
              v-model="http.name.lt"
              :placeholder="$t('Institucijos pavadinimas')"
              :class="{ 'border-destructive focus-visible:ring-destructive': createErrors['name.lt'] }"
            />
            <p v-if="createErrors['name.lt']" class="text-xs text-destructive">
              {{ createErrors['name.lt'][0] }}
            </p>
          </div>

          <!-- Tenant (required) -->
          <div class="space-y-1.5">
            <Label :class="{ 'text-destructive': createErrors['tenant_id'] }">
              {{ $t('Padalinys') }} *
            </Label>
            <Select v-model="http.tenant_id">
              <SelectTrigger :class="{ 'border-destructive focus-visible:ring-destructive': createErrors['tenant_id'] }">
                <SelectValue :placeholder="$t('Pasirinkti padalinį...')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="tenant in assignableTenants"
                  :key="tenant.id"
                  :value="tenant.id"
                >
                  {{ tenant.shortname }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="createErrors['tenant_id']" class="text-xs text-destructive">
              {{ createErrors['tenant_id'][0] }}
            </p>
          </div>

          <!-- Institution Types -->
          <div v-if="institutionTypes.length > 0" class="space-y-2">
            <Label>{{ $t('Institucijos tipas') }}</Label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="type in institutionTypes"
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
            <p class="text-xs text-muted-foreground">
              {{ $t('Tipas nustato ar institucija bus rodoma viešai') }}
            </p>
          </div>

          <!-- Additional fields (collapsible) -->
          <Collapsible v-model:open="showAdditionalFields" class="border border-border">
            <CollapsibleTrigger as-child>
              <Button variant="ghost" class="w-full justify-between h-10 px-3 font-medium">
                <span class="text-xs">{{ $t('Papildoma informacija') }}</span>
                <ChevronDown class="size-4 transition-transform" :class="{ 'rotate-180': showAdditionalFields }" />
              </Button>
            </CollapsibleTrigger>
            <CollapsibleContent class="p-3 pt-0 space-y-4 border-t border-border mt-1">
              <!-- Short name -->
              <div class="space-y-1.5 pt-2">
                <Label>{{ $t('Trumpinys') }}</Label>
                <Input
                  v-model="http.short_name.lt"
                  :placeholder="$t('Pvz.: VU SA FSF')"
                />
              </div>

              <!-- Email -->
              <div class="space-y-1.5">
                <Label :class="{ 'text-destructive': createErrors['email'] }">{{ $t('El. paštas') }}</Label>
                <Input
                  v-model="http.email"
                  type="email"
                  placeholder="institucija@vusa.lt"
                  :class="{ 'border-destructive': createErrors['email'] }"
                />
                <p v-if="createErrors['email']" class="text-xs text-destructive">
                  {{ createErrors['email'][0] }}
                </p>
              </div>

              <!-- Phone -->
              <div class="space-y-1.5">
                <Label>{{ $t('Telefonas') }}</Label>
                <Input
                  v-model="http.phone"
                  placeholder="+370 600 00000"
                />
              </div>
            </CollapsibleContent>
          </Collapsible>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-4 border-t border-border">
          <Button variant="ghost" :disabled="http.processing" @click="cancelCreate">
            {{ $t('Atšaukti') }}
          </Button>
          <Button variant="brand" :disabled="http.processing" @click="createInstitution">
            <Loader2 v-if="http.processing" class="size-4 mr-2 animate-spin" />
            {{ http.processing ? $t('Kuriama...') : $t('Sukurti ir tęsti') }}
          </Button>
        </div>
      </div>

      <!-- Institution List -->
      <div v-else class="space-y-5">
        <!-- Search input -->
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" />
          <Input
            v-model="searchQuery"
            :placeholder="$t('Ieškoti institucijos...')"
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

        <!-- Results list -->
        <ScrollArea class="h-[360px] sm:h-[400px] pr-2">
          <div class="space-y-6">
            <!-- Institutions needing attention (prioritized) -->
            <div v-if="filteredAttentionInstitutions.length > 0" class="space-y-3">
              <div class="flex items-center gap-2">
                <AlertTriangle class="size-4 text-status-attention" />
                <h3 class="text-xs font-bold uppercase tracking-wider text-status-attention">
                  {{ $t('Reikia dėmesio') }}
                </h3>
                <span class="inline-flex items-center border border-status-attention-border bg-status-attention-surface px-1.5 py-0.5 text-[10px] font-bold text-status-attention">
                  {{ filteredAttentionInstitutions.length }}
                </span>
              </div>

              <div class="space-y-2">
                <button
                  v-for="institution in filteredAttentionInstitutions"
                  :key="institution.id"
                  type="button"
                  class="group w-full text-left border border-status-attention-border bg-status-attention-surface/30 p-3.5 sm:p-4 transition-colors hover:bg-status-attention-surface/60 pointer-coarse:py-4"
                  @click="selectInstitution(institution)"
                >
                  <div class="flex items-center gap-3.5">
                    <div class="size-11 border border-status-attention-border bg-background flex items-center justify-center shrink-0">
                      <img
                        v-if="institution.logo_url"
                        :src="institution.logo_url"
                        :alt="String(institution.name)"
                        class="size-9 object-contain"
                      >
                      <Building2 v-else class="size-5 text-status-attention" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-semibold text-sm sm:text-base text-foreground group-hover:text-brand transition-colors truncate">
                        {{ institution.name }}
                      </p>
                      <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <Badge v-if="institution.tenant?.shortname" variant="outline" class="text-[11px]">
                          {{ institution.tenant.shortname }}
                        </Badge>
                        <span class="inline-flex items-center border border-status-attention-border bg-status-attention-surface px-1.5 py-0.5 text-[11px] font-medium text-status-attention">
                          {{ getInstitutionStatus(institution).label }}
                        </span>
                      </div>
                    </div>
                    <ChevronRight class="size-4 text-muted-foreground group-hover:text-foreground group-hover:translate-x-0.5 transition-all shrink-0" />
                  </div>
                </button>
              </div>
            </div>

            <!-- Other institutions -->
            <div v-if="filteredOtherInstitutions.length > 0" class="space-y-3">
              <div class="flex items-center gap-2">
                <Building2 class="size-4 text-muted-foreground" />
                <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                  {{ $t('Visos institucijos') }}
                </h3>
                <span class="inline-flex items-center border border-border bg-muted px-1.5 py-0.5 text-[10px] font-bold text-muted-foreground">
                  {{ filteredOtherInstitutions.length }}
                </span>
              </div>

              <div class="space-y-2">
                <button
                  v-for="institution in filteredOtherInstitutions"
                  :key="institution.id"
                  type="button"
                  class="group w-full text-left border border-border bg-card p-3.5 sm:p-4 transition-colors hover:border-foreground/30 hover:bg-muted/40 pointer-coarse:py-4"
                  @click="selectInstitution(institution)"
                >
                  <div class="flex items-center gap-3.5">
                    <div class="size-11 border border-border bg-muted flex items-center justify-center shrink-0">
                      <img
                        v-if="institution.logo_url"
                        :src="institution.logo_url"
                        :alt="String(institution.name)"
                        class="size-9 object-contain"
                      >
                      <Building2 v-else class="size-5 text-muted-foreground" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-semibold text-sm sm:text-base text-foreground group-hover:text-brand transition-colors truncate">
                        {{ institution.name }}
                      </p>
                      <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <Badge v-if="institution.tenant?.shortname" variant="outline" class="text-[11px]">
                          {{ institution.tenant.shortname }}
                        </Badge>
                        <Badge v-if="(institution as { is_external?: boolean }).is_external" variant="secondary" class="text-[11px]">
                          {{ $t('forms.fields.external_duty_badge') }}
                        </Badge>
                        <span v-if="institution.duties?.length" class="text-xs text-muted-foreground flex items-center gap-1">
                          <Users class="size-3" />
                          {{ institution.duties.length }} {{ $t('pareigybės') }}
                        </span>
                        <span v-if="getLongStayingUsersCount(institution) > 0" class="text-xs text-muted-foreground">
                          {{ $t('Ilgai esančių:') }} {{ getLongStayingUsersCount(institution) }}
                        </span>
                      </div>
                    </div>
                    <ChevronRight class="size-4 text-muted-foreground group-hover:text-foreground group-hover:translate-x-0.5 transition-all shrink-0" />
                  </div>
                </button>
              </div>
            </div>

            <!-- Empty state -->
            <EmptyState
              v-if="!hasResults"
              mode="no-results"
              :title="$t('Nerasta institucijų pagal paiešką')"
              :clear-label="$t('Išvalyti paiešką')"
              @clear="clearSearch"
            />
          </div>
        </ScrollArea>

        <!-- Create new institution button -->
        <div v-if="canCreateInstitution" class="pt-4 border-t border-border">
          <Button variant="outline" class="w-full" @click="openCreateForm">
            <Plus class="size-4 mr-2" />
            {{ $t('Sukurti naują instituciją') }}
          </Button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, inject } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage, useHttp } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
  Search,
  Building2,
  ChevronRight,
  Users,
  X,
  AlertTriangle,
  Plus,
  ChevronDown,
  Loader2,
} from 'lucide-vue-next';

import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import type { useDutyUserWizard } from '@/Composables/useDutyUserWizard';

const props = defineProps<{
  institutions: App.Entities.Institution[];
}>();

const wizard = inject<ReturnType<typeof useDutyUserWizard>>('dutyUserWizard')!;
const assignableTenants = inject<App.Entities.Tenant[]>('assignableTenants', []);
const institutionTypes = inject<App.Entities.Type[]>('institutionTypes', []);
const addInstitution = inject<(institution: App.Entities.Institution) => void>('addInstitution');

const page = usePage();
const auth = page.props.auth as any;

// Search state
const searchQuery = ref('');

// Creation mode
const showCreateForm = ref(false);

// Form state via useHttp
const http = useHttp({
  name: { lt: '', en: '' },
  short_name: { lt: '', en: '' },
  tenant_id: '',
  types: [] as string[],
  email: '',
  phone: '',
  is_active: true,
});

// Normalize form errors
const createErrors = computed<Record<string, string[]>>(() => {
  const errs: Record<string, string[]> = {};
  for (const [key, val] of Object.entries(http.errors)) {
    errs[key] = Array.isArray(val) ? val : [val];
  }
  return errs;
});

// Additional fields expanded
const showAdditionalFields = ref(false);

// Check permission for creating institutions
const canCreateInstitution = computed(() => Boolean(auth?.can?.create?.institution));

const getInstitutionStatus = (institution: App.Entities.Institution) => {
  const duties = institution.duties || [];

  if (duties.length === 0) {
    return { needsAttention: true, reason: 'no_duties', label: $t('Nėra pareigybių') };
  }

  const emptyDuties = duties.filter(d => !d.current_users?.length);
  if (emptyDuties.length > 0) {
    return { needsAttention: true, reason: 'empty_duties', label: `${$t('Tuščių pareigybių: ')}${emptyDuties.length}` };
  }

  return { needsAttention: false, reason: null, label: null };
};

const getLongStayingUsersCount = (institution: App.Entities.Institution): number => {
  const duties = institution.duties || [];
  const twoYearsAgo = new Date();
  twoYearsAgo.setFullYear(twoYearsAgo.getFullYear() - 2);

  let count = 0;
  for (const duty of duties) {
    for (const user of duty.current_users || []) {
      const { pivot } = user as any;
      if (pivot?.start_date) {
        const startDate = new Date(pivot.start_date);
        if (startDate < twoYearsAgo) {
          count++;
        }
      }
    }
  }
  return count;
};

// Institutions that need attention (prioritized)
const institutionsNeedingAttention = computed(() => {
  return props.institutions
    .filter(i => getInstitutionStatus(i).needsAttention)
    .sort((a, b) => {
      const priority: Record<string, number> = { no_duties: 0, empty_duties: 1 };
      const aStatus = getInstitutionStatus(a);
      const bStatus = getInstitutionStatus(b);
      return (priority[aStatus.reason || ''] ?? 2) - (priority[bStatus.reason || ''] ?? 2);
    });
});

const otherInstitutions = computed(() => {
  const attentionIds = new Set(institutionsNeedingAttention.value.map(i => i.id));
  return props.institutions.filter(i => !attentionIds.has(i.id));
});

const filterBySearch = (institutions: App.Entities.Institution[]) => {
  if (!searchQuery.value) return institutions;
  const query = searchQuery.value.toLowerCase();
  return institutions.filter(i =>
    i.name?.toString().toLowerCase().includes(query)
    || i.short_name?.toString().toLowerCase().includes(query),
  );
};

const filteredAttentionInstitutions = computed(() => filterBySearch(institutionsNeedingAttention.value));
const filteredOtherInstitutions = computed(() => filterBySearch(otherInstitutions.value));

const hasResults = computed(() =>
  filteredAttentionInstitutions.value.length > 0
  || filteredOtherInstitutions.value.length > 0,
);

const selectInstitution = (institution: App.Entities.Institution) => {
  wizard.setInstitution(institution);
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

const openCreateForm = () => {
  showCreateForm.value = true;
  http.clearErrors();
};

const cancelCreate = () => {
  showCreateForm.value = false;
  http.reset();
  showAdditionalFields.value = false;
};

const validateCreateForm = (): boolean => {
  http.clearErrors();

  if (!http.name.lt?.trim()) {
    http.setError('name.lt', $t('Pavadinimas yra privalomas'));
  }

  if (!http.tenant_id) {
    http.setError('tenant_id', $t('Padalinys yra privalomas'));
  }

  return !http.hasErrors;
};

const createInstitution = () => {
  if (!validateCreateForm()) return;

  http.post(route('institutions.store'), {
    onSuccess: (response: any) => {
      if (response?.institution) {
        addInstitution?.(response.institution);
        toast.success($t('Institucija sėkmingai sukurta'));
        cancelCreate();
      }
    },
    onError: () => {
      toast.error($t('Patikrinkite formos laukus'));
    },
    onNetworkError: () => {
      toast.error($t('Nepavyko sukurti institucijos'));
    },
  });
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateX(8px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
</style>
