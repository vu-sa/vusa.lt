<template>
  <a 
    :href="institutionUrl"
    class="group block rounded-xl border border-border/60 bg-card p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary/30 transition-all duration-200"
  >
    <div class="flex gap-4">
      <!-- Logo/Image -->
      <div class="flex-shrink-0">
        <div 
          v-if="institution.logo_url" 
          class="size-14 sm:size-16 rounded-full border border-border/50 bg-white overflow-hidden shadow-sm"
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
          class="size-14 sm:size-16 rounded-full border border-border/50 bg-muted flex items-center justify-center"
        >
          <Building2 class="w-6 h-6 text-muted-foreground" />
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <!-- Name -->
        <h3 class="text-base sm:text-lg font-semibold text-foreground line-clamp-2 group-hover:text-primary transition-colors">
          {{ institution.name }}
        </h3>

        <!-- Tenant Badge -->
        <div v-if="institution.tenant?.shortname" class="mt-1.5">
          <Badge variant="secondary" class="text-xs">
            {{ institution.tenant.shortname }}
          </Badge>
        </div>

        <!-- Types -->
        <div v-if="institution.types?.length" class="mt-2 flex flex-wrap gap-1">
          <span 
            v-for="type in institution.types.slice(0, 2)" 
            :key="type.id"
            class="text-xs text-muted-foreground"
          >
            {{ type.title }}{{ institution.types.indexOf(type) < Math.min(institution.types.length, 2) - 1 ? ', ' : '' }}
          </span>
          <span v-if="institution.types.length > 2" class="text-xs text-muted-foreground">
            +{{ institution.types.length - 2 }}
          </span>
        </div>

        <!-- Contact Info Row -->
        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
          <span v-if="institution.email" class="inline-flex items-center gap-1">
            <Mail class="w-3 h-3" />
            <span class="truncate max-w-32">{{ institution.email }}</span>
          </span>
          <span v-if="institution.duties_count > 0" class="inline-flex items-center gap-1">
            <Users class="w-3 h-3" />
            {{ institution.duties_count }} {{ institution.duties_count === 1 ? $t('search.contact_singular') : $t('search.contacts_plural') }}
          </span>
        </div>

        <!-- Social Links -->
        <div v-if="institution.facebook_url || institution.instagram_url" class="mt-2 flex gap-2">
          <a 
            v-if="institution.facebook_url" 
            :href="institution.facebook_url" 
            target="_blank" 
            rel="noopener noreferrer"
            class="text-blue-600 hover:text-blue-700"
            @click.stop
          >
            <IMdiFacebook class="w-4 h-4" />
          </a>
          <a 
            v-if="institution.instagram_url" 
            :href="institution.instagram_url" 
            target="_blank" 
            rel="noopener noreferrer"
            class="text-pink-600 hover:text-pink-700"
            @click.stop
          >
            <IMdiInstagram class="w-4 h-4" />
          </a>
        </div>
      </div>
    </div>
  </a>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { Building2, Mail, Users } from 'lucide-vue-next'
import { Badge } from '@/Components/ui/badge'

interface InstitutionData {
  id: string
  name: string
  short_name?: string | null
  description?: string | null
  alias?: string
  email?: string | null
  phone?: string | null
  website?: string | null
  logo_url?: string | null
  image_url?: string | null
  facebook_url?: string | null
  instagram_url?: string | null
  tenant?: {
    id: number | null
    shortname: string | null
    alias: string | null
  } | null
  types?: Array<{
    id: number
    slug: string
    title: string
  }>
  duties_count?: number
}

const props = defineProps<{
  institution: InstitutionData
}>()

const page = usePage()

const institutionUrl = computed(() => {
  const locale = (page.props.app as any)?.locale || 'lt'
  const subdomain = props.institution.tenant?.alias === 'vusa' ? 'www' : (props.institution.tenant?.alias || 'www')
  
  return route('contacts.institution', {
    institution: props.institution.id,
    subdomain,
    lang: locale
  })
})
</script>
