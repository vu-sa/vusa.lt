<template>
  <div class="flex flex-col gap-6">
    <FadeTransition>
      <SuggestionAlert :show-alert @alert-closed="showAlert = false">
        <p v-if="$page.props.app.locale === 'lt'">
          Viena svarbiausių veiklų atstovavime yra
          <strong>dalinimasis informacija</strong>, tada kai ji pasirodo!
        </p>
        <p v-else>
          One of the most important activities in representation is
          <strong>sharing information</strong> when it appears!
        </p>
        <p class="mt-4">
          {{ $t('Būtent') }}
          <Badge size="tiny" variant="secondary" class="mx-1">
            <Icons.MEETING class="h-3 w-3" />
            <strong>{{ $t('posėdžiai') }}</strong>
          </Badge>
          <template v-if="$page.props.app.locale === 'lt'">
            ir jų informacija yra labai svarbi – kad galėtume atstovauti studentams geriausiai, kaip tik tai įmanoma!
          </template>
          <template v-else>
            and their information is very important – so we can represent students as best as possible!
          </template>
        </p>
        <p class="mt-4">
          <strong>{{ $t('Pradėkim') }}! 💪</strong>
        </p>
      </SuggestionAlert>
    </FadeTransition>

    <Form v-slot="{ errors }" class="flex flex-col gap-6" :initial-values="initialValues.value" @submit="onSubmit">
      <!-- Quick Institution Selection as mini cards/badges -->
      <div v-if="selectedInstitution==''" class="space-y-4">
        <h3 class="text-sm font-medium text-muted-foreground">
          {{ $t('Jūsų institucijos') }}
        </h3>
        <div class="flex flex-wrap gap-2">
          <Button v-for="inst in myInstitutions" :key="`mine-${inst.value}`" type="button" size="sm" variant="secondary"
            class="h-8 max-w-full" @click="selectInstitution(inst.value)">
            <component :is="Icons.INSTITUTION" class="mr-2 h-3 w-3 shrink-0" />
            <span class="truncate max-w-48 sm:max-w-56 md:max-w-72" :title="inst.label">{{ inst.label }}</span>
          </Button>
        </div>
      </div>

      <Separator />

      <!-- Admin search inside collapsible -->
      <!-- Only show search section if user has additional admin access -->
      <div v-if="hasAdditionalInstitutions && selectedInstitution == ''" class="space-y-2">
        <h3 class="text-sm font-medium text-muted-foreground">
          {{ $t('Kitos institucijos') }}
        </h3>
        <Collapsible v-model:open="showAllInstitutions">
          <CollapsibleTrigger as-child>
            <Button type="button" variant="ghost" size="sm" class="w-full justify-between">
              {{ showAllInstitutions ? $t('Slėpti paiešką') : $t('Rodyti paiešką') }}
              <ChevronDown class="ml-1 h-3 w-3" :class="{ 'rotate-180': showAllInstitutions }" />
            </Button>
          </CollapsibleTrigger>
          <CollapsibleContent class="space-y-3 mt-2">
            <!-- Search and Select -->
            <div class="relative">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="searchQuery" :placeholder="$t('Ieškoti institucijos...')" class="pl-9"
                  @focus="showDropdown = true" />
              </div>
              <!-- Dropdown with filtered institutions -->
              <div v-if="showDropdown && filteredInstitutions.length > 0"
                class="absolute top-full left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto rounded-md border bg-popover p-1 shadow-md">
                <template v-for="institution in filteredInstitutions" :key="institution?.value">
                  <Button v-if="institution" type="button" variant="ghost" size="sm"
                    class="w-full justify-start text-left h-auto p-2"
                    @click="selectInstitutionFromSearch(institution.value, institution.label)">
                    <div class="flex items-start gap-2 w-full">
                      <component :is="Icons.INSTITUTION" class="h-4 w-4 mt-0.5 shrink-0" />
                      <div class="flex-1 min-w-0">
                        <p class="font-medium">
                          {{ institution.label }}
                        </p>
                        <p v-if="institution.context" class="text-xs text-muted-foreground">
                          {{ institution.context }}
                        </p>
                      </div>
                      <Badge v-if="institution.origin === 'admin'" variant="outline" class="shrink-0 text-[10px]">
                        Admin
                      </Badge>
                    </div>
                  </Button>
                </template>
              </div>
            </div>
          </CollapsibleContent>
        </Collapsible>
      </div>

      <!-- Full Institution Selector (selected summary only) -->
      <div class="space-y-4">
        <FormField v-slot="{ componentField }" name="institution_id">
          <FormItem>
            <FormLabel class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <component :is="Icons.INSTITUTION" class="h-4 w-4" />
                {{ $t("Institucija") }}
              </div>
              <div v-if="selectedInstitution" class="text-xs text-muted-foreground">
                {{ getInstitutionInfo(selectedInstitution) }}
              </div>
            </FormLabel>

            <!-- Selected Institution Display -->
            <div v-if="selectedInstitution && selectedInstitutionData" class="mt-3 p-3 bg-muted/50 rounded-lg">
              <div class="flex items-start gap-3">
                <component :is="Icons.INSTITUTION" class="h-5 w-5 mt-0.5 text-primary shrink-0" />
                <div class="flex-1 min-w-0">
                  <h4 class="font-medium truncate" :title="selectedInstitutionData.name">
                    {{ selectedInstitutionData.name }}
                  </h4>
                  <div class="mt-2 space-y-1 text-sm text-muted-foreground">
                    <div v-if="selectedInstitutionData.type" class="flex items-center gap-2">
                      <Badge variant="secondary" class="text-xs">
                        {{ selectedInstitutionData.type }}
                      </Badge>
                    </div>
                    <div v-if="selectedInstitutionData.lastMeeting" class="flex items-center gap-1">
                      <Calendar class="h-3 w-3" />
                      {{ $t('Paskutinis susitikimas') }}: {{ formatLastMeeting(selectedInstitutionData.lastMeeting) }}
                    </div>
                    <div v-if="selectedInstitutionData.activeCheckIn" class="flex items-center gap-1">
                      <CheckCircle class="h-3 w-3 text-green-600" />
                      {{ $t('Turi aktyvią pažymą') }}
                    </div>
                  </div>
                </div>
                <Button type="button" variant="ghost" size="icon" class="h-8 w-8" @click="clearSelection">
                  <X class="h-4 w-4" />
                </Button>
              </div>
            </div>

            <FormMessage />
          </FormItem>
        </FormField>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-2">
          <div class="flex items-center gap-2" />

          <Button type="submit" :disabled="!selectedInstitution">
            {{ $t("Toliau") }}
            <ArrowRight class="ml-2 h-4 w-4" />
          </Button>
        </div>
      </div>
    </Form>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { useForm, Form } from "vee-validate";
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';
import {
  Search,
  Calendar,
  CheckCircle,
  X,
  ChevronDown,
  ArrowRight
} from "lucide-vue-next";

import Icons from "@/Types/Icons/filled";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

// Import Shadcn components
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
import { Separator } from "@/Components/ui/separator";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/collapsible";
import {
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/Components/ui/form";


// Import Lucide icons

const emit = defineEmits<(e: "submit", data: string) => void>();

const props = defineProps<{
  selectedInstitution?: App.Entities.Institution;
}>();

const showAlert = ref(true);
const searchQuery = ref("");
const showDropdown = ref(false);
const selectedInstitution = ref<string>("");
const showAllInstitutions = ref(false);
const visibleInstitutionsLimit = 5;

// Institution will be initialized by the watcher

// Set initial values from props (computed to be reactive)
const initialValues = computed(() => ({
  institution_id: props.selectedInstitution?.id || ''
}));

// Get all available institutions from Inertia props
const page = usePage()
const providedInstitutions = computed(() => {
  const provided = (page.props as any)?.accessibleInstitutions || []
  return provided.map((i: any) => ({
    label: i.name,
    value: String(i.id),
    context: i.tenant?.shortname || '',
    lastMeeting: i.last_meeting_date,
    activeCheckIn: !!i.active_check_in,
    meetingCount: i.meetings?.length || 0,
    institution: i,
    origin: 'admin' as const,
  }))
})

// Only the user's own institutions for badge/cards
const myInstitutions = computed(() => {
  const duties = usePage().props.auth?.user?.current_duties || []
  const fromDuties = duties
    .map((duty: any) => {
      const inst = duty.institution
      if (!inst) return null
      return {
        label: inst.name,
        value: inst.id,
        context: inst.types?.[0]?.title || '',
        lastMeeting: inst.last_meeting_date,
        activeCheckIn: inst.active_check_in,
        meetingCount: inst.meetings?.length || 0,
        institution: inst,
      }
    })
    .filter(Boolean) as any[]

  // Dedupe and sort
  const deduped = fromDuties.filter((value, index, self) => self.findIndex(t => t.value === value.value) === index)
  return deduped.sort((a, b) => a.label.localeCompare(b.label))
})

// Recent institutions based on meeting dates
const recentInstitutions = computed(() => {
  return adminOnlyInstitutions.value
    .filter((inst: any) => inst.lastMeeting)
    .sort((a: any, b: any) => new Date(b.lastMeeting).getTime() - new Date(a.lastMeeting).getTime())
    .slice(0, 3)
    .map((inst: any) => ({
      id: inst.value,
      name: inst.label,
      last_meeting_date: inst.lastMeeting,
      meetingCount: inst.meetingCount,
      active_check_in: inst.activeCheckIn,
    }))
});

// Favorite institutions based on meeting frequency
const favoriteInstitutions = computed(() => {
  return adminOnlyInstitutions.value
    .filter((inst: any) => inst.meetingCount > 0)
    .sort((a: any, b: any) => b.meetingCount - a.meetingCount)
    .slice(0, 4)
    .map((inst: any) => ({ id: inst.value, name: inst.label }))
});

// Only admin institutions that user doesn't already have direct access to
const adminOnlyInstitutions = computed(() => {
  const myIds = myInstitutions.value.map(inst => inst.value)
  return providedInstitutions.value.filter(inst => !myIds.includes(inst.value))
})

// Show search UI only if there are additional admin institutions
const hasAdditionalInstitutions = computed(() => adminOnlyInstitutions.value.length > 0)

// Filtered institutions for search
const filteredInstitutions = computed(() => {
  const q = searchQuery.value.trim()
  if (!q) return adminOnlyInstitutions.value.slice(0, showAllInstitutions.value ? undefined : visibleInstitutionsLimit)
  return adminOnlyInstitutions.value.filter((inst: any) =>
    inst.label.toLowerCase().includes(q.toLowerCase()) ||
    inst.context.toLowerCase().includes(q.toLowerCase())
  )
})

// Selected institution data
const selectedInstitutionData = computed(() => {
  if (!selectedInstitution.value) return null;

  // Check both user institutions and admin institutions
  const allInstitutions = [...myInstitutions.value, ...adminOnlyInstitutions.value]
  const institution = allInstitutions.find((inst: any) => inst.value === selectedInstitution.value);
  if (!institution) return null;

  return {
    id: institution.value,
    name: institution.label,
    type: institution.context,
    lastMeeting: institution.lastMeeting,
    activeCheckIn: institution.activeCheckIn,
    meetingCount: institution.meetingCount
  };
});

// Methods
const selectInstitution = (institutionId: string) => {
  selectedInstitution.value = institutionId;
  searchQuery.value = "";
  showDropdown.value = false;
};

const selectInstitutionFromSearch = (institutionId: string, institutionName: string) => {
  selectedInstitution.value = institutionId;
  searchQuery.value = institutionName;
  showDropdown.value = false;
};

const clearSelection = () => {
  selectedInstitution.value = "";
  searchQuery.value = "";
};

const getInstitutionInfo = (institutionId: string): string => {
  const allInstitutions = [...myInstitutions.value, ...adminOnlyInstitutions.value]
  const institution = allInstitutions.find((inst: any) => inst.value === institutionId);
  if (!institution) return '';

  const parts: string[] = [];
  if (institution.meetingCount > 0) {
    parts.push(`${institution.meetingCount} ${$t('susitikimai')}`);
  }
  if (institution.activeCheckIn) {
    parts.push($t('aktyvi pažyma'));
  }

  return parts.join(' • ');
};

const formatLastMeeting = (dateString: string): string => {
  if (!dateString) return $t('Nėra');

  const date = new Date(dateString);
  const now = new Date();
  const diffTime = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays === 0) {
    return $t('Šiandien');
  } else if (diffDays === 1) {
    return $t('Vakar');
  } else if (diffDays < 7) {
    return `${diffDays} ${$t('d. praeityje')}`;
  } else if (diffDays < 30) {
    const weeks = Math.floor(diffDays / 7);
    return `${weeks} ${$t('sav. praeityje')}`;
  } else {
    return date.toLocaleDateString();
  }
};

const onSubmit = ({ institution_id }: any) => {
  const institutionToSubmit = selectedInstitution.value || institution_id;

  if (institutionToSubmit) {
    emit("submit", institutionToSubmit);
  }
};

// Handle clicks outside dropdown
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.relative')) {
    showDropdown.value = false;
  }
};

// Watch for props changes to update selected institution
watch(() => props.selectedInstitution, (newInstitution) => {
  if (newInstitution) {
    selectedInstitution.value = newInstitution.id;
  } else {
    selectedInstitution.value = '';
  }
}, { immediate: true });

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
