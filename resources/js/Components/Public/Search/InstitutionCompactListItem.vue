<template>
  <div
    class="group transition-all duration-200 border border-border/50 rounded-md bg-card hover:shadow-lg hover:bg-accent/20 hover:border-primary/30 cursor-pointer"
    @click="navigateToInstitution"
  >
    <div class="block sm:flex sm:items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2">
      <!-- Mobile Layout: Stacked -->
      <div class="sm:hidden space-y-2">
        <!-- Title Row -->
        <div class="flex items-center gap-3">
          <!-- Logo/Placeholder -->
          <div class="flex-shrink-0">
            <div 
              v-if="institution.logo_url" 
              class="size-10 rounded-full border border-border/50 bg-white overflow-hidden shadow-sm"
            >
              <img 
                :src="institution.logo_url" 
                :alt="`${institution.name} logo`"
                class="w-full h-full object-contain p-0.5"
                loading="lazy"
              >
            </div>
            <div 
              v-else 
              class="size-10 rounded-full border border-border/50 bg-muted flex items-center justify-center"
            >
              <Building2 class="w-4 h-4 text-muted-foreground" />
            </div>
          </div>
          
          <!-- Name -->
          <div class="flex-1 min-w-0">
            <h3 class="text-sm font-medium text-card-foreground group-hover:text-primary transition-colors line-clamp-1">
              {{ institution.name }}
            </h3>
          </div>
          
          <!-- Arrow -->
          <ArrowRightIcon class="w-3 h-3 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>

        <!-- Metadata Row -->
        <div class="flex items-center gap-2 text-xs text-muted-foreground ml-13">
          <!-- Tenant Badge -->
          <Badge 
            v-if="institution.tenant?.shortname" 
            variant="secondary" 
            class="text-[10px] font-medium px-1.5 py-0 flex-shrink-0"
          >
            {{ institution.tenant.shortname }}
          </Badge>

          <!-- Types (max 1 on mobile) -->
          <span 
            v-if="displayTypes.length > 0"
            class="truncate max-w-32 flex-shrink-0"
            :title="allTypesText"
          >
            {{ displayTypes[0].title }}
            <template v-if="institution.types && institution.types.length > 1">
              +{{ institution.types.length - 1 }}
            </template>
          </span>

          <!-- Contact indicators -->
          <span v-if="institution.duties_count && institution.duties_count > 0" class="whitespace-nowrap flex-shrink-0">
            {{ institution.duties_count }} {{ institution.duties_count === 1 ? $t('search.contact_singular') : $t('search.contacts_plural') }}
          </span>
        </div>
      </div>

      <!-- Desktop Layout: Horizontal -->
      <div class="hidden sm:flex sm:items-center sm:gap-3 sm:w-full">
        <!-- Logo/Placeholder -->
        <div class="flex-shrink-0">
          <div 
            v-if="institution.logo_url" 
            class="size-12 rounded-full border border-border/50 bg-white overflow-hidden shadow-sm"
          >
            <img 
              :src="institution.logo_url" 
              :alt="`${institution.name} logo`"
              class="w-full h-full object-contain p-1"
              loading="lazy"
            >
          </div>
          <div 
            v-else 
            class="size-12 rounded-full border border-border/50 bg-muted flex items-center justify-center"
          >
            <Building2 class="w-5 h-5 text-muted-foreground" />
          </div>
        </div>

        <!-- Name -->
        <div class="flex-1 min-w-0">
          <h3 class="text-sm font-medium text-card-foreground group-hover:text-primary transition-colors line-clamp-1">
            {{ institution.name }}
          </h3>
          <!-- Types as subtitle -->
          <p v-if="displayTypes.length > 0" class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
            {{ displayTypesText }}
          </p>
        </div>

        <!-- Compact Metadata -->
        <div class="flex items-center gap-2 flex-shrink-0 min-w-0">
          <!-- Contact Count -->
          <span 
            v-if="institution.duties_count && institution.duties_count > 0" 
            class="text-xs text-muted-foreground whitespace-nowrap flex items-center gap-1"
          >
            <Users class="w-3 h-3" />
            {{ institution.duties_count }}
          </span>

          <!-- Social Icons -->
          <div class="flex items-center gap-1">
            <a 
              v-if="institution.facebook_url" 
              :href="institution.facebook_url" 
              target="_blank" 
              rel="noopener noreferrer"
              class="text-muted-foreground hover:text-blue-600 transition-colors"
              @click.stop
            >
              <IMdiFacebook class="w-4 h-4" />
            </a>
            <a 
              v-if="institution.instagram_url" 
              :href="institution.instagram_url" 
              target="_blank" 
              rel="noopener noreferrer"
              class="text-muted-foreground hover:text-pink-600 transition-colors"
              @click.stop
            >
              <IMdiInstagram class="w-4 h-4" />
            </a>
            <a 
              v-if="institution.website" 
              :href="institution.website" 
              target="_blank" 
              rel="noopener noreferrer"
              class="text-muted-foreground hover:text-primary transition-colors"
              @click.stop
            >
              <IFluentGlobe20Regular class="w-4 h-4" />
            </a>
          </div>

          <!-- Tenant Badge -->
          <Badge 
            v-if="institution.tenant?.shortname" 
            variant="secondary" 
            class="text-xs font-medium px-1.5 py-0.5 flex-shrink-0"
          >
            {{ institution.tenant.shortname }}
          </Badge>

          <!-- Arrow -->
          <ArrowRightIcon class="w-3.5 h-3.5 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Badge } from '@/Components/ui/badge';
import { ArrowRightIcon, Building2, Users } from 'lucide-vue-next';

// Processed institution result structure
interface InstitutionData {
  id: string | number;
  name: string;
  short_name?: string | null;
  description?: string | null;
  alias?: string;
  email?: string | null;
  phone?: string | null;
  website?: string | null;
  address?: string | null;
  image_url?: string | null;
  logo_url?: string | null;
  facebook_url?: string | null;
  instagram_url?: string | null;
  tenant?: {
    id?: number | null;
    shortname?: string | null;
    alias?: string | null;
  } | null;
  types?: Array<{
    id?: number;
    slug?: string;
    title?: string;
  }>;
  duties_count?: number;
  has_contacts?: boolean;
}

const props = defineProps<{
  institution: InstitutionData;
}>();

const page = usePage();
const locale = computed(() => (page.props.app as any)?.locale || 'lt');

// Get types to display (max 2 on desktop)
const displayTypes = computed(() => {
  const types = props.institution.types || [];
  return types.slice(0, 2);
});

// Text for all types (for tooltip)
const allTypesText = computed(() => {
  const types = props.institution.types || [];
  return types.map(t => t.title).join(', ');
});

// Formatted types text for display
const displayTypesText = computed(() => {
  const types = props.institution.types || [];
  if (types.length === 0) return '';
  
  const displayedTypes = types.slice(0, 2).map(t => t.title).join(', ');
  if (types.length > 2) {
    return `${displayedTypes} +${types.length - 2}`;
  }
  return displayedTypes;
});

// Build institution detail URL
const getInstitutionUrl = () => {
  const subdomain = props.institution.tenant?.alias === 'vusa' 
    ? 'www' 
    : (props.institution.tenant?.alias || 'www');
  
  return route('contacts.institution', {
    institution: props.institution.id,
    subdomain,
    lang: locale.value
  });
};

// Navigate to institution (used for card click)
const navigateToInstitution = () => {
  router.visit(getInstitutionUrl());
};
</script>
