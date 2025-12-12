<script setup lang="ts">
import { ref, computed, inject, reactive } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { toast } from 'vue-sonner'
import { 
  Search, 
  Building2, 
  ChevronRight,
  Users,
  X,
  AlertTriangle,
  Plus,
  ChevronDown,
  Loader2
} from 'lucide-vue-next'

import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { Label } from '@/Components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible'

import type { useDutyUserWizard } from '@/Composables/useDutyUserWizard'

const props = defineProps<{
  institutions: App.Entities.Institution[]
}>()

const wizard = inject<ReturnType<typeof useDutyUserWizard>>('dutyUserWizard')!
const assignableTenants = inject<App.Entities.Tenant[]>('assignableTenants', [])
const institutionTypes = inject<App.Entities.Type[]>('institutionTypes', [])
const addInstitution = inject<(institution: App.Entities.Institution) => void>('addInstitution')

const page = usePage()
const auth = page.props.auth as any

// Search state
const searchQuery = ref('')

// Creation mode
const showCreateForm = ref(false)
const isCreating = ref(false)
const createErrors = ref<Record<string, string[]>>({})

// New institution form data
const newInstitution = reactive({
  name: { lt: '', en: '' },
  short_name: { lt: '', en: '' },
  tenant_id: '',
  types: [] as string[],
  contacts_layout: 'aside' as 'aside' | 'below',
  email: '',
  phone: '',
  is_active: true
})

// Additional fields expanded
const showAdditionalFields = ref(false)

// Check permission for creating institutions
const canCreateInstitution = computed(() => auth?.can?.create?.institution)

/**
 * Check if an institution needs attention:
 * - Has no duties at all
 * - Has duties with no active users
 */
const getInstitutionStatus = (institution: App.Entities.Institution) => {
  const duties = institution.duties || []
  
  if (duties.length === 0) {
    return { needsAttention: true, reason: 'no_duties', label: $t('Nėra pareigybių') }
  }
  
  const emptyDuties = duties.filter(d => !d.current_users?.length)
  if (emptyDuties.length > 0) {
    return { needsAttention: true, reason: 'empty_duties', label: $t('Tuščių pareigybių: ') + emptyDuties.length }
  }
  
  return { needsAttention: false, reason: null, label: null }
}

/**
 * Get count of users staying longer than 2 years (informational, not requiring attention)
 */
const getLongStayingUsersCount = (institution: App.Entities.Institution): number => {
  const duties = institution.duties || []
  const twoYearsAgo = new Date()
  twoYearsAgo.setFullYear(twoYearsAgo.getFullYear() - 2)
  
  let count = 0
  for (const duty of duties) {
    for (const user of duty.current_users || []) {
      const pivot = (user as any).pivot
      if (pivot?.start_date) {
        const startDate = new Date(pivot.start_date)
        if (startDate < twoYearsAgo) {
          count++
        }
      }
    }
  }
  return count
}

// Institutions that need attention (prioritized)
const institutionsNeedingAttention = computed(() => {
  return props.institutions
    .filter(i => getInstitutionStatus(i).needsAttention)
    .sort((a, b) => {
      // Sort by severity: no_duties > empty_duties
      const priority: Record<string, number> = { no_duties: 0, empty_duties: 1 }
      const aStatus = getInstitutionStatus(a)
      const bStatus = getInstitutionStatus(b)
      return (priority[aStatus.reason || ''] ?? 2) - (priority[bStatus.reason || ''] ?? 2)
    })
})

// All other institutions (not needing attention)
const otherInstitutions = computed(() => {
  const attentionIds = new Set(institutionsNeedingAttention.value.map(i => i.id))
  return props.institutions.filter(i => !attentionIds.has(i.id))
})

// Filtered institutions based on search
const filterBySearch = (institutions: App.Entities.Institution[]) => {
  if (!searchQuery.value) return institutions
  const query = searchQuery.value.toLowerCase()
  return institutions.filter(i => 
    i.name?.toString().toLowerCase().includes(query) ||
    i.short_name?.toString().toLowerCase().includes(query)
  )
}

const filteredAttentionInstitutions = computed(() => filterBySearch(institutionsNeedingAttention.value))
const filteredOtherInstitutions = computed(() => filterBySearch(otherInstitutions.value))

const hasResults = computed(() => 
  filteredAttentionInstitutions.value.length > 0 || 
  filteredOtherInstitutions.value.length > 0
)

// Handle institution selection
const selectInstitution = (institution: App.Entities.Institution) => {
  wizard.setInstitution(institution)
  wizard.nextStep()
}

const clearSearch = () => {
  searchQuery.value = ''
}

// Toggle type selection
const toggleType = (typeId: string) => {
  const idx = newInstitution.types.indexOf(typeId)
  if (idx === -1) {
    newInstitution.types.push(typeId)
  } else {
    newInstitution.types.splice(idx, 1)
  }
}

// Create institution handlers
const openCreateForm = () => {
  showCreateForm.value = true
  createErrors.value = {}
}

const cancelCreate = () => {
  showCreateForm.value = false
  createErrors.value = {}
  // Reset form
  newInstitution.name = { lt: '', en: '' }
  newInstitution.short_name = { lt: '', en: '' }
  newInstitution.tenant_id = ''
  newInstitution.types = []
  newInstitution.contacts_layout = 'aside'
  newInstitution.email = ''
  newInstitution.phone = ''
  newInstitution.is_active = true
  showAdditionalFields.value = false
}

const validateCreateForm = (): boolean => {
  createErrors.value = {}
  
  if (!newInstitution.name.lt?.trim()) {
    createErrors.value['name.lt'] = [$t('Pavadinimas yra privalomas')]
  }
  
  if (!newInstitution.tenant_id) {
    createErrors.value['tenant_id'] = [$t('Padalinys yra privalomas')]
  }
  
  return Object.keys(createErrors.value).length === 0
}

const createInstitution = async () => {
  if (!validateCreateForm()) return
  
  isCreating.value = true
  createErrors.value = {}
  
  try {
    const response = await axios.post(route('institutions.store'), {
      ...newInstitution,
      is_active: true
    }, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.data.success && response.data.institution) {
      // Add the new institution to the list
      addInstitution?.(response.data.institution)
      toast.success($t('Institucija sėkmingai sukurta'))
      cancelCreate()
    }
  } catch (error: any) {
    // Handle validation errors
    if (error.response?.status === 422 && error.response?.data?.errors) {
      Object.entries(error.response.data.errors).forEach(([key, value]) => {
        createErrors.value[key] = Array.isArray(value) ? value as string[] : [value as string]
      })
      toast.error($t('Patikrinkite formos laukus'))
    } else {
      createErrors.value['general'] = [$t('Nepavyko sukurti institucijos')]
      toast.error($t('Nepavyko sukurti institucijos'))
    }
  } finally {
    isCreating.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Create Institution Form -->
    <Transition name="fade" mode="out-in">
      <div v-if="showCreateForm" class="space-y-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="h-10 w-10 rounded-xl bg-primary/10 flex items-center justify-center">
            <Building2 class="h-5 w-5 text-primary" />
          </div>
          <div>
            <h3 class="font-semibold text-foreground">{{ $t('Nauja institucija') }}</h3>
            <p class="text-sm text-muted-foreground">{{ $t('Užpildykite informaciją apie naują instituciją') }}</p>
          </div>
        </div>

        <div class="space-y-4">
          <!-- Name (required) -->
          <div class="space-y-2">
            <Label :class="{ 'text-vusa-red': createErrors['name.lt'] }">
              {{ $t('Pavadinimas') }} *
            </Label>
            <Input 
              v-model="newInstitution.name.lt" 
              :placeholder="$t('Institucijos pavadinimas')"
              :class="{ 'border-vusa-red focus-visible:ring-vusa-red': createErrors['name.lt'] }"
            />
            <p v-if="createErrors['name.lt']" class="text-xs text-vusa-red">
              {{ createErrors['name.lt'][0] }}
            </p>
          </div>

          <!-- Tenant (required) -->
          <div class="space-y-2">
            <Label :class="{ 'text-vusa-red': createErrors['tenant_id'] }">
              {{ $t('Padalinys') }} *
            </Label>
            <Select v-model="newInstitution.tenant_id">
              <SelectTrigger :class="{ 'border-vusa-red focus-visible:ring-vusa-red': createErrors['tenant_id'] }">
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
            <p v-if="createErrors['tenant_id']" class="text-xs text-vusa-red">
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
                class="inline-flex items-center px-3 py-1.5 rounded-full text-sm border transition-colors"
                :class="newInstitution.types.includes(String(type.id))
                  ? 'bg-primary text-primary-foreground border-primary'
                  : 'bg-background hover:bg-accent border-border'"
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
          <Collapsible v-model:open="showAdditionalFields" class="border rounded-lg">
            <CollapsibleTrigger as-child>
              <Button variant="ghost" class="w-full justify-between h-10 px-3">
                <span class="text-sm">{{ $t('Papildoma informacija') }}</span>
                <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': showAdditionalFields }" />
              </Button>
            </CollapsibleTrigger>
            <CollapsibleContent class="p-3 pt-0 space-y-4">
              <!-- Short name -->
              <div class="space-y-2">
                <Label>{{ $t('Trumpinys') }}</Label>
                <Input 
                  v-model="newInstitution.short_name.lt" 
                  :placeholder="$t('Pvz.: VU SA FSF')"
                />
              </div>

              <!-- Email -->
              <div class="space-y-2">
                <Label :class="{ 'text-vusa-red': createErrors['email'] }">{{ $t('El. paštas') }}</Label>
                <Input 
                  v-model="newInstitution.email" 
                  type="email"
                  placeholder="institucija@vusa.lt"
                  :class="{ 'border-vusa-red': createErrors['email'] }"
                />
                <p v-if="createErrors['email']" class="text-xs text-vusa-red">
                  {{ createErrors['email'][0] }}
                </p>
              </div>

              <!-- Phone -->
              <div class="space-y-2">
                <Label>{{ $t('Telefonas') }}</Label>
                <Input 
                  v-model="newInstitution.phone" 
                  placeholder="+370 600 00000"
                />
              </div>

              <!-- Contacts layout -->
              <div class="space-y-2">
                <Label>{{ $t('Kontaktų išdėstymas') }}</Label>
                <Select v-model="newInstitution.contacts_layout">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="aside">{{ $t('Šone') }}</SelectItem>
                    <SelectItem value="below">{{ $t('Apačioje') }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </CollapsibleContent>
          </Collapsible>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-4 border-t">
          <Button variant="ghost" @click="cancelCreate" :disabled="isCreating">
            {{ $t('Atšaukti') }}
          </Button>
          <Button @click="createInstitution" :disabled="isCreating">
            <Loader2 v-if="isCreating" class="h-4 w-4 mr-2 animate-spin" />
            {{ isCreating ? $t('Kuriama...') : $t('Sukurti ir tęsti') }}
          </Button>
        </div>
      </div>

      <!-- Institution List -->
      <div v-else class="space-y-6">
        <!-- Search input -->
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            :placeholder="$t('Ieškoti institucijos...')"
            class="pl-10 pr-10 h-11"
          />
          <Button 
            v-if="searchQuery" 
            variant="ghost" 
            size="icon" 
            class="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8"
            @click="clearSearch"
          >
            <X class="h-4 w-4" />
          </Button>
        </div>

        <!-- Results -->
        <ScrollArea class="h-[420px] pr-4">
          <div class="space-y-6">
            <!-- Institutions needing attention (prioritized) -->
            <div v-if="filteredAttentionInstitutions.length > 0">
              <div class="flex items-center gap-2 mb-3">
                <AlertTriangle class="h-4 w-4 text-vusa-yellow-dark" />
                <h3 class="text-sm font-medium text-foreground">{{ $t('Reikia dėmesio') }}</h3>
                <Badge variant="secondary" class="text-xs bg-vusa-yellow/20 text-vusa-yellow-dark">{{ filteredAttentionInstitutions.length }}</Badge>
              </div>
              
              <div class="space-y-2">
                <button
                  v-for="institution in filteredAttentionInstitutions"
                  :key="institution.id"
                  type="button"
                  class="group w-full text-left rounded-xl border border-vusa-yellow/30 bg-gradient-to-r from-vusa-yellow/10 to-amber-50/30 dark:from-vusa-yellow-dark/10 dark:to-amber-950/10 p-4 transition-all duration-200 hover:shadow-md hover:border-vusa-yellow/50 hover:scale-[1.01]"
                  @click="selectInstitution(institution)"
                >
                  <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl bg-white dark:bg-zinc-800 border border-border/50 flex items-center justify-center shrink-0 shadow-sm">
                      <img 
                        v-if="institution.logo_url" 
                        :src="institution.logo_url" 
                        :alt="String(institution.name)"
                        class="h-10 w-10 object-contain rounded-lg"
                      />
                      <Building2 v-else class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-foreground group-hover:text-primary transition-colors truncate">
                        {{ institution.name }}
                      </p>
                      <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <Badge v-if="institution.tenant?.shortname" variant="outline" class="text-xs">
                          {{ institution.tenant.shortname }}
                        </Badge>
                        <Badge variant="secondary" class="text-xs bg-vusa-yellow/20 text-vusa-yellow-dark dark:text-vusa-yellow">
                          {{ getInstitutionStatus(institution).label }}
                        </Badge>
                      </div>
                    </div>
                    <ChevronRight class="h-5 w-5 text-muted-foreground group-hover:text-primary group-hover:translate-x-1 transition-all shrink-0" />
                  </div>
                </button>
              </div>
            </div>

            <!-- Other institutions -->
            <div v-if="filteredOtherInstitutions.length > 0">
              <div class="flex items-center gap-2 mb-3">
                <Building2 class="h-4 w-4 text-muted-foreground" />
                <h3 class="text-sm font-medium text-foreground">{{ $t('Visos institucijos') }}</h3>
                <Badge variant="secondary" class="text-xs">{{ filteredOtherInstitutions.length }}</Badge>
              </div>
              
              <div class="space-y-2">
                <button
                  v-for="institution in filteredOtherInstitutions"
                  :key="institution.id"
                  type="button"
                  class="group w-full text-left rounded-xl border border-border/50 bg-card p-4 transition-all duration-200 hover:shadow-md hover:border-primary/30 hover:bg-accent/30"
                  @click="selectInstitution(institution)"
                >
                  <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl bg-muted/50 border border-border/50 flex items-center justify-center shrink-0">
                      <img 
                        v-if="institution.logo_url" 
                        :src="institution.logo_url" 
                        :alt="String(institution.name)"
                        class="h-10 w-10 object-contain rounded-lg"
                      />
                      <Building2 v-else class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-foreground group-hover:text-primary transition-colors truncate">
                        {{ institution.name }}
                      </p>
                      <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <Badge v-if="institution.tenant?.shortname" variant="outline" class="text-xs">
                          {{ institution.tenant.shortname }}
                        </Badge>
                        <span v-if="institution.duties?.length" class="text-xs text-muted-foreground flex items-center gap-1">
                          <Users class="h-3 w-3" />
                          {{ institution.duties.length }} {{ $t('pareigybės') }}
                        </span>
                        <Badge v-if="getLongStayingUsersCount(institution) > 0" variant="secondary" class="text-xs">
                          {{ $t('Ilgai esančių:') }} {{ getLongStayingUsersCount(institution) }}
                        </Badge>
                      </div>
                    </div>
                    <ChevronRight class="h-5 w-5 text-muted-foreground group-hover:text-primary group-hover:translate-x-1 transition-all shrink-0" />
                  </div>
                </button>
              </div>
            </div>

            <!-- Empty state -->
            <div v-if="!hasResults" class="text-center py-12">
              <div class="h-16 w-16 rounded-full bg-muted mx-auto flex items-center justify-center mb-4">
                <Building2 class="h-8 w-8 text-muted-foreground" />
              </div>
              <p class="text-muted-foreground">{{ $t('Nerasta institucijų pagal paiešką') }}</p>
              <Button variant="link" class="mt-2" @click="clearSearch">
                {{ $t('Išvalyti paiešką') }}
              </Button>
            </div>
          </div>
        </ScrollArea>

        <!-- Create new institution button -->
        <div v-if="canCreateInstitution" class="pt-4 border-t">
          <Button variant="outline" class="w-full" @click="openCreateForm">
            <Plus class="h-4 w-4 mr-2" />
            {{ $t('Sukurti naują instituciją') }}
          </Button>
        </div>
      </div>
    </Transition>
  </div>
</template>
