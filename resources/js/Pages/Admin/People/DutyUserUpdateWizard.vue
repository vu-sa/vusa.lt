<template>
  <Head>
    <title>{{ $t('Pareigybių atnaujinimas') }}</title>
  </Head>

  <div class="-m-6 min-h-[calc(100vh-4rem)] bg-secondary">
    <div class="sticky top-0 z-10 border-b border-border bg-background">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-4">
            <Button variant="ghost" size="icon" @click="goBack">
              <ArrowLeft class="h-4 w-4" />
            </Button>
            <div class="flex items-center gap-3">
              <div class="flex size-8 items-center justify-center border border-border bg-muted">
                <Sparkles class="h-4 w-4 text-primary" />
              </div>
              <div>
                <h1 class="text-lg font-semibold text-foreground">
                  {{ $t('Pareigybių atnaujinimas') }}
                </h1>
                <p class="text-xs text-muted-foreground">
                  {{ $t('Greitas narių valdymas') }}
                </p>
              </div>
            </div>
          </div>

          <div class="hidden sm:flex items-center gap-2">
            <span class="text-sm text-muted-foreground">{{ $t('Žingsnis') }}</span>
            <span class="font-mono text-sm tabular-nums">{{ wizard.state.currentStep }} / {{ wizard.totalSteps.value }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <aside class="lg:col-span-4 xl:col-span-3">
          <div class="lg:sticky lg:top-24">
            <section class="border-y border-border">
              <h2 class="border-b border-border px-4 py-3 text-sm font-medium text-foreground">{{ $t('Žingsniai') }}</h2>
              <nav class="flex flex-col">
                  <button
                    v-for="(step, index) in steps"
                    :key="step.id"
                    type="button"
                    :disabled="step.id > wizard.state.maxCompletedStep + 1"
                    class="group relative flex items-start gap-4 border-l-2 border-transparent px-4 py-3 text-left transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50"
                    :class="{
                      'bg-muted': step.active,
                      'border-l-2 border-l-primary': step.active,
                      'border-l-2 border-l-transparent': !step.active
                    }"
                    @click="handleStepClick(step.id)"
                  >
                    <div
                      class="relative z-10 flex size-10 shrink-0 items-center justify-center border transition-colors"
                      :class="{
                        'border-brand-fill bg-brand-fill text-brand-foreground': step.active,
                        'border-status-success bg-status-success-surface text-status-success': step.completed && !step.active,
                        'border-border bg-background text-muted-foreground group-hover:border-brand-fill': !step.active && !step.completed
                      }"
                    >
                      <CheckCircle2 v-if="step.completed && !step.active" class="h-5 w-5" />
                      <component :is="step.icon" v-else class="h-5 w-5" />
                    </div>

                    <div
                      v-if="index < steps.length - 1"
                      class="absolute left-9 top-14 h-[calc(100%-2rem)] w-px"
                      :class="step.completed ? 'bg-status-success' : 'bg-border'"
                    />

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
                        class="mt-1 truncate text-xs font-medium text-brand"
                      >
                        {{ step.hint }}
                      </p>
                    </div>
                  </button>
              </nav>
            </section>

            <section class="mt-4 border-y border-status-attention-border bg-status-attention-surface px-4 py-3">
                <div class="flex items-start gap-3">
                  <div class="flex size-8 shrink-0 items-center justify-center border border-status-attention-border text-status-attention">
                    <Lightbulb class="size-4" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-status-attention">
                      {{ $t('Patarimas') }}
                    </p>
                    <p class="mt-1 text-xs text-foreground">
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
            </section>
          </div>
        </aside>

        <main class="lg:col-span-8 xl:col-span-9">
          <section class="border-y border-border bg-background">
            <div class="border-b border-border px-4 py-4 sm:px-6">
              <div class="flex items-center gap-3">
                <div
                  class="flex size-10 items-center justify-center border border-brand-fill bg-brand-fill text-brand-foreground"
                >
                  <component :is="currentStep?.icon" class="h-5 w-5" />
                </div>
                <div>
                  <h2 class="text-lg font-semibold text-foreground">
                    {{ currentStep?.title }}
                  </h2>
                  <p class="text-sm text-muted-foreground">
                    {{ currentStep?.description }}
                  </p>
                </div>
              </div>
            </div>

            <div class="p-4 sm:p-6">
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
            </div>

            <div class="border-t border-border px-4 py-4 sm:px-6">
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
                  <span
                    v-if="wizard.state.currentStep === 3 && !wizard.hasChanges"
                    class="text-sm text-muted-foreground"
                  >
                    {{ $t('Pridėkite bent vieną pakeitimą') }}
                  </span>

                  <Button
                    :disabled="!wizard.canProceedToNext || wizard.state.loading.submission"
                    class="min-w-32"
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
          </section>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, provide, ref, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  ArrowLeft,
  ArrowRight,
  Building2,
  Users,
  UserCheck,
  ClipboardCheck,
  CheckCircle2,
  Sparkles,
  Lightbulb,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

import { useDutyUserWizard, getSuggestedEndDate, formatDateForDisplay } from '@/Composables/useDutyUserWizard';
import { Button } from '@/Components/ui/button';

// Step components
import Step1InstitutionSelect from '@/Components/DutyUserWizard/Step1InstitutionSelect.vue';
import Step2DutySelect from '@/Components/DutyUserWizard/Step2DutySelect.vue';
import Step3UserAssignment from '@/Components/DutyUserWizard/Step3UserAssignment.vue';
import Step4Review from '@/Components/DutyUserWizard/Step4Review.vue';
import { DutyIcon } from '@/Components/icons';

const props = defineProps<{
  institutions: App.Entities.Institution[];
  // For inline creation
  assignableTenants: App.Entities.Tenant[];
  institutionTypes: App.Entities.Type[];
}>();

// Get reactive page props for lazy-loaded data
const page = usePage();

// Computed refs for lazy-loaded data (reactive when data arrives via router.reload)
const studyPrograms = computed(() => (page.props.studyPrograms as App.Entities.StudyProgram[] | undefined) ?? []);
const dutyTypes = computed(() => (page.props.dutyTypes as App.Entities.Type[] | undefined) ?? []);

// Reactive institutions list (can be updated when new institution is created)
const institutionsList = ref([...props.institutions]);

// Function to add newly created institution to the list
const addInstitution = (institution: App.Entities.Institution) => {
  institutionsList.value = [institution, ...institutionsList.value];
};

// Initialize wizard - redirect to duty.show on success
const wizard = useDutyUserWizard({
  onSuccess: () => {
    // Show success toast
    toast.success($t('Pakeitimai sėkmingai išsaugoti'));
  },
  onError: (errors) => {
    // Show error toast
    const errorMessage = Object.values(errors).flat()[0] || $t('Nepavyko išsaugoti pakeitimų');
    toast.error(String(errorMessage));
  },
});

// Provide wizard to child components
provide('dutyUserWizard', wizard);
// Provide lazy-loaded data as computed refs (reactive when data arrives)
provide('studyPrograms', studyPrograms);
// For inline creation
provide('assignableTenants', props.assignableTenants);
provide('institutionTypes', props.institutionTypes);
provide('dutyTypes', dutyTypes);
provide('addInstitution', addInstitution);

// Step definitions with icons and descriptions
const steps = computed(() => [
  {
    id: 1,
    title: $t('Institucija'),
    description: $t('Pasirinkite instituciją, kuriai norite atnaujinti pareigybes'),
    icon: Building2,
    completed: wizard.state.maxCompletedStep >= 1,
    active: wizard.state.currentStep === 1,
    hint: wizard.state.institution?.name,
  },
  {
    id: 2,
    title: $t('Pareigybė'),
    description: $t('Pasirinkite pareigybę, kurios narius norite keisti'),
    icon: DutyIcon,
    completed: wizard.state.maxCompletedStep >= 2,
    active: wizard.state.currentStep === 2,
    hint: wizard.state.duty?.name,
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
      : undefined,
  },
  {
    id: 4,
    title: $t('Peržiūra'),
    description: $t('Peržiūrėkite ir patvirtinkite pakeitimus'),
    icon: ClipboardCheck,
    completed: false,
    active: wizard.state.currentStep === 4,
    hint: undefined,
  },
]);

const currentStep = computed(() => steps.value.find(s => s.id === wizard.state.currentStep));

// Navigation helpers
const handleStepClick = (stepId: number) => {
  wizard.goToStep(stepId);
};

const goBack = () => {
  if (wizard.state.currentStep === 1) {
    router.visit(route('administration'));
  }
  else {
    wizard.previousStep();
  }
};
</script>

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
