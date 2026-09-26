<template>
  <Head>
    <title>{{ $t('Pareigybių atnaujinimas') }}</title>
  </Head>

  <!-- In the admin shell this replaces the navigation chrome (useShellFocus); elsewhere it renders inline. -->
  <Teleport defer :to="`#${SHELL_FORM_BAR_ID}`" :disabled="!shellFocus">
    <div class="flex min-w-0 flex-1 items-center gap-2 md:gap-3" data-testid="duty-wizard-bar">
      <button
        type="button"
        class="group inline-flex shrink-0 items-center gap-2 text-sm font-bold text-foreground transition-colors hover:text-brand"
        @click="goBack"
      >
        <span class="flex size-9 items-center justify-center border border-border transition-colors group-hover:border-brand pointer-coarse:size-11">
          <ArrowLeft class="size-4" />
        </span>
        <span :class="shellFocus ? 'sr-only lg:not-sr-only' : ''">{{ wizard.state.currentStep === 1 ? $t('Grįžti') : $t('Atgal') }}</span>
      </button>

      <div class="min-w-0 flex-1 border-l border-border pl-3">
        <div class="flex items-center gap-2 min-w-0">
          <p class="truncate text-sm font-bold text-foreground">
            {{ $t('Pareigybių atnaujinimas') }}
          </p>
          <span class="hidden sm:inline-block text-xs text-muted-foreground font-mono tabular-nums">
            {{ wizard.state.currentStep }} / {{ wizard.totalSteps.value }}
          </span>
        </div>
        <p class="hidden md:block truncate text-xs text-muted-foreground">
          {{ currentStep?.title }}
        </p>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        <Button
          v-if="wizard.state.currentStep === wizard.totalSteps.value"
          variant="brand"
          size="sm"
          class="hidden sm:inline-flex pointer-coarse:h-11"
          :disabled="!wizard.canProceedToNext || wizard.state.loading.submission"
          @click="wizard.nextStep"
        >
          <Loader2 v-if="wizard.state.loading.submission" class="size-4 animate-spin" />
          <UserCheck v-else class="size-4" />
          {{ $t('Patvirtinti') }}
        </Button>
        <Button
          v-else
          variant="brand"
          size="sm"
          class="hidden sm:inline-flex pointer-coarse:h-11"
          :disabled="!wizard.canProceedToNext"
          @click="wizard.nextStep"
        >
          <span>{{ $t('Toliau') }}</span>
          <ArrowRight class="size-4 ml-1" />
        </Button>
      </div>
    </div>
  </Teleport>

  <div class="w-full pb-20 sm:pb-0" data-slot="duty-wizard-page">
    <!-- Header band: title + entity mark -->
    <header class="space-y-1.5 border-b border-border pt-2 sm:pt-3 pb-3 sm:pb-4">
      <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
        <EntityTypeMark type="duty" size="sm" class="text-xs font-bold uppercase tracking-[0.2em]" />
        <span class="h-3 border-l border-border" aria-hidden="true" />
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand">
          {{ $t('shell.workspaces.organizacija.title') }}
        </p>
      </div>

      <h1 class="u-display text-balance text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
        {{ $t('Pareigybių atnaujinimas') }}
      </h1>
    </header>

    <!-- Mobile Stepper Strip (< lg) -->
    <nav aria-label="Žingsniai" class="lg:hidden my-3 border border-border bg-card p-2.5">
      <div class="flex items-center justify-between text-xs font-medium text-foreground mb-2">
        <div class="flex items-center gap-2">
          <span class="font-mono tabular-nums text-muted-foreground">{{ $t('Žingsnis') }} {{ wizard.state.currentStep }} / {{ wizard.totalSteps.value }}</span>
          <span class="text-border">·</span>
          <span class="font-bold text-foreground">{{ currentStep?.title }}</span>
        </div>
        <span v-if="currentStep?.hint" class="truncate max-w-[140px] text-brand text-[11px]">
          {{ currentStep.hint }}
        </span>
      </div>
      <div class="h-1.5 w-full bg-muted overflow-hidden">
        <div
          class="h-full bg-brand-fill transition-all duration-300"
          :style="{ width: `${(wizard.state.currentStep / wizard.totalSteps.value) * 100}%` }"
        />
      </div>
      <div class="mt-2.5 flex items-center justify-between gap-1">
        <button
          v-for="step in steps"
          :key="step.id"
          type="button"
          :disabled="step.id > wizard.state.maxCompletedStep + 1"
          class="flex-1 py-1 text-center text-[11px] font-medium border-b-2 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
          :class="[
            step.active
              ? 'border-brand-fill text-foreground font-bold'
              : step.completed
                ? 'border-status-success text-status-success'
                : 'border-transparent text-muted-foreground'
          ]"
          @click="handleStepClick(step.id)"
        >
          {{ step.id }}. {{ step.title }}
        </button>
      </div>
    </nav>

    <!-- Main grid layout -->
    <div class="my-3 lg:my-5 lg:grid lg:grid-cols-12 lg:gap-6">
      <!-- Desktop Sidebar (lg+) -->
      <aside class="hidden lg:block lg:col-span-4 xl:col-span-3">
        <div class="lg:sticky lg:top-24 space-y-4">
          <section class="border border-border bg-card">
            <h2 class="border-b border-border px-4 py-3 text-xs font-bold uppercase tracking-wider text-muted-foreground">
              {{ $t('Žingsniai') }}
            </h2>
            <nav class="flex flex-col divide-y divide-border">
              <button
                v-for="step in steps"
                :key="step.id"
                type="button"
                :disabled="step.id > wizard.state.maxCompletedStep + 1"
                class="group relative flex items-start gap-3.5 px-4 py-3.5 text-left transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40"
                :class="{
                  'bg-muted/50 border-l-2 border-l-brand': step.active,
                  'border-l-2 border-l-transparent': !step.active,
                }"
                @click="handleStepClick(step.id)"
              >
                <div
                  class="flex size-7 shrink-0 items-center justify-center border text-xs font-bold transition-colors"
                  :class="{
                    'border-brand-fill bg-brand-fill text-brand-foreground': step.active,
                    'border-status-success bg-status-success-surface text-status-success': step.completed && !step.active,
                    'border-border bg-background text-muted-foreground group-hover:border-foreground/40': !step.active && !step.completed
                  }"
                >
                  <CheckCircle2 v-if="step.completed && !step.active" class="size-4" />
                  <span v-else>{{ step.id }}</span>
                </div>

                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm font-semibold leading-none transition-colors"
                    :class="step.active ? 'text-foreground' : 'text-foreground/90'"
                  >
                    {{ step.title }}
                  </p>
                  <p class="text-xs text-muted-foreground mt-1 line-clamp-2">
                    {{ step.description }}
                  </p>
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

          <!-- Contextual tip -->
          <section class="border border-status-attention-border bg-status-attention-surface/30 p-4">
            <div class="flex items-start gap-3">
              <div class="flex size-7 shrink-0 items-center justify-center border border-status-attention-border text-status-attention bg-status-attention-surface">
                <Lightbulb class="size-3.5" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-wider text-status-attention">
                  {{ $t('Patarimas') }}
                </p>
                <p class="mt-1 text-xs text-foreground leading-relaxed">
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

      <!-- Main Step Content -->
      <main class="lg:col-span-8 xl:col-span-9 space-y-4">
        <section class="border border-border bg-card">
          <div class="border-b border-border px-4 py-2.5 sm:px-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="flex size-7 shrink-0 items-center justify-center border border-brand-fill bg-brand-fill text-brand-foreground">
                <component :is="currentStep?.icon" class="size-3.5" />
              </div>
              <div class="min-w-0">
                <h2 class="text-sm font-bold text-foreground truncate">
                  {{ currentStep?.title }}
                </h2>
                <p class="text-[11px] text-muted-foreground truncate">
                  {{ currentStep?.description }}
                </p>
              </div>
            </div>
          </div>

          <div class="p-3.5 sm:p-5">
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

          <!-- Desktop Bottom Action Bar -->
          <div class="border-t border-border px-4 py-2.5 sm:px-6 hidden sm:flex items-center justify-between">
            <Button
              variant="ghost"
              :disabled="wizard.state.loading.submission"
              @click="goBack"
            >
              <ArrowLeft class="size-4 mr-1.5" />
              {{ wizard.state.currentStep === 1 ? $t('Atšaukti') : $t('Atgal') }}
            </Button>

            <div class="flex items-center gap-3">
              <span
                v-if="wizard.state.currentStep === 3 && !wizard.hasChanges"
                class="text-xs text-muted-foreground"
              >
                {{ $t('Pridėkite bent vieną pakeitimą') }}
              </span>

              <Button
                variant="brand"
                :disabled="!wizard.canProceedToNext || wizard.state.loading.submission"
                class="min-w-32"
                @click="wizard.nextStep"
              >
                <template v-if="wizard.state.loading.submission">
                  <Loader2 class="size-4 mr-1.5 animate-spin" />
                  <span>{{ $t('Vykdoma...') }}</span>
                </template>
                <template v-else-if="wizard.state.currentStep === wizard.totalSteps.value">
                  <UserCheck class="size-4 mr-1.5" />
                  {{ $t('Patvirtinti') }}
                </template>
                <template v-else>
                  {{ $t('Toliau') }}
                  <ArrowRight class="size-4 ml-1.5" />
                </template>
              </Button>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Mobile Sticky Footer Bar (< sm) -->
    <div class="sm:hidden fixed inset-x-0 bottom-0 z-30 border-t border-border bg-background/95 backdrop-blur-sm p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom,0px))] flex items-center justify-between gap-3 shadow-none">
      <Button
        variant="outline"
        size="default"
        class="flex-1 pointer-coarse:h-11"
        :disabled="wizard.state.loading.submission"
        @click="goBack"
      >
        <ArrowLeft class="size-4 mr-1.5" />
        {{ wizard.state.currentStep === 1 ? $t('Atšaukti') : $t('Atgal') }}
      </Button>
      <Button
        variant="brand"
        size="default"
        class="flex-1 pointer-coarse:h-11"
        :disabled="!wizard.canProceedToNext || wizard.state.loading.submission"
        @click="wizard.nextStep"
      >
        <template v-if="wizard.state.loading.submission">
          <Loader2 class="size-4 mr-1.5 animate-spin" />
          {{ $t('Vykdoma...') }}
        </template>
        <template v-else-if="wizard.state.currentStep === wizard.totalSteps.value">
          <UserCheck class="size-4 mr-1.5" />
          {{ $t('Patvirtinti') }}
        </template>
        <template v-else>
          {{ $t('Toliau') }}
          <ArrowRight class="size-4 ml-1.5" />
        </template>
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, provide, ref, onUnmounted } from 'vue';
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
  Lightbulb,
  Loader2,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

import { useDutyUserWizard, getSuggestedEndDate, formatDateForDisplay } from '@/Composables/useDutyUserWizard';
import { SHELL_FORM_BAR_ID, useShellFocus } from '@/Composables/useShellFocus';
import { Button } from '@/Components/ui/button';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';

// Step components
import Step1InstitutionSelect from '@/Components/DutyUserWizard/Step1InstitutionSelect.vue';
import Step2DutySelect from '@/Components/DutyUserWizard/Step2DutySelect.vue';
import Step3UserAssignment from '@/Components/DutyUserWizard/Step3UserAssignment.vue';
import Step4Review from '@/Components/DutyUserWizard/Step4Review.vue';
import { DutyIcon } from '@/Components/icons';

const props = defineProps<{
  institutions: App.Entities.Institution[];
  assignableTenants: App.Entities.Tenant[];
  institutionTypes: App.Entities.Type[];
}>();

// Shell focus mode: swaps shell navigation for the wizard's editor bar
const shellFocus = useShellFocus();
if (shellFocus) {
  onUnmounted(shellFocus.enter());
}

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

// Initialize wizard
const wizard = useDutyUserWizard({
  onSuccess: () => {
    toast.success($t('Pakeitimai sėkmingai išsaugoti'));
  },
  onError: (errors) => {
    const errorMessage = Object.values(errors).flat()[0] || $t('Nepavyko išsaugoti pakeitimų');
    toast.error(String(errorMessage));
  },
});

// Provide wizard to child components
provide('dutyUserWizard', wizard);
provide('studyPrograms', studyPrograms);
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
  transform: translateX(8px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
</style>
