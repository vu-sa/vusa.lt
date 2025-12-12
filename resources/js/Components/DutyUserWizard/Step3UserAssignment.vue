<script setup lang="ts">
import { ref, computed, inject, watch } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { usePage } from '@inertiajs/vue3'
import { 
  Search, 
  UserPlus, 
  UserMinus,
  X,
  Edit3,
  Calendar,
  ChevronDown,
  Plus,
  GraduationCap,
  Check,
  AlertTriangle
} from 'lucide-vue-next'

import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { Separator } from '@/Components/ui/separator'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible'
import { Label } from '@/Components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import Icons from '@/Types/Icons/regular'

import { getSuggestedEndDate, getTodayDate, formatDateForDisplay } from '@/Composables/useDutyUserWizard'
import type { useDutyUserWizard, UserChange, NewUserData } from '@/Composables/useDutyUserWizard'

const wizard = inject<ReturnType<typeof useDutyUserWizard>>('dutyUserWizard')!
const studyPrograms = inject<App.Entities.StudyProgram[]>('studyPrograms', [])
const allUsers = inject<App.Entities.User[]>('allUsers', [])

const page = usePage()
const auth = page.props.auth as any

// Search for users
const userSearchQuery = ref('')
const showUserSearch = ref(false)

// Inline user creation
const showNewUserForm = ref(false)
const newUser = ref<NewUserData>({
  name: '',
  email: '',
  phone: ''
})

// Batch date setting
const batchStartDate = ref(getTodayDate())
const batchEndDate = ref(getSuggestedEndDate())
const showBatchDateSetter = ref(false)

// Study program collapsible (for curator duties)
const showStudyProgramSection = ref(false)

// Current users from selected duty
const currentUsers = computed(() => {
  return wizard.state.duty?.current_users || []
})

// Filter out users that are already in the duty
const availableUsers = computed(() => {
  const currentUserIds = currentUsers.value.map(u => u.id)
  const addedUserIds = wizard.state.userChanges
    .filter(c => c.action === 'add')
    .map(c => c.userId)
  
  const excludeIds = new Set([...currentUserIds, ...addedUserIds])
  
  let filtered = allUsers.filter(u => !excludeIds.has(u.id))
  
  if (userSearchQuery.value) {
    const query = userSearchQuery.value.toLowerCase()
    filtered = filtered.filter(u => 
      u.name?.toLowerCase().includes(query) ||
      u.email?.toLowerCase().includes(query)
    )
  }
  
  return filtered.slice(0, 20) // Limit for performance
})

// Users being added
const usersToAdd = computed(() => {
  return wizard.state.userChanges.filter(c => c.action === 'add')
})

// Users being removed
const usersToRemove = computed(() => {
  return wizard.state.userChanges.filter(c => c.action === 'remove')
})

// Current users not being removed
const remainingCurrentUsers = computed(() => {
  const removeIds = new Set(usersToRemove.value.map(c => c.userId))
  return currentUsers.value.filter(u => !removeIds.has(u.id))
})

// Check if duty might need study programs (curator-like duties)
const mightNeedStudyProgram = computed(() => {
  const dutyName = wizard.state.duty?.name?.toString().toLowerCase() || ''
  return dutyName.includes('kurator') || 
         dutyName.includes('studij') || 
         dutyName.includes('atstovas') ||
         (wizard.state.duty?.places_to_occupy || 0) > 5
})

// Handle adding a user
const handleAddUser = (user: App.Entities.User) => {
  wizard.addUserToAdd(user, {
    startDate: batchStartDate.value,
    endDate: batchEndDate.value
  })
  userSearchQuery.value = ''
  showUserSearch.value = false
}

// Handle removing a user
const handleRemoveUser = (user: any) => {
  wizard.addUserToRemove(user as App.Entities.User, getTodayDate())
}

// Cancel removal
const cancelRemoval = (userId: string) => {
  wizard.removeUserChange(userId)
}

// Cancel addition
const cancelAddition = (userId: string) => {
  wizard.removeUserChange(userId)
}

// Update date for a change
const updateChangeDate = (userId: string, field: 'startDate' | 'endDate', value: string) => {
  wizard.updateUserChange(userId, { [field]: value })
}

// Update study program
const updateStudyProgram = (userId: string, programId: string | null) => {
  const program = studyPrograms.find(p => p.id === programId)
  wizard.updateUserChange(userId, { 
    studyProgramId: programId,
    studyProgramName: program?.name?.toString() || null
  })
}

// Apply batch dates
const applyBatchStartDate = () => {
  wizard.setAllAddedUsersStartDate(batchStartDate.value)
}

const applyBatchEndDate = () => {
  wizard.setAllAddedUsersEndDate(batchEndDate.value)
}

// Create new user inline
const createNewUser = () => {
  if (!newUser.value.name || !newUser.value.email) return
  
  wizard.addNewUserToCreate({ ...newUser.value })
  
  // Also add to userChanges with a temporary ID
  const tempId = `new-${Date.now()}`
  // Create temporary user object to pass to addUserToAdd
  const tempUser = {
    id: tempId,
    name: newUser.value.name,
    email: newUser.value.email,
    profile_photo_path: null
  } as App.Entities.User
  
  wizard.addUserToAdd(tempUser, {
    startDate: batchStartDate.value,
    endDate: batchEndDate.value
  })
  
  // Mark as new user
  wizard.updateUserChange(tempId, { isNewUser: true })
  
  // Reset form
  newUser.value = { name: '', email: '', phone: '' }
  showNewUserForm.value = false
}

// Check permissions
const canCreateUser = computed(() => auth?.can?.create?.user)

// Check for date validation errors
const dateValidationError = computed(() => {
  return wizard.state.errors['date_range']?.[0] || null
})

// Check if a specific user has date error (end < start)
const hasDateError = (change: UserChange) => {
  if (change.action !== 'add' || !change.startDate || !change.endDate) return false
  return new Date(change.endDate) < new Date(change.startDate)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Selected duty header -->
    <div class="flex items-center gap-3 p-3 rounded-lg bg-primary/5 border border-primary/10">
      <div class="h-10 w-10 rounded-lg bg-primary/10 flex items-center justify-center">
        <Icons.DUTY class="h-5 w-5 text-primary" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs text-muted-foreground">{{ $t('Pasirinkta pareigybė') }}</p>
        <p class="font-medium text-foreground truncate">{{ wizard.state.duty?.name }}</p>
        <p class="text-xs text-muted-foreground">
          {{ wizard.state.institution?.name }}
        </p>
      </div>
      <div class="text-right">
        <p class="text-xs text-muted-foreground">{{ $t('Dabartinis vietų skaičius') }}</p>
        <p class="font-medium">
          {{ currentUsers.length }} / {{ wizard.state.duty?.places_to_occupy || '?' }}
        </p>
      </div>
      <Button variant="ghost" size="sm" @click="wizard.previousStep()">
        <Edit3 class="h-4 w-4 mr-1" />
        {{ $t('Keisti') }}
      </Button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Left: Current members -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-medium flex items-center gap-2">
            <UserMinus class="h-4 w-4 text-muted-foreground" />
            {{ $t('Dabartiniai nariai') }}
            <Badge variant="secondary">{{ currentUsers.length }}</Badge>
          </h3>
        </div>

        <ScrollArea class="h-[280px] pr-2">
          <div class="space-y-2">
            <!-- Remaining users -->
            <div
              v-for="user in remainingCurrentUsers"
              :key="user.id"
              class="flex items-center gap-3 p-3 rounded-lg border bg-card hover:bg-accent/30 transition-colors group"
            >
              <div class="h-9 w-9 rounded-full bg-muted flex items-center justify-center overflow-hidden shrink-0">
                <img 
                  v-if="user.profile_photo_path"
                  :src="user.profile_photo_path"
                  :alt="user.name"
                  class="h-full w-full object-cover"
                />
                <span v-else class="text-sm font-medium">{{ user.name?.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-sm truncate">{{ user.name }}</p>
                <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
              </div>
              <Button 
                variant="ghost" 
                size="sm"
                class="opacity-0 group-hover:opacity-100 transition-opacity text-destructive hover:text-destructive hover:bg-destructive/10"
                @click="handleRemoveUser(user)"
              >
                <UserMinus class="h-4 w-4" />
              </Button>
            </div>

            <!-- Users being removed -->
            <div
              v-for="change in usersToRemove"
              :key="change.userId"
              class="flex items-center gap-3 p-3 rounded-lg border border-vusa-red/30 bg-vusa-red/5"
            >
              <div class="h-9 w-9 rounded-full bg-vusa-red/10 flex items-center justify-center overflow-hidden shrink-0">
                <img 
                  v-if="change.userPhoto"
                  :src="change.userPhoto"
                  :alt="change.userName"
                  class="h-full w-full object-cover opacity-50"
                />
                <span v-else class="text-sm font-medium text-vusa-red">{{ change.userName?.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-sm text-vusa-red line-through truncate">{{ change.userName }}</p>
                <div class="flex items-center gap-2 mt-1">
                  <Label class="text-xs text-muted-foreground">{{ $t('Pabaigos data:') }}</Label>
                  <Input 
                    type="date"
                    :model-value="change.endDate"
                    class="h-7 w-32 text-xs"
                    @update:model-value="(v) => updateChangeDate(change.userId, 'endDate', String(v))"
                  />
                </div>
              </div>
              <Button 
                variant="ghost" 
                size="icon"
                class="h-8 w-8"
                @click="cancelRemoval(change.userId)"
              >
                <X class="h-4 w-4" />
              </Button>
            </div>

            <!-- Empty state -->
            <div v-if="currentUsers.length === 0" class="text-center py-8 text-muted-foreground">
              <p class="text-sm">{{ $t('Ši pareigybė neturi narių') }}</p>
            </div>
          </div>
        </ScrollArea>
      </div>

      <!-- Right: Add new members -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-medium flex items-center gap-2">
            <UserPlus class="h-4 w-4 text-muted-foreground" />
            {{ $t('Pridėti narius') }}
            <Badge v-if="usersToAdd.length > 0" variant="default">{{ usersToAdd.length }}</Badge>
          </h3>
        </div>

        <!-- User search -->
        <div class="space-y-1">
          <div class="relative h-10">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
            <Input
              v-model="userSearchQuery"
              :placeholder="$t('Įveskite vardą arba el. paštą...')"
              class="pl-10 h-10"
              @focus="showUserSearch = true"
            />
            
            <!-- Search dropdown -->
            <div 
              v-if="showUserSearch && userSearchQuery.length >= 2"
              class="absolute top-full left-0 right-0 z-50 mt-1 max-h-48 overflow-y-auto rounded-lg border bg-popover shadow-lg"
            >
              <button
                v-for="user in availableUsers"
                :key="user.id"
                type="button"
                class="w-full flex items-center gap-3 p-3 text-left hover:bg-accent transition-colors"
                @click="handleAddUser(user)"
              >
                <div class="h-8 w-8 rounded-full bg-muted flex items-center justify-center overflow-hidden shrink-0">
                  <img 
                    v-if="user.profile_photo_path"
                    :src="user.profile_photo_path"
                    :alt="user.name"
                    class="h-full w-full object-cover"
                  />
                  <span v-else class="text-xs font-medium">{{ user.name?.charAt(0) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-sm truncate">{{ user.name }}</p>
                  <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                </div>
                <Plus class="h-4 w-4 text-primary shrink-0" />
              </button>
              
              <div v-if="availableUsers.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                {{ $t('Nerasta naudotojų') }}
              </div>
            </div>
          </div>
          <p v-if="!userSearchQuery && !showUserSearch" class="text-xs text-muted-foreground">
            {{ $t('Pradėkite rašyti, kad rastumėte naudotoją') }}
          </p>
        </div>

        <!-- Batch date setting -->
        <Collapsible v-model:open="showBatchDateSetter">
          <CollapsibleTrigger as-child>
            <Button variant="outline" size="sm" class="w-full justify-between">
              <span class="flex items-center gap-2">
                <Calendar class="h-4 w-4" />
                {{ $t('Datos nustatymai') }}
              </span>
              <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': showBatchDateSetter }" />
            </Button>
          </CollapsibleTrigger>
          <CollapsibleContent class="pt-3 space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <Label class="text-xs">{{ $t('Pradžios data') }}</Label>
                <div class="flex gap-1 mt-1">
                  <Input v-model="batchStartDate" type="date" class="h-8 text-xs" />
                  <Button size="sm" variant="secondary" class="h-8 px-2" @click="applyBatchStartDate">
                    <Check class="h-3 w-3" />
                  </Button>
                </div>
              </div>
              <div>
                <Label class="text-xs">{{ $t('Pabaigos data') }}</Label>
                <div class="flex gap-1 mt-1">
                  <Input v-model="batchEndDate" type="date" class="h-8 text-xs" />
                  <Button size="sm" variant="secondary" class="h-8 px-2" @click="applyBatchEndDate">
                    <Check class="h-3 w-3" />
                  </Button>
                </div>
              </div>
            </div>
            <p class="text-xs text-muted-foreground">
              {{ $t('Siūloma pabaigos data: ') }} 
              <strong>{{ formatDateForDisplay(getSuggestedEndDate()) }}</strong>
              {{ $t('(liepos 1 d.)') }}
            </p>
          </CollapsibleContent>
        </Collapsible>

        <!-- Users being added -->
        <ScrollArea class="h-[200px] pr-2">
          <div class="space-y-2">
            <div
              v-for="change in usersToAdd"
              :key="change.userId"
              class="p-3 rounded-lg border border-green-200 dark:border-green-900 bg-green-50/50 dark:bg-green-950/20"
            >
              <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center overflow-hidden shrink-0">
                  <img 
                    v-if="change.userPhoto"
                    :src="change.userPhoto"
                    :alt="change.userName"
                    class="h-full w-full object-cover"
                  />
                  <span v-else class="text-sm font-medium text-green-700 dark:text-green-300">{{ change.userName?.charAt(0) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-sm truncate text-green-800 dark:text-green-200">
                    {{ change.userName }}
                    <Badge v-if="change.isNewUser" variant="outline" class="ml-1 text-[10px]">{{ $t('Naujas') }}</Badge>
                  </p>
                  <p class="text-xs text-green-600 dark:text-green-400 truncate">{{ change.userEmail }}</p>
                </div>
                <Button 
                  variant="ghost" 
                  size="icon"
                  class="h-8 w-8"
                  @click="cancelAddition(change.userId)"
                >
                  <X class="h-4 w-4" />
                </Button>
              </div>
              
              <!-- Date inputs -->
              <div class="mt-3 grid grid-cols-2 gap-2">
                <div>
                  <Label class="text-xs text-muted-foreground">{{ $t('Pradžia') }}</Label>
                  <Input 
                    type="date"
                    :model-value="change.startDate"
                    class="h-7 text-xs mt-0.5"
                    :class="{ 'border-vusa-red focus-visible:ring-vusa-red': hasDateError(change) }"
                    @update:model-value="(v) => updateChangeDate(change.userId, 'startDate', String(v))"
                  />
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">{{ $t('Pabaiga') }}</Label>
                  <Input 
                    type="date"
                    :model-value="change.endDate"
                    class="h-7 text-xs mt-0.5"
                    :class="{ 'border-vusa-red focus-visible:ring-vusa-red': hasDateError(change) }"
                    @update:model-value="(v) => updateChangeDate(change.userId, 'endDate', String(v))"
                  />
                </div>
              </div>
              <!-- Date error message -->
              <p v-if="hasDateError(change)" class="text-xs text-vusa-red mt-1 flex items-center gap-1">
                <AlertTriangle class="h-3 w-3" />
                {{ $t('Pabaigos data negali būti ankstesnė nei pradžios data') }}
              </p>

              <!-- Study program (collapsible for relevant duties) -->
              <Collapsible v-if="mightNeedStudyProgram" class="mt-2">
                <CollapsibleTrigger as-child>
                  <Button variant="ghost" size="sm" class="h-6 px-2 text-xs w-full justify-start">
                    <GraduationCap class="h-3 w-3 mr-1" />
                    {{ change.studyProgramName || $t('Pridėti studijų programą') }}
                    <ChevronDown class="h-3 w-3 ml-auto" />
                  </Button>
                </CollapsibleTrigger>
                <CollapsibleContent class="pt-2">
                  <Select 
                    :model-value="change.studyProgramId || undefined"
                    @update:model-value="(v) => updateStudyProgram(change.userId, v ? String(v) : null)"
                  >
                    <SelectTrigger class="h-8 text-xs">
                      <SelectValue :placeholder="$t('Pasirinkti programą...')" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="program in studyPrograms" 
                        :key="program.id" 
                        :value="program.id"
                      >
                        {{ program.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </CollapsibleContent>
              </Collapsible>
            </div>

            <!-- Empty state -->
            <div v-if="usersToAdd.length === 0" class="text-center py-8 text-muted-foreground border-2 border-dashed rounded-lg">
              <UserPlus class="h-8 w-8 mx-auto mb-2 opacity-50" />
              <p class="text-sm">{{ $t('Ieškokite ir pridėkite narius') }}</p>
            </div>
          </div>
        </ScrollArea>

        <!-- Create new user -->
        <div v-if="canCreateUser">
          <Separator class="my-3" />
          <Collapsible v-model:open="showNewUserForm">
            <CollapsibleTrigger as-child>
              <Button variant="outline" size="sm" class="w-full">
                <Plus class="h-4 w-4 mr-2" />
                {{ $t('Sukurti naują naudotoją') }}
              </Button>
            </CollapsibleTrigger>
            <CollapsibleContent class="pt-3 space-y-3">
              <div class="p-3 border rounded-lg bg-muted/30 space-y-3">
                <div>
                  <Label class="text-xs">{{ $t('Vardas ir pavardė') }} *</Label>
                  <Input v-model="newUser.name" :placeholder="$t('Jonas Jonaitis')" class="h-8 mt-1" />
                </div>
                <div>
                  <Label class="text-xs">{{ $t('El. paštas') }} *</Label>
                  <Input v-model="newUser.email" type="email" placeholder="jonas@stud.vu.lt" class="h-8 mt-1" />
                </div>
                <div>
                  <Label class="text-xs">{{ $t('Telefonas') }}</Label>
                  <Input v-model="newUser.phone" placeholder="+370 600 00000" class="h-8 mt-1" />
                </div>
                <div class="flex gap-2">
                  <Button 
                    size="sm" 
                    :disabled="!newUser.name || !newUser.email"
                    @click="createNewUser"
                  >
                    <Check class="h-4 w-4 mr-1" />
                    {{ $t('Pridėti') }}
                  </Button>
                  <Button variant="ghost" size="sm" @click="showNewUserForm = false">
                    {{ $t('Atšaukti') }}
                  </Button>
                </div>
              </div>
            </CollapsibleContent>
          </Collapsible>
        </div>
      </div>
    </div>

    <!-- Summary footer -->
    <div v-if="wizard.hasChanges" class="flex items-center justify-between p-3 rounded-lg bg-muted/50 border">
      <div class="flex items-center gap-4 text-sm">
        <span v-if="usersToAdd.length > 0" class="text-green-600 dark:text-green-400 flex items-center gap-1">
          <UserPlus class="h-4 w-4" />
          +{{ usersToAdd.length }}
        </span>
        <span v-if="usersToRemove.length > 0" class="text-vusa-red flex items-center gap-1">
          <UserMinus class="h-4 w-4" />
          -{{ usersToRemove.length }}
        </span>
        <Separator orientation="vertical" class="h-4" />
        <span class="text-muted-foreground">
          {{ $t('Naujas vietų skaičius:') }} 
          <strong>{{ wizard.projectedUserCount.value }}</strong>
          <span v-if="wizard.capacityMismatch.value" class="text-vusa-yellow-dark dark:text-vusa-yellow ml-1">
            <AlertTriangle class="h-3 w-3 inline" />
          </span>
        </span>
      </div>
    </div>
  </div>
</template>
