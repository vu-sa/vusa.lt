<template>
  <SmartLink :href="institutionUrl" class="plain">
    <Card class="border shadow-xs transition-all duration-300 hover:scale-105 hover:shadow-md dark:border-zinc-200/20"
      as="button">
      <!-- Cover Image Section with Placeholder -->
      <div class="relative h-32 w-full">
        <img 
          v-if="institution.image_url" 
          class="size-full rounded-t-md object-cover" 
          :src="institution.image_url"
          :alt="institution.name"
        >
        <div 
          v-else 
          class="size-full rounded-t-md bg-gradient-to-br from-muted/50 to-muted"
        />
        <!-- Logo with Placeholder -->
        <div 
          v-if="institution.logo_url"
          class="absolute -bottom-4 left-8 size-16 rounded-full border bg-white shadow-xs overflow-hidden"
        >
          <img 
            class="size-full object-contain p-1"
            :src="institution.logo_url"
            :alt="`${institution.name} logo`"
          >
        </div>
        <div 
          v-else
          class="absolute -bottom-4 left-8 size-16 rounded-full border bg-white shadow-xs flex items-center justify-center"
        >
          <Building2 class="w-6 h-6 text-muted-foreground" />
        </div>
      </div>
      
      <CardHeader class="mt-2">
        <!-- Metadata Row (tenant + types) - only shown when showMetadata is true -->
        <div v-if="showMetadata && (tenantName || displayTypes.length > 0)" class="flex flex-wrap items-center gap-1.5 mb-1.5">
          <Badge v-if="tenantName" variant="secondary" class="text-xs">
            {{ tenantName }}
          </Badge>
          <span 
            v-for="(type, index) in displayTypes" 
            :key="type.id || index"
            class="text-xs text-muted-foreground"
          >
            {{ type.title }}{{ index < displayTypes.length - 1 ? ',' : '' }}
          </span>
          <span v-if="hasMoreTypes" class="text-xs text-muted-foreground">
            +{{ institution.types.length - 2 }}
          </span>
        </div>
        
        <CardTitle class="font-bold">
          {{ institution.name }}
        </CardTitle>
      </CardHeader>
      
      <CardContent>
        <div v-if="institution.description" v-html="institution.description" />
        
        <!-- Duties count (contacts) -->
        <div v-if="showMetadata && dutiesCount > 0" class="mt-2 flex items-center gap-1.5 text-xs text-muted-foreground">
          <Users class="w-3.5 h-3.5" />
          <span>{{ dutiesCount }} {{ dutiesCount === 1 ? $t('search.contact_singular') : $t('search.contacts_plural') }}</span>
        </div>
      </CardContent>
      
      <CardFooter class="flex gap-2">
        <a v-if="institution.facebook_url" :href="institution.facebook_url" target="_blank" rel="noopener noreferrer">
          <Button variant="ghost" size="icon-sm" class="rounded-full" @click.stop>
            <IMdiFacebook />
          </Button>
        </a>
        <a v-if="institution.instagram_url" :href="institution.instagram_url" target="_blank" rel="noopener noreferrer">
          <Button variant="ghost" size="icon-sm" class="rounded-full" @click.stop>
            <IMdiInstagram />
          </Button>
        </a>
        <a v-if="institution.website" :href="institution.website" target="_blank" rel="noopener noreferrer">
          <Button variant="ghost" size="icon-sm" class="rounded-full" @click.stop>
            <IFluentGlobe20Regular />
          </Button>
        </a>
        <a v-if="institution.email" :href="`mailto:${institution.email}`">
          <Button variant="ghost" size="icon-sm" class="rounded-full" @click.stop>
            <IFluentMail20Regular />
          </Button>
        </a>
        <a v-if="institution.phone" :href="`tel:${institution.phone}`">
          <Button variant="ghost" size="icon-sm" class="rounded-full" @click.stop>
            <IFluentPhone20Regular />
          </Button>
        </a>
      </CardFooter>
    </Card>
  </SmartLink>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, Users } from 'lucide-vue-next';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import Card from '../ui/card/Card.vue';
import CardContent from '../ui/card/CardContent.vue';
import CardFooter from '../ui/card/CardFooter.vue';
import CardHeader from '../ui/card/CardHeader.vue';
import CardTitle from '../ui/card/CardTitle.vue';
import SmartLink from '../Public/SmartLink.vue';

// Support both full Institution entity and processed search result structure
interface InstitutionData {
  id: string | number;
  name?: string | Array<unknown> | null;
  short_name?: string | Array<unknown> | null;
  description?: string | Array<unknown> | null;
  alias?: string;
  email?: string | null;
  phone?: string | null;
  website?: string | null;
  image_url?: string | null;
  logo_url?: string | null;
  facebook_url?: string | null;
  instagram_url?: string | null;
  tenant?: {
    id?: number | null;
    shortname?: string | null;
    alias?: string | null;
    type?: string | null;
  } | null;
  types?: Array<{
    id?: number;
    slug?: string;
    title?: string;
  }>;
  duties_count?: number;
  has_contacts?: boolean;
}

const props = withDefaults(defineProps<{
  institution: InstitutionData | App.Entities.Institution;
  showMetadata?: boolean;
  href?: string | null;
}>(), {
  showMetadata: false,
  href: null
});

const page = usePage();

// Build institution URL - use provided href or generate from institution data
const institutionUrl = computed(() => {
  // If href is explicitly provided, use it
  if (props.href) {
    return props.href;
  }
  
  // Generate URL from institution data
  const locale = (page.props.app as any)?.locale || 'lt';
  // Use www subdomain for: vusa alias, pkp type tenants, or when no tenant
  const tenant = props.institution.tenant;
  const subdomain = (!tenant || tenant.alias === 'vusa' || tenant.type === 'pkp')
    ? 'www'
    : (tenant.alias || 'www');
  
  // Use alias if available, otherwise use id
  if (props.institution.alias) {
    return route('contacts.alias', {
      institution: props.institution.alias,
      subdomain,
      lang: locale
    });
  }
  
  return route('contacts.institution', {
    institution: props.institution.id,
    subdomain,
    lang: locale
  });
});

// Get tenant name for display
const tenantName = computed(() => {
  return props.institution.tenant?.shortname || null;
});

// Get types to display (max 2)
const displayTypes = computed(() => {
  const types = props.institution.types || [];
  return types.slice(0, 2);
});

// Check if there are more types than displayed
const hasMoreTypes = computed(() => {
  const types = props.institution.types || [];
  return types.length > 2;
});

// Get duties count (active contacts)
const dutiesCount = computed(() => {
  return props.institution.duties_count || 0;
});

</script>
