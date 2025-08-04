<template>
  <div class="space-y-4">
    <!-- Main Organization (VU SA) -->
    <div v-if="tenantHierarchy.main.length > 0" class="space-y-2">
      <h4 class="text-xs sr-only font-medium text-muted-foreground uppercase tracking-wide flex items-center gap-2">
        Pagrindinė organizacija
      </h4>
      <div class="space-y-1">
        <label v-for="tenant in tenantHierarchy.main" :key="tenant.shortname"
          :class="getTenantLabelClasses(tenant.shortname)">
          <Checkbox :model-value="selectedTenants.includes(tenant.shortname)"
            @update:model-value="toggleTenant(tenant.shortname)" />
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-1 min-w-0">
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-card-foreground truncate">
                  {{ tenant.shortname }}
                </div>
              </div>
            </div>
            <Badge v-if="tenant.count && tenant.count > 0" variant="outline" class="ml-auto text-xs">
              {{ formatCount(tenant.count) }}
            </Badge>
          </div>
        </label>
      </div>
    </div>

    <!-- Faculty Representations (Padaliniai) -->
    <div v-if="tenantHierarchy.padaliniai.length > 0" class="space-y-2">
      <h4 class="text-xs font-medium text-muted-foreground uppercase tracking-wide flex items-center gap-2">
        <GraduationCap class="w-3 h-3" />
        {{ $t('search.units') }}
      </h4>
      <ScrollArea class="h-40 w-full rounded-md border border-border/50">
        <div class="space-y-1 p-2">
          <label v-for="tenant in sortedPadaliniai" :key="tenant.shortname"
            :class="getTenantLabelClasses(tenant.shortname)">
            <Checkbox :model-value="selectedTenants.includes(tenant.shortname)"
              @update:model-value="toggleTenant(tenant.shortname)" />
            <div class="flex items-center gap-2 flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-1 min-w-0">
                <div class="min-w-0 flex-1">
                  <div class="text-sm font-medium text-card-foreground truncate">
                    {{ tenant.shortname }}
                  </div>
                  <div class="text-xs text-muted-foreground truncate" :title="getFacultyNameLocative(tenant)">
                    {{ getFacultyNameLocative(tenant) }}
                  </div>
                </div>
              </div>
              <Badge v-if="tenant.count && tenant.count > 0" variant="outline" class="ml-auto text-xs">
                {{ formatCount(tenant.count) }}
              </Badge>
            </div>
          </label>
        </div>
      </ScrollArea>
    </div>

    <!-- Selected Tenants Summary -->
    <div v-if="selectedTenants.length > 0" class="pt-2 border-t">
      <div class="flex flex-wrap gap-1">
        <Badge v-for="tenant in selectedTenants.slice(0, 5)" :key="tenant" variant="secondary" class="text-xs gap-1">
          {{ tenant }}
          <Button variant="ghost" size="sm" class="h-3 w-3 p-0 hover:bg-destructive/20 hover:text-destructive"
            @click="toggleTenant(tenant)">
            <X class="w-2 h-2" />
          </Button>
        </Badge>
        <Badge v-if="selectedTenants.length > 5" variant="outline" class="text-xs">
          +{{ selectedTenants.length - 5 }} daugiau
        </Badge>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex gap-1 pt-2 border-t border-border/50">
      <Button variant="ghost" size="sm" class="h-6 px-1.5 text-xs" :disabled="allSelected" @click="selectAll">
        <CheckSquare class="w-3 h-3 mr-1" />
        {{ $t('search.select_all') }}
      </Button>
      <Button variant="ghost" size="sm" class="h-6 px-1.5 text-xs" :disabled="selectedTenants.length === 0"
        @click="clearAll">
        <RotateCcw class="w-3 h-3 mr-1" />
        {{ $t('search.clear_all') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// ShadcnVue components
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Checkbox } from '@/Components/ui/checkbox'
import { ScrollArea } from '@/Components/ui/scroll-area'

// Icons
import {
  Building,
  GraduationCap,
  X,
  CheckSquare,
  RotateCcw
} from 'lucide-vue-next'

// Props and emits
interface Tenant {
  shortname: string
  name: string // This will be fullname for padaliniai
  fullname?: string // Optional in case it's provided separately
  type: string
  count?: number
}

interface TenantHierarchy {
  main: Tenant[]
  padaliniai: Tenant[]
  pkp: Tenant[]
}

interface Props {
  tenantHierarchy: TenantHierarchy
  selectedTenants: string[]
}

interface Emits {
  (e: 'toggleTenant', tenant: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Helper function to extract faculty name in locative case from tenant
const getFacultyNameLocative = (tenant: Tenant): string => {
  // First, try to use fullname if available
  const fullname = tenant.fullname || tenant.name

  // If we have a proper fullname with the VU SA prefix, extract from it
  if (fullname.includes('Vilniaus universiteto Studentų atstovybė')) {
    const vusaPrefix = 'Vilniaus universiteto Studentų atstovybė '
    const parts = fullname.split(vusaPrefix)

    if (parts.length > 1 && parts[1]) {
      const result = parts[1].trim()
      return result
    }
  }

  // Otherwise, look up the tenant in the tenants data
  const page = usePage()
  const tenants = page.props.tenants as any[]

  if (tenants && Array.isArray(tenants)) {
    const matchingTenant = tenants.find(t => t.shortname === tenant.shortname)

    if (matchingTenant && matchingTenant.fullname) {
      // Extract the faculty name from the fullname
      const vusaPrefix = 'Vilniaus universiteto Studentų atstovybė '
      const parts = matchingTenant.fullname.split(vusaPrefix)

      if (parts.length > 1 && parts[1]) {
        const result = parts[1].trim()
        return result
      }
    }
  }

  return ''
}

// Helper functions
const formatCount = (count: number | undefined): string => {
  if (!count) return '0'
  if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}k`
  }
  return count.toString()
}

const toggleTenant = (tenantShortname: string) => {
  emit('toggleTenant', tenantShortname)
}

// Computed properties
const allTenants = computed(() => [
  ...props.tenantHierarchy.main,
  ...props.tenantHierarchy.padaliniai
  // Exclude PKP tenants as per user request
])

const allSelected = computed(() => {
  const allTenantNames = allTenants.value.map(t => t.shortname)
  return allTenantNames.every(name => props.selectedTenants.includes(name))
})

const sortedPadaliniai = computed(() => {
  return [...props.tenantHierarchy.padaliniai].sort((a, b) => {
    // Sort by count first (descending), then by name
    if (a.count !== b.count) {
      return (b.count || 0) - (a.count || 0)
    }
    return a.shortname.localeCompare(b.shortname)
  })
})

// Actions
const selectAll = () => {
  allTenants.value.forEach(tenant => {
    if (!props.selectedTenants.includes(tenant.shortname)) {
      toggleTenant(tenant.shortname)
    }
  })
}

const clearAll = () => {
  // Toggle off all selected tenants
  props.selectedTenants.forEach(tenant => {
    toggleTenant(tenant)
  })
}

// Styling functions
const getTenantLabelClasses = (tenantShortname: string): string => {
  const baseClasses = 'flex items-center gap-2.5 p-2 rounded-lg border cursor-pointer transition-all duration-200 hover:shadow-sm'
  const isSelected = props.selectedTenants.includes(tenantShortname)

  if (isSelected) {
    return `${baseClasses} bg-accent border-accent-foreground/30 shadow-sm`
  }

  return `${baseClasses} bg-background border-border hover:bg-accent/50`
}
</script>

<style scoped>
/* Custom scrollbar for tenant lists - can't be replaced with Tailwind */
.max-h-48 {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--muted-foreground)) hsl(var(--background));
}

.max-h-48::-webkit-scrollbar {
  width: 6px;
}

.max-h-48::-webkit-scrollbar-track {
  background: hsl(var(--background));
}

.max-h-48::-webkit-scrollbar-thumb {
  background-color: hsl(var(--muted-foreground));
  border-radius: 3px;
}

.max-h-48::-webkit-scrollbar-thumb:hover {
  background-color: hsl(var(--foreground));
}
</style>
