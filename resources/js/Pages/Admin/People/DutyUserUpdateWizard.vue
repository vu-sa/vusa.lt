<script setup lang="ts">
import { computed, provide, ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { 
  ArrowLeft, 
  ArrowRight, 
  Building2, 
  Users, 
  UserCheck, 
  ClipboardCheck,
  CheckCircle2,
  Sparkles,
  Lightbulb
} from 'lucide-vue-next'

import { useDutyUserWizard, getSuggestedEndDate, formatDateForDisplay } from '@/Composables/useDutyUserWizard'
import { useSidebar } from '@/Components/ui/sidebar'
import { toast } from 'vue-sonner'
import { Button } from '@/Components/ui/button'
import { Card, CardContent } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'
import Icons from '@/Types/Icons/regular'

// Step components
import Step1InstitutionSelect from '@/Components/DutyUserWizard/Step1InstitutionSelect.vue'
import Step2DutySelect from '@/Components/DutyUserWizard/Step2DutySelect.vue'
import Step3UserAssignment from '@/Components/DutyUserWizard/Step3UserAssignment.vue'
import Step4Review from '@/Components/DutyUserWizard/Step4Review.vue'

const props = defineProps<{
  institutions: App.Entities.Institution[]
  studyPrograms: App.Entities.StudyProgram[]
  users: App.Entities.User[]
  // For inline creation
  assignableTenants: App.Entities.Tenant[]
  institutionTypes: App.Entities.Type[]
  dutyTypes: App.Entities.Type[]
}>()

// Reactive institutions list (can be updated when new institution is created)
const institutionsList = ref([...props.institutions])

// Function to add newly created institution to the list
const addInstitution = (institution: App.Entities.Institution) => {
  institutionsList.value = [institution, ...institutionsList.value]
}

// Close sidebar on mount to give more room
const { setOpen, isMobile } = useSidebar()
onMounted(() => {
  if (!isMobile.value) {
    setOpen(false)
  }
})

// Initialize wizard - redirect to duty.show on success, expand sidebar
const wizard = useDutyUserWizard({
  onSuccess: () => {
    // Expand sidebar back when wizard is completed
    if (!isMobile.value) {
      setOpen(true)
    }
    // Show success toast
    toast.success($t('Pakeitimai sėkmingai išsaugoti'))
  },
  onError: (errors) => {
    // Show error toast
    const errorMessage = Object.values(errors).flat()[0] || $t('Nepavyko išsaugoti pakeitimų')
    toast.error(String(errorMessage))
  }
})

// Provide wizard to child components
provide('dutyUserWizard', wizard)
provide('studyPrograms', props.studyPrograms)
provide('allUsers', props.users)
// For inline creation
provide('assignableTenants', props.assignableTenants)
provide('institutionTypes', props.institutionTypes)
provide('dutyTypes', props.dutyTypes)
provide('addInstitution', addInstitution)

// Step definitions with icons and descriptions
const steps = computed(() => [
  {
    id: 1,
    title: $t('Institucija'),
    description: $t('Pasirinkite instituciją, kuriai norite atnaujinti pareigybes'),
    icon: Building2,
    completed: wizard.state.maxCompletedStep >= 1,
    active: wizard.state.currentStep === 1,
    hint: wizard.state.institution?.name
  },
  {
    id: 2,
    title: $t('Pareigybė'),
    description: $t('Pasirinkite pareigybę, kurios narius norite keisti'),
    icon: Icons.DUTY,
    completed: wizard.state.maxCompletedStep >= 2,
    active: wizard.state.currentStep === 2,
    hint: wizard.state.duty?.name
  },
  {
    id: 3,
    title: $t('Nariai'),
    description: $t('Pridėkite arba pašalinkite narius, nustatykite datas'),
    icon: Users,
    completed: wizard.state.maxCompletedStep >= 3 && wizard.hasChanges,
    active: wizard.state.currentStep === 3,
    hint: wizard.hasChanges 
      ? `${wizard.state.userChanges.filter(c => c.action === 'add').length} ${$t('pridedama')}, ${wizard.state.userChanges.filter(c => c.action === 'remove').length} ${$t('šalinama')}`
      : undefined
  },
  {
    id: 4,
    title: $t('Peržiūra'),
    description: $t('Peržiūrėkite ir patvirtinkite pakeitimus'),
    icon: ClipboardCheck,
    completed: false,
    active: wizard.state.currentStep === 4,
    hint: undefined
  }
])

const currentStep = computed(() => steps.value.find(s => s.id === wizard.state.currentStep))

// Navigation helpers
const handleStepClick = (stepId: number) => {
  wizard.goToStep(stepId)
}

const goBack = () => {
  if (wizard.state.currentStep === 1) {
    router.visit(route('administration'))
  } else {
    wizard.previousStep()
  }
}
</script>

<template>
  <Head>
    <title>{{ $t('Pareigybių atnaujinimas') }}</title>
  </Head>

  <div class="-m-6 min-h-[calc(100vh-4rem)] bg-gradient-to-br from-slate-50 via-white to-blue-50/30 dark:from-zinc-950 dark:via-zinc-900 dark:to-blue-950/20">
    <!-- Header with gradient accent -->
    <div class="border-b bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-4">
            <Button variant="ghost" size="icon" @click="goBack">
              <ArrowLeft class="h-4 w-4" />
            </Button>
            <div class="flex items-center gap-3">
              <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                <Sparkles class="h-4 w-4 text-primary" />
              </div>
              <div>
                <h1 class="text-lg font-semibold text-foreground">{{ $t('Pareigybių atnaujinimas') }}</h1>
                <p class="text-xs text-muted-foreground">{{ $t('Greitas narių valdymas') }}</p>
              </div>
            </div>
          </div>
          
          <!-- Progress indicator -->
          <div class="hidden sm:flex items-center gap-2">
            <span class="text-sm text-muted-foreground">{{ $t('Žingsnis') }}</span>
            <Badge variant="secondary" class="font-mono">
              {{ wizard.state.currentStep }} / {{ wizard.totalSteps.value }}
            </Badge>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left sidebar: Stepper -->
        <aside class="lg:col-span-4 xl:col-span-3">
          <div class="lg:sticky lg:top-24">
            <Card class="overflow-hidden border-0 shadow-lg shadow-primary/5">
              <!-- Card header with gradient -->
              <div class="bg-gradient-to-r from-primary/10 via-primary/5 to-transparent p-4 border-b">
                <h2 class="font-medium text-foreground">{{ $t('Žingsniai') }}</h2>
              </div>
              
              <CardContent class="p-0">
                <nav class="flex flex-col">
                  <button
                    v-for="(step, index) in steps"
                    :key="step.id"
                    type="button"
                    :disabled="step.id > wizard.state.maxCompletedStep + 1"
                    class="group relative flex items-start gap-4 p-4 text-left transition-all duration-200 hover:bg-accent/50 disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="{ 
                      'bg-primary/5': step.active,
                      'border-l-2 border-l-primary': step.active,
                      'border-l-2 border-l-transparent': !step.active
                    }"
                    @click="handleStepClick(step.id)"
                  >
                    <!-- Step indicator -->
                    <div 
                      class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-300"
                      :class="{
                        'bg-primary border-primary text-primary-foreground shadow-lg shadow-primary/25': step.active,
                        'bg-green-500 border-green-500 text-white': step.completed && !step.active,
                        'bg-background border-border text-muted-foreground group-hover:border-primary/50': !step.active && !step.completed
                      }"
                    >
                      <CheckCircle2 v-if="step.completed && !step.active" class="h-5 w-5" />
                      <component :is="step.icon" v-else class="h-5 w-5" />
                    </div>
                    
                    <!-- Connector line -->
                    <div 
                      v-if="index < steps.length - 1"
                      class="absolute left-[2.25rem] top-14 h-[calc(100%-2rem)] w-0.5 -translate-x-1/2"
                      :class="step.completed ? 'bg-green-500' : 'bg-border'"
                    />
                    
                    <!-- Step content -->
                    <div class="flex-1 min-w-0 pt-1">
                      <p 
                        class="text-sm font-medium transition-colors"
                        :class="step.active ? 'text-primary' : 'text-foreground'"
                      >
                        {{ step.title }}
                      </p>
                      <p class="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                        {{ step.description }}
                      </p>
                      <!-- Selection hint -->
                      <p 
                        v-if="step.hint && step.completed"
                        class="text-xs text-primary mt-1 font-medium truncate"
                      >
                        {{ step.hint }}
                      </p>
                    </div>
                  </button>
                </nav>
              </CardContent>
            </Card>

            <!-- Helpful tips -->
            <Card class="mt-4 border-0 shadow-md bg-gradient-to-br from-amber-50 to-orange-50/50 dark:from-amber-950/30 dark:to-orange-950/20">
              <CardContent class="p-4">
                <div class="flex items-start gap-3">
                  <div class="h-8 w-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center shrink-0">
                    <Lightbulb class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-amber-900 dark:text-amber-100">{{ $t('Patarimas') }}</p>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                      <template v-if="wizard.state.currentStep === 1">
                        {{ $t('Pasirinkite instituciją, kurioje norite atnaujinti pareigybes. Galite ieškoti pagal pavadinimą.') }}
                      </template>
                      <template v-else-if="wizard.state.currentStep === 2">
                        {{ $t('Pasirinkite pareigybę. Skaičius prie pareigybės rodo kiek vietų užimta.') }}
                      </template>
                      <template v-else-if="wizard.state.currentStep === 3">
                        {{ $t('Galite pridėti kelis narius vienu metu. Siūloma pabaigos data: ') }}
                        <strong>{{ formatDateForDisplay(getSuggestedEndDate()) }}</strong>
                      </template>
                      <template v-else>
                        {{ $t('Peržiūrėkite visus pakeitimus prieš patvirtindami.') }}
                      </template>
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </aside>

        <!-- Right: Step content -->
        <main class="lg:col-span-8 xl:col-span-9">
          <Card class="border-0 shadow-lg shadow-black/5 overflow-hidden">
            <!-- Step header -->
            <div class="bg-gradient-to-r from-slate-50 to-white dark:from-zinc-800 dark:to-zinc-900 border-b px-6 py-4">
              <div class="flex items-center gap-3">
                <div 
                  class="h-10 w-10 rounded-xl flex items-center justify-center bg-gradient-to-br from-vusa-red to-vusa-red-dark text-white"
                >
                  <component :is="currentStep?.icon" class="h-5 w-5" />
                </div>
                <div>
                  <h2 class="text-lg font-semibold text-foreground">{{ currentStep?.title }}</h2>
                  <p class="text-sm text-muted-foreground">{{ currentStep?.description }}</p>
                </div>
              </div>
            </div>

            <!-- Step content -->
            <CardContent class="p-6">
              <Transition name="fade" mode="out-in">
                <Step1InstitutionSelect 
                  v-if="wizard.state.currentStep === 1"
                  :institutions="institutionsList"
                />
                <Step2DutySelect 
                  v-else-if="wizard.state.currentStep === 2"
                />
                <Step3UserAssignment 
                  v-else-if="wizard.state.currentStep === 3"
                />
                <Step4Review 
                  v-else-if="wizard.state.currentStep === 4"
                />
              </Transition>
            </CardContent>

            <!-- Footer with navigation -->
            <div class="border-t bg-slate-50/50 dark:bg-zinc-800/50 px-6 py-4">
              <div class="flex items-center justify-between">
                <Button 
                  variant="ghost" 
                  :disabled="wizard.state.loading.submission"
                  @click="goBack"
                >
                  <ArrowLeft class="h-4 w-4 mr-2" />
                  {{ wizard.state.currentStep === 1 ? $t('Atšaukti') : $t('Atgal') }}
                </Button>

                <div class="flex items-center gap-3">
                  <!-- Skip hint for step 3 -->
                  <span 
                    v-if="wizard.state.currentStep === 3 && !wizard.hasChanges"
                    class="text-sm text-muted-foreground"
                  >
                    {{ $t('Pridėkite bent vieną pakeitimą') }}
                  </span>

                  <Button 
                    :disabled="!wizard.canProceedToNext || wizard.state.loading.submission"
                    class="min-w-32"
                    :class="{
                      'bg-gradient-to-r from-primary to-primary/90': wizard.canProceedToNext
                    }"
                    @click="wizard.nextStep"
                  >
                    <template v-if="wizard.state.loading.submission">
                      <span class="animate-pulse">{{ $t('Vykdoma...') }}</span>
                    </template>
                    <template v-else-if="wizard.state.currentStep === wizard.totalSteps.value">
                      <UserCheck class="h-4 w-4 mr-2" />
                      {{ $t('Patvirtinti') }}
                    </template>
                    <template v-else>
                      {{ $t('Toliau') }}
                      <ArrowRight class="h-4 w-4 ml-2" />
                    </template>
                  </Button>
                </div>
              </div>
            </div>
          </Card>
        </main>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateX(10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}
</style>
