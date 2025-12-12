import { ref, reactive, computed, watch, readonly } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'

export interface UserChange {
  userId: string
  userName: string
  userEmail: string
  userPhoto?: string | null
  action: 'add' | 'remove'
  startDate?: string
  endDate?: string
  studyProgramId?: string | null
  studyProgramName?: string | null
  isNewUser?: boolean
}

export interface NewUserData {
  name: string
  email: string
  phone?: string
  profile_photo_path?: string | null
}

export interface DutyUserWizardState {
  currentStep: number
  maxCompletedStep: number
  
  // Step 1: Institution
  institution?: App.Entities.Institution
  
  // Step 2: Duty
  duty?: App.Entities.Duty
  
  // Step 3: User changes
  userChanges: UserChange[]
  newUsersToCreate: NewUserData[]
  
  // Capacity update
  newPlacesToOccupy?: number | null
  
  // Loading and errors
  loading: {
    submission: boolean
    institutions: boolean
    duties: boolean
    users: boolean
  }
  errors: Record<string, string[]>
  
  // Validation per step
  validation: {
    institution: boolean
    duty: boolean
    users: boolean
    canProceed: boolean
  }
}

export interface UseDutyUserWizardOptions {
  preSelectedInstitution?: App.Entities.Institution
  preSelectedDuty?: App.Entities.Duty
  onSuccess?: (result: any) => void
  onError?: (errors: any) => void
}

/**
 * Calculate the suggested end date based on July 1 rule:
 * - If more than 3 months until next July 1, suggest next July 1
 * - If less than 3 months, suggest July 1 of the following year
 */
export function getSuggestedEndDate(): string {
  const now = new Date()
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth() // 0-indexed, so July = 6
  
  // Calculate next July 1
  let nextJuly1Year = currentYear
  if (currentMonth >= 6) {
    // We're past July, so next July 1 is next year
    nextJuly1Year = currentYear + 1
  }
  
  const nextJuly1 = new Date(nextJuly1Year, 6, 1) // July 1
  
  // Calculate months until next July 1
  const monthsUntil = (nextJuly1.getFullYear() - now.getFullYear()) * 12 + (nextJuly1.getMonth() - now.getMonth())
  
  // If less than 3 months, push to following year
  if (monthsUntil < 3) {
    nextJuly1Year += 1
  }
  
  // Format as YYYY-MM-DD
  return `${nextJuly1Year}-07-01`
}

/**
 * Format date for display in localized format
 */
export function formatDateForDisplay(dateString: string): string {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

/**
 * Get today's date as YYYY-MM-DD
 */
export function getTodayDate(): string {
  const now = new Date()
  return now.toISOString().split('T')[0] ?? ''
}

export function useDutyUserWizard(options: UseDutyUserWizardOptions = {}) {
  const state = reactive<DutyUserWizardState>({
    currentStep: options.preSelectedDuty ? 3 : options.preSelectedInstitution ? 2 : 1,
    maxCompletedStep: options.preSelectedDuty ? 2 : options.preSelectedInstitution ? 1 : 0,
    
    institution: options.preSelectedInstitution,
    duty: options.preSelectedDuty,
    
    userChanges: [],
    newUsersToCreate: [],
    newPlacesToOccupy: undefined,
    
    loading: {
      submission: false,
      institutions: false,
      duties: false,
      users: false
    },
    errors: {},
    validation: {
      institution: !!options.preSelectedInstitution,
      duty: !!options.preSelectedDuty,
      users: false,
      canProceed: false
    }
  })

  // Computed properties
  const totalSteps = computed(() => 4)
  
  const currentStepValid = computed(() => {
    switch (state.currentStep) {
      case 1: return state.validation.institution
      case 2: return state.validation.duty
      case 3: return state.validation.users
      case 4: return true // Review step
      default: return false
    }
  })

  const canProceedToNext = computed(() => {
    return currentStepValid.value && !state.loading.submission
  })

  const canGoToPrevious = computed(() => {
    return state.currentStep > 1 && !state.loading.submission
  })

  // Capacity calculations
  const currentUserCount = computed(() => {
    return state.duty?.current_users?.length || 0
  })

  const projectedUserCount = computed(() => {
    const additions = state.userChanges.filter(c => c.action === 'add').length
    const removals = state.userChanges.filter(c => c.action === 'remove').length
    return currentUserCount.value + additions - removals
  })

  const targetCapacity = computed(() => {
    return state.newPlacesToOccupy ?? state.duty?.places_to_occupy ?? 0
  })

  const capacityMismatch = computed(() => {
    return projectedUserCount.value !== targetCapacity.value
  })

  const hasChanges = computed(() => {
    return state.userChanges.length > 0 || 
           state.newUsersToCreate.length > 0 ||
           state.newPlacesToOccupy !== undefined
  })

  // Step management
  const goToStep = (step: number) => {
    if (step < 1 || step > totalSteps.value) return
    if (step > state.maxCompletedStep + 1) return
    
    state.currentStep = step
    clearStepErrors(step)
  }

  const nextStep = () => {
    if (!canProceedToNext.value) return
    
    if (state.currentStep > state.maxCompletedStep) {
      state.maxCompletedStep = state.currentStep
    }
    
    if (state.currentStep < totalSteps.value) {
      state.currentStep++
    } else {
      submitChanges()
    }
  }

  const previousStep = () => {
    if (canGoToPrevious.value) {
      state.currentStep--
    }
  }

  // Validation methods
  const validateInstitution = (institutionId: string): boolean => {
    clearStepErrors(1)
    
    if (!institutionId) {
      setStepError(1, 'institution_id', [$t('Institucija yra privaloma')])
      state.validation.institution = false
      return false
    }
    
    state.validation.institution = true
    return true
  }

  const validateDuty = (dutyId: string): boolean => {
    clearStepErrors(2)
    
    if (!dutyId) {
      setStepError(2, 'duty_id', [$t('Pareigybė yra privaloma')])
      state.validation.duty = false
      return false
    }
    
    state.validation.duty = true
    return true
  }

  const validateUsers = (): boolean => {
    clearStepErrors(3)
    
    // Check that all additions have start dates
    const missingStartDates = state.userChanges
      .filter(c => c.action === 'add' && !c.startDate)
    
    if (missingStartDates.length > 0) {
      setStepError(3, 'start_date', [$t('Visi nauji nariai turi turėti pradžios datą')])
      state.validation.users = false
      return false
    }
    
    // Check that all removals have end dates
    const missingEndDates = state.userChanges
      .filter(c => c.action === 'remove' && !c.endDate)
    
    if (missingEndDates.length > 0) {
      setStepError(3, 'end_date', [$t('Visi šalinami nariai turi turėti pabaigos datą')])
      state.validation.users = false
      return false
    }
    
    // Check that end_date is not before start_date for additions
    const invalidAdditionDates = state.userChanges
      .filter(c => c.action === 'add' && c.startDate && c.endDate)
      .filter(c => new Date(c.endDate!) < new Date(c.startDate!))
    
    if (invalidAdditionDates.length > 0) {
      setStepError(3, 'date_range', [$t('Pabaigos data negali būti ankstesnė nei pradžios data')])
      state.validation.users = false
      return false
    }
    
    // Check new users have required fields
    const invalidNewUsers = state.newUsersToCreate.filter(u => !u.name || !u.email)
    if (invalidNewUsers.length > 0) {
      setStepError(3, 'new_users', [$t('Nauji naudotojai turi turėti vardą ir el. paštą')])
      state.validation.users = false
      return false
    }
    
    state.validation.users = true
    return true
  }

  // Error management
  const clearStepErrors = (step: number) => {
    const stepKeys: Record<number, string[]> = {
      1: ['institution_id'],
      2: ['duty_id'],
      3: ['start_date', 'end_date', 'date_range', 'new_users', 'users'],
      4: []
    }
    
    const keysToReset = stepKeys[step] || []
    keysToReset.forEach(key => {
      delete state.errors[key]
    })
  }

  const setStepError = (step: number, field: string, errors: string[]) => {
    state.errors[field] = errors
  }

  const clearAllErrors = () => {
    state.errors = {}
  }

  // Data update methods
  const setInstitution = (institution: App.Entities.Institution) => {
    // Reset duty and users if institution changes
    if (state.institution?.id !== institution.id) {
      state.duty = undefined
      state.userChanges = []
      state.newUsersToCreate = []
      state.newPlacesToOccupy = undefined
      state.validation.duty = false
      state.validation.users = false
    }
    
    state.institution = institution
    
    if (validateInstitution(institution.id)) {
      if (state.maxCompletedStep < 1) {
        state.maxCompletedStep = 1
      }
    }
  }

  const setDuty = (duty: App.Entities.Duty) => {
    // Reset users if duty changes
    if (state.duty?.id !== duty.id) {
      state.userChanges = []
      state.newUsersToCreate = []
      state.newPlacesToOccupy = undefined
      state.validation.users = false
    }
    
    state.duty = duty
    
    if (validateDuty(duty.id)) {
      if (state.maxCompletedStep < 2) {
        state.maxCompletedStep = 2
      }
    }
  }

  // User change methods
  const addUserToAdd = (user: App.Entities.User, options: { startDate?: string, endDate?: string, studyProgramId?: string | null } = {}) => {
    // Check if already in changes
    const existingIndex = state.userChanges.findIndex(c => c.userId === user.id)
    if (existingIndex !== -1) {
      // Update existing
      const existing = state.userChanges[existingIndex]!
      existing.action = 'add'
      existing.startDate = options.startDate || getTodayDate()
      existing.endDate = options.endDate || getSuggestedEndDate()
      existing.studyProgramId = options.studyProgramId
    } else {
      state.userChanges.push({
        userId: user.id,
        userName: user.name,
        userEmail: user.email,
        userPhoto: user.profile_photo_path,
        action: 'add',
        startDate: options.startDate || getTodayDate(),
        endDate: options.endDate || getSuggestedEndDate(),
        studyProgramId: options.studyProgramId,
        isNewUser: false
      })
    }
    
    validateUsers()
  }

  const addUserToRemove = (user: App.Entities.User, endDate?: string) => {
    // Check if already in changes
    const existingIndex = state.userChanges.findIndex(c => c.userId === user.id)
    if (existingIndex !== -1) {
      const existing = state.userChanges[existingIndex]!
      existing.action = 'remove'
      existing.endDate = endDate || getTodayDate()
    } else {
      state.userChanges.push({
        userId: user.id,
        userName: user.name,
        userEmail: user.email,
        userPhoto: user.profile_photo_path,
        action: 'remove',
        endDate: endDate || getTodayDate(),
        isNewUser: false
      })
    }
    
    validateUsers()
  }

  const removeUserChange = (userId: string) => {
    const index = state.userChanges.findIndex(c => c.userId === userId)
    if (index !== -1) {
      state.userChanges.splice(index, 1)
    }
    validateUsers()
  }

  const updateUserChange = (userId: string, updates: Partial<UserChange>) => {
    const index = state.userChanges.findIndex(c => c.userId === userId)
    if (index !== -1) {
      const existing = state.userChanges[index]!
      Object.assign(existing, updates)
    }
    validateUsers()
  }

  const addNewUserToCreate = (userData: NewUserData) => {
    state.newUsersToCreate.push(userData)
  }

  const removeNewUserToCreate = (index: number) => {
    state.newUsersToCreate.splice(index, 1)
  }

  const updateNewUserToCreate = (index: number, updates: Partial<NewUserData>) => {
    if (state.newUsersToCreate[index]) {
      state.newUsersToCreate[index] = { ...state.newUsersToCreate[index], ...updates }
    }
  }

  // Batch operations
  const setAllAddedUsersStartDate = (date: string) => {
    state.userChanges
      .filter(c => c.action === 'add')
      .forEach(c => { c.startDate = date })
  }

  const setAllAddedUsersEndDate = (date: string) => {
    state.userChanges
      .filter(c => c.action === 'add')
      .forEach(c => { c.endDate = date })
  }

  const setAllRemovedUsersEndDate = (date: string) => {
    state.userChanges
      .filter(c => c.action === 'remove')
      .forEach(c => { c.endDate = date })
  }

  // Capacity update
  const setNewPlacesToOccupy = (places: number | null) => {
    state.newPlacesToOccupy = places
  }

  // Submission
  const submitChanges = async () => {
    if (!state.duty) return
    
    state.loading.submission = true
    clearAllErrors()
    
    try {
      const payload = {
        duty_id: state.duty.id,
        user_changes: state.userChanges.map(c => ({
          user_id: c.userId,
          action: c.action,
          start_date: c.startDate,
          end_date: c.endDate,
          study_program_id: c.studyProgramId
        })),
        new_users: state.newUsersToCreate,
        places_to_occupy: state.newPlacesToOccupy
      }
      
      router.post(route('duties.batchUpdateUsers', state.duty.id), payload, {
        preserveScroll: true,
        onSuccess: (page) => {
          options.onSuccess?.(page)
        },
        onError: (errors) => {
          // Convert Inertia errors to our format
          const errorRecord: Record<string, string[]> = {}
          Object.entries(errors).forEach(([key, value]) => {
            errorRecord[key] = Array.isArray(value) ? value : [value as string]
          })
          state.errors = errorRecord
          options.onError?.(errors)
        },
        onFinish: () => {
          state.loading.submission = false
        }
      })
    } catch (error) {
      state.loading.submission = false
      console.error('Failed to submit changes:', error)
    }
  }

  // Reset wizard
  const reset = () => {
    state.currentStep = 1
    state.maxCompletedStep = 0
    state.institution = undefined
    state.duty = undefined
    state.userChanges = []
    state.newUsersToCreate = []
    state.newPlacesToOccupy = undefined
    state.errors = {}
    state.validation = {
      institution: false,
      duty: false,
      users: false,
      canProceed: false
    }
  }

  return {
    state: readonly(state),
    
    // Computed
    totalSteps,
    currentStepValid,
    canProceedToNext,
    canGoToPrevious,
    currentUserCount,
    projectedUserCount,
    targetCapacity,
    capacityMismatch,
    hasChanges,
    
    // Step navigation
    goToStep,
    nextStep,
    previousStep,
    
    // Data setters
    setInstitution,
    setDuty,
    
    // User changes
    addUserToAdd,
    addUserToRemove,
    removeUserChange,
    updateUserChange,
    addNewUserToCreate,
    removeNewUserToCreate,
    updateNewUserToCreate,
    
    // Batch operations
    setAllAddedUsersStartDate,
    setAllAddedUsersEndDate,
    setAllRemovedUsersEndDate,
    
    // Capacity
    setNewPlacesToOccupy,
    
    // Actions
    submitChanges,
    reset,
    validateUsers,
    
    // Helpers
    getSuggestedEndDate,
    getTodayDate,
    formatDateForDisplay
  }
}
