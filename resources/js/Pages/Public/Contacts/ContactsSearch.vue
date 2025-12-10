<template>
  <div class="mt-12">
    <h1 class="text-4xl">
      {{ $t("Kontaktų paieška") }}
    </h1>
    <div class="my-4 flex flex-col sm:flex-row gap-2">
      <!-- Institution search combobox -->
      <div class="flex-1">
        <Popover v-model:open="institutionOpen">
          <PopoverTrigger as-child>
            <Button variant="outline" role="combobox" :aria-expanded="institutionOpen" 
              class="w-full justify-between h-11 text-left font-normal">
              <span class="text-muted-foreground">{{ $t('Ieškoti') }}...</span>
              <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
          </PopoverTrigger>
          <PopoverContent class="w-full p-0" align="start">
            <Command>
              <CommandInput :placeholder="`${$t('Ieškoti')}...`" />
              <CommandEmpty>{{ $t('Nieko nerasta') }}</CommandEmpty>
              <CommandList>
                <template v-for="group in filteredInstitutions" :key="group.value">
                  <CommandGroup :heading="group.label">
                    <CommandItem v-for="institution in group.institutions" :key="institution.value"
                      :value="institution.label ?? ''" @select="handleSelectInstitution(institution)">
                      {{ institution.label }}
                    </CommandItem>
                  </CommandGroup>
                </template>
              </CommandList>
            </Command>
          </PopoverContent>
        </Popover>
      </div>
      
      <!-- Tenant multi-select -->
      <div class="sm:w-64">
        <Popover v-model:open="tenantOpen">
          <PopoverTrigger as-child>
            <Button variant="outline" role="combobox" :aria-expanded="tenantOpen"
              class="w-full justify-between h-11 text-left font-normal">
              <span v-if="selectedTenants.length === 0" class="text-muted-foreground">{{ $t('Padaliniai') }}</span>
              <span v-else class="truncate">{{ selectedTenantLabels }}</span>
              <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
          </PopoverTrigger>
          <PopoverContent class="w-full p-0" align="start">
            <Command>
              <CommandInput :placeholder="`${$t('Ieškoti padalinių')}...`" />
              <CommandEmpty>{{ $t('Nieko nerasta') }}</CommandEmpty>
              <CommandList>
                <CommandItem v-for="tenant in tenantOptions" :key="tenant.value" :value="tenant.label"
                  @select="toggleTenant(tenant.value)">
                  <Check :class="cn('mr-2 h-4 w-4', selectedTenants.includes(tenant.value) ? 'opacity-100' : 'opacity-0')" />
                  {{ tenant.label }}
                </CommandItem>
              </CommandList>
              <div class="p-2 text-xs text-zinc-500 dark:text-zinc-400 border-t">
                {{ $t('Pasirink padalinius, kuriuose ieškoti kontaktų.') }}
              </div>
            </Command>
          </PopoverContent>
        </Popover>
      </div>
    </div>
    <template v-for="institution in institutions" :key="institution.id">
      <InstitutionFigure :institution="institution" />
      <Separator v-if="institution.id !== institutions[institutions.length - 1].id" class="my-12" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Check, ChevronsUpDown } from "lucide-vue-next";
import InstitutionFigure from "@/Components/Public/InstitutionFigure.vue";
import { Separator } from "@/Components/ui/separator";
import { Button } from "@/Components/ui/button";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/Components/ui/command";
import { cn } from "@/Utils/Shadcn/utils";

const props = defineProps<{
  institutions: App.Entities.Institution[];
  selectedTenants: number[];
}>();

const institutionOpen = ref(false);
const tenantOpen = ref(false);
const institutionSearch = ref("");

// Get tenant options from page props
const tenantOptions = computed(() => {
  return usePage().props.tenants.map((tenant) => ({
    label: tenant.shortname,
    value: tenant.id,
  }));
});

// Get labels for selected tenants
const selectedTenantLabels = computed(() => {
  return props.selectedTenants
    .map((id) => tenantOptions.value.find((t) => t.value === id)?.label)
    .filter(Boolean)
    .join(", ");
});

// Group institutions by tenant
const groupedInstitutions = computed(() => {
  return props.selectedTenants.map((tenant_id) => {
    const tenant = usePage().props.tenants.find((t) => t.id === tenant_id);
    return {
      label: tenant?.shortname ?? "",
      value: tenant_id,
      institutions: props.institutions
        .filter((institution) => institution.tenant?.id === tenant_id)
        .map((institution) => ({
          label: String(institution.name ?? ""),
          value: institution.id,
          tenant_alias: institution.tenant?.alias,
        })),
    };
  });
});

// Filtered institutions based on search
const filteredInstitutions = computed(() => {
  const search = institutionSearch.value.toLowerCase();
  if (!search) return groupedInstitutions.value;
  
  return groupedInstitutions.value
    .map((group) => ({
      ...group,
      institutions: group.institutions.filter((inst) =>
        inst.label.toLowerCase().includes(search)
      ),
    }))
    .filter((group) => group.institutions.length > 0);
});

// Toggle tenant selection
const toggleTenant = (tenantId: number) => {
  const newSelection = props.selectedTenants.includes(tenantId)
    ? props.selectedTenants.filter((id) => id !== tenantId)
    : [...props.selectedTenants, tenantId];
  
  router.reload({
    data: {
      selectedTenants: btoa(JSON.stringify(newSelection)),
    },
  });
};

// Build institution URL
const getInstitutionUrl = (institution: { value: string; tenant_alias?: string }) => {
  const subdomain = institution.tenant_alias === "vusa" ? "www" : institution.tenant_alias;
  return route("contacts.institution", {
    institution: institution.value,
    subdomain,
    lang: usePage().props.app.locale,
  });
};

// Handle institution selection
const handleSelectInstitution = (institution: { value: string; tenant_alias?: string }) => {
  router.visit(getInstitutionUrl(institution));
};
</script>
