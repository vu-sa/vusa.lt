<script setup lang="ts">
import { ref, computed, inject, reactive } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { toast } from 'vue-sonner'
import { trans as $t } from 'laravel-vue-i18n'
import { 
  Search, 
  Users, 
  ChevronRight, 
  AlertCircle,
  CheckCircle,
  X,
  Edit3,
  Plus,
  ChevronDown
} from 'lucide-vue-next'

import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { Alert, AlertDescription } from '@/Components/ui/alert'
import { Label } from '@/Components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible'
import Icons from '@/Types/Icons/regular'

import type { useDutyUserWizard } from '@/Composables/useDutyUserWizard'

const wizard = inject<ReturnType<typeof useDutyUserWizard>>('dutyUserWizard')!
const dutyTypes = inject<App.Entities.Type[]>('dutyTypes', [])

const page = usePage()
const auth = page.props.auth as any
const canCreateDuty = computed(() => auth?.can?.create?.duty)

// Search state
const searchQuery = ref('')

// Create duty form state
const showCreateForm = ref(false)
const isCreating = ref(false)
const createErrors = ref<Record<string, string[]>>({})
const showExtraFields = ref(false)

const newDuty = reactive({
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  email: '',
  institution_id: '',
  places_to_occupy: 1,
  contacts_grouping: 'none',
  types: [] as string[]
})

// Reset form
const resetForm = () => {
  newDuty.name = { lt: '', en: '' }
  newDuty.description = { lt: '', en: '' }
  newDuty.email = ''
  newDuty.places_to_occupy = 1
  newDuty.contacts_grouping = 'none'
  newDuty.types = []
  createErrors.value = {}
  showExtraFields.value = false
}

const cancelCreate = () => {
  showCreateForm.value = false
  resetForm()
}

const createDuty = async () => {
  if (!wizard.state.institution?.id) return
  
  isCreating.value = true
  createErrors.value = {}
  
  const institutionId = wizard.state.institution.id
  
  try {
    const response = await axios.post(route('duties.store'), {
      ...newDuty,
      institution_id: institutionId
    }, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.data.success && response.data.duty) {
      // Add the new duty to the current institution
      const newDutyData = response.data.duty as App.Entities.Duty
      
      // Update the institution's duties list locally
      if (wizard.state.institution) {
        const updatedInstitution = {
          ...wizard.state.institution,
          duties: [...(wizard.state.institution.duties || []), newDutyData]
        } as App.Entities.Institution
        wizard.setInstitution(updatedInstitution)
      }
      toast.success($t('Pareigybė sėkmingai sukurta'))
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
      createErrors.value['general'] = [$t('Nepavyko sukurti pareigybės')]
      toast.error($t('Nepavyko sukurti pareigybės'))
    }
  } finally {
    isCreating.value = false
  }
}

// Get duties from selected institution
const duties = computed(() => {
  return wizard.state.institution?.duties || []
})

// Filtered duties
const filteredDuties = computed(() => {
  if (!searchQuery.value) return duties.value
  const query = searchQuery.value.toLowerCase()
  return duties.value.filter(d => 
    d.name?.toString().toLowerCase().includes(query) ||
    d.email?.toLowerCase().includes(query)
  )
})

// Compute status for each duty
const getDutyStatus = (duty: any) => {
  const currentCount = duty.current_users?.length || 0
  const maxCount = duty.places_to_occupy || 0

  if (maxCount === 0) return { type: 'unknown', label: $t('Nenurodyta'), color: 'secondary' }
  if (currentCount === 0) return { type: 'empty', label: $t('Neužimta'), color: 'outline' }
  if (currentCount < maxCount) return { type: 'partial', label: $t('Dalinai užimta'), color: 'secondary' }
  if (currentCount === maxCount) return { type: 'full', label: $t('Pilnai užimta'), color: 'default' }
  return { type: 'over', label: $t('Viršija limitą'), color: 'destructive' }
}

// Sort duties: empty/partial first, then by name
const sortedDuties = computed(() => {
  return [...filteredDuties.value].sort((a, b) => {
    const aStatus = getDutyStatus(a)
    const bStatus = getDutyStatus(b)
    
    // Priority: empty > partial > unknown > full > over
    const priority: Record<string, number> = { empty: 0, partial: 1, unknown: 2, full: 3, over: 4 }
    const aPriority = priority[aStatus.type] ?? 5
    const bPriority = priority[bStatus.type] ?? 5
    
    if (aPriority !== bPriority) return aPriority - bPriority
    
    // Then by name
    return (a.name?.toString() || '').localeCompare(b.name?.toString() || '')
  })
})

// Handle duty selection
const selectDuty = (duty: any) => {
  wizard.setDuty(duty as App.Entities.Duty)
  wizard.nextStep()
}

const clearSearch = () => {
  searchQuery.value = ''
}

const toggleType = (typeId: string) => {
  const idx = newDuty.types.indexOf(typeId)
  if (idx === -1) {
    newDuty.types.push(typeId)
  } else {
    newDuty.types.splice(idx, 1)
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Selected institution header -->
    <div class="flex items-center gap-3 p-3 rounded-lg bg-primary/5 border border-primary/10">
      <div class="h-10 w-10 rounded-lg bg-primary/10 flex items-center justify-center">
        <Icons.INSTITUTION class="h-5 w-5 text-primary" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs text-muted-foreground">{{ $t('Pasirinkta institucija') }}</p>
        <p class="font-medium text-foreground truncate">{{ wizard.state.institution?.name }}</p>
      </div>
      <Button variant="ghost" size="sm" @click="wizard.previousStep()">
        <Edit3 class="h-4 w-4 mr-1" />
        {{ $t('Keisti') }}
      </Button>
    </div>

    <!-- Create Duty Form -->
    <Card v-if="showCreateForm" class="border-primary/30">
      <CardHeader class="pb-4">
        <CardTitle class="text-lg flex items-center gap-2">
          <Plus class="h-5 w-5" />
          {{ $t('Nauja pareigybė') }}
        </CardTitle>
        <CardDescription>
          {{ $t('Sukurti naują pareigybę institucijai') }}: {{ wizard.state.institution?.name }}
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <!-- Duty Name -->
        <div class="space-y-2">
          <Label class="text-sm font-medium">{{ $t('Pavadinimas') }} *</Label>
          <Input 
            v-model="newDuty.name.lt" 
            :placeholder="$t('Pareigybės pavadinimas lietuvių kalba')"
            :class="{ 'border-destructive': createErrors['name.lt'] }"
          />
          <p v-if="createErrors['name.lt']" class="text-sm text-destructive">
            {{ createErrors['name.lt'] }}
          </p>
        </div>

        <!-- Places to Occupy -->
        <div class="space-y-2">
          <Label class="text-sm font-medium">{{ $t('Kiek vietų') }}</Label>
          <Input 
            v-model.number="newDuty.places_to_occupy" 
            type="number"
            min="1"
            :placeholder="$t('1')"
          />
          <p class="text-xs text-muted-foreground">{{ $t('Kiek žmonių gali užimti šią pareigybę') }}</p>
        </div>

        <!-- Contacts Grouping -->
        <div class="space-y-2">
          <Label class="text-sm font-medium">{{ $t('Kontaktų grupavimas') }} *</Label>
          <Select v-model="newDuty.contacts_grouping">
            <SelectTrigger :class="{ 'border-destructive': createErrors['contacts_grouping'] }">
              <SelectValue :placeholder="$t('Pasirinkite grupavimą')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">{{ $t('Jokio') }}</SelectItem>
              <SelectItem value="study_program">{{ $t('Pagal studijų programą') }}</SelectItem>
              <SelectItem value="tenant">{{ $t('Pagal padalinį') }}</SelectItem>
            </SelectContent>
          </Select>
          <p v-if="createErrors['contacts_grouping']" class="text-sm text-destructive">
            {{ createErrors['contacts_grouping'] }}
          </p>
        </div>

        <!-- Types (if available) -->
        <div v-if="dutyTypes.length > 0" class="space-y-2">
          <Label class="text-sm font-medium">{{ $t('Tipai') }}</Label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="type in dutyTypes"
              :key="type.id"
              type="button"
              class="inline-flex items-center px-3 py-1.5 rounded-full text-sm border transition-colors"
              :class="newDuty.types.includes(String(type.id))
                ? 'bg-primary text-primary-foreground border-primary'
                : 'bg-background hover:bg-accent border-border'"
              @click="toggleType(String(type.id))"
            >
              {{ type.title }}
            </button>
          </div>
        </div>

        <!-- Extra fields (collapsible) -->
        <Collapsible v-model:open="showExtraFields">
          <CollapsibleTrigger class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors">
            <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': showExtraFields }" />
            {{ $t('Papildomi laukai') }}
          </CollapsibleTrigger>
          <CollapsibleContent class="pt-4 space-y-4">
            <!-- English Name -->
            <div class="space-y-2">
              <Label class="text-sm font-medium">{{ $t('Pavadinimas anglų kalba') }}</Label>
              <Input 
                v-model="newDuty.name.en" 
                :placeholder="$t('Duty name in English')"
              />
            </div>

            <!-- Email -->
            <div class="space-y-2">
              <Label class="text-sm font-medium">{{ $t('El. paštas') }}</Label>
              <Input 
                v-model="newDuty.email" 
                type="email"
                :placeholder="$t('pareigybe@vusa.lt')"
                :class="{ 'border-destructive': createErrors['email'] }"
              />
              <p v-if="createErrors['email']" class="text-sm text-destructive">
                {{ createErrors['email'] }}
              </p>
            </div>

            <!-- Description -->
            <div class="space-y-2">
              <Label class="text-sm font-medium">{{ $t('Aprašymas') }}</Label>
              <Input 
                v-model="newDuty.description.lt" 
                :placeholder="$t('Pareigybės aprašymas')"
              />
            </div>
          </CollapsibleContent>
        </Collapsible>

        <!-- Form actions -->
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="outline" @click="cancelCreate" :disabled="isCreating">
            {{ $t('Atšaukti') }}
          </Button>
          <Button @click="createDuty" :disabled="isCreating || !newDuty.name.lt">
            <Plus v-if="!isCreating" class="h-4 w-4 mr-1" />
            {{ isCreating ? $t('Kuriama...') : $t('Sukurti pareigybę') }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Regular duty selection view -->
    <template v-else>
      <!-- Search input -->
      <div class="relative">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          v-model="searchQuery"
          :placeholder="$t('Ieškoti pareigybės...')"
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

      <!-- Create duty button -->
      <Button 
        v-if="canCreateDuty"
        variant="outline" 
        class="w-full border-dashed"
        @click="showCreateForm = true"
      >
        <Plus class="h-4 w-4 mr-2" />
        {{ $t('Sukurti naują pareigybę') }}
      </Button>

      <!-- Duties list -->
      <ScrollArea class="h-[380px] pr-4">
        <div class="space-y-2">
          <button
            v-for="duty in sortedDuties"
            :key="duty.id"
            type="button"
            class="group w-full text-left rounded-xl border border-border/50 bg-card p-4 transition-all duration-200 hover:shadow-md hover:border-primary/30 hover:bg-accent/20"
            @click="selectDuty(duty)"
          >
            <div class="flex items-start gap-4">
              <!-- Icon -->
              <div 
                class="h-11 w-11 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                :class="{
                  'bg-zinc-100 dark:bg-zinc-800': getDutyStatus(duty).type === 'unknown',
                  'bg-amber-50 dark:bg-amber-950/30': getDutyStatus(duty).type === 'empty',
                  'bg-blue-50 dark:bg-blue-950/30': getDutyStatus(duty).type === 'partial',
                  'bg-green-50 dark:bg-green-950/30': getDutyStatus(duty).type === 'full',
                  'bg-red-50 dark:bg-red-950/30': getDutyStatus(duty).type === 'over'
                }"
              >
                <Icons.DUTY 
                  class="h-5 w-5"
                  :class="{
                    'text-zinc-500': getDutyStatus(duty).type === 'unknown',
                    'text-amber-600 dark:text-amber-400': getDutyStatus(duty).type === 'empty',
                    'text-blue-600 dark:text-blue-400': getDutyStatus(duty).type === 'partial',
                    'text-green-600 dark:text-green-400': getDutyStatus(duty).type === 'full',
                    'text-red-600 dark:text-red-400': getDutyStatus(duty).type === 'over'
                  }"
                />
              </div>
              
              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <p class="font-medium text-foreground group-hover:text-primary transition-colors">
                    {{ duty.name }}
                  </p>
                  <Badge :variant="getDutyStatus(duty).color as any" class="shrink-0 text-xs">
                    {{ getDutyStatus(duty).label }}
                  </Badge>
                </div>
                
                <!-- Email -->
                <p v-if="duty.email" class="text-sm text-muted-foreground mt-1 truncate">
                  {{ duty.email }}
                </p>
                
                <!-- Capacity indicator -->
                <div class="flex items-center gap-3 mt-2">
                  <div class="flex items-center gap-1.5">
                    <Users class="h-3.5 w-3.5 text-muted-foreground" />
                    <span class="text-sm font-medium">
                      {{ duty.current_users?.length || 0 }}
                      <span class="text-muted-foreground font-normal">/ {{ duty.places_to_occupy || '?' }}</span>
                    </span>
                  </div>
                  
                  <!-- Capacity bar -->
                  <div class="flex-1 h-1.5 bg-muted rounded-full overflow-hidden max-w-24">
                    <div 
                      class="h-full rounded-full transition-all"
                      :class="{
                        'bg-zinc-400': getDutyStatus(duty).type === 'unknown',
                        'bg-amber-500': getDutyStatus(duty).type === 'empty',
                        'bg-blue-500': getDutyStatus(duty).type === 'partial',
                        'bg-green-500': getDutyStatus(duty).type === 'full',
                        'bg-red-500': getDutyStatus(duty).type === 'over'
                      }"
                      :style="{ 
                        width: duty.places_to_occupy 
                          ? `${Math.min(100, ((duty.current_users?.length || 0) / duty.places_to_occupy) * 100)}%`
                          : '0%'
                      }"
                    />
                  </div>
                  
                  <!-- Current users preview -->
                  <div v-if="duty.current_users?.length" class="flex -space-x-2">
                    <div 
                      v-for="(user, idx) in duty.current_users.slice(0, 3)" 
                      :key="user.id"
                      class="h-6 w-6 rounded-full border-2 border-background bg-muted flex items-center justify-center text-xs font-medium overflow-hidden"
                      :title="user.name"
                    >
                      <img 
                        v-if="user.profile_photo_path"
                        :src="user.profile_photo_path"
                        :alt="user.name"
                        class="h-full w-full object-cover"
                      />
                      <span v-else>{{ user.name?.charAt(0) }}</span>
                    </div>
                    <div 
                      v-if="duty.current_users.length > 3"
                      class="h-6 w-6 rounded-full border-2 border-background bg-muted flex items-center justify-center text-xs font-medium"
                    >
                      +{{ duty.current_users.length - 3 }}
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Arrow -->
              <ChevronRight class="h-5 w-5 text-muted-foreground group-hover:text-primary group-hover:translate-x-1 transition-all shrink-0 mt-1" />
            </div>
          </button>

          <!-- Empty state -->
          <div v-if="sortedDuties.length === 0" class="text-center py-12">
            <div class="h-16 w-16 rounded-full bg-muted mx-auto flex items-center justify-center mb-4">
              <Icons.DUTY class="h-8 w-8 text-muted-foreground" />
            </div>
            <p v-if="searchQuery" class="text-muted-foreground">{{ $t('Nerasta pareigybių pagal paiešką') }}</p>
            <p v-else class="text-muted-foreground">{{ $t('Ši institucija neturi pareigybių') }}</p>
            <Button v-if="searchQuery" variant="link" class="mt-2" @click="clearSearch">
              {{ $t('Išvalyti paiešką') }}
            </Button>
            <Button v-else-if="canCreateDuty" variant="default" class="mt-4" @click="showCreateForm = true">
              <Plus class="h-4 w-4 mr-2" />
              {{ $t('Sukurti pirmą pareigybę') }}
            </Button>
          </div>
        </div>
      </ScrollArea>
    </template>
  </div>
</template>
