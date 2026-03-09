<template>
  <div class="flex items-center gap-1 sm:gap-1.5 p-1 sm:p-1.5 bg-muted/50 rounded-xl border border-border/50">
    <component
      :is="item.page === page ? 'div' : Link"
      v-for="item in pages"
      :key="item.page"
      :href="item.page !== page ? getPageUrl(item) : undefined"
      :class="[
        'group relative p-2 sm:p-3 rounded-xl transition-all duration-200',
        'focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2',
        item.page === page
          ? 'bg-gradient-to-br from-primary/10 to-secondary/10 shadow-sm cursor-default'
          : 'hover:bg-muted cursor-pointer'
      ]"
      :title="$t(item.titleKey)"
      :aria-label="$t(item.ariaLabelKey)"
      :aria-current="item.page === page ? 'page' : undefined"
    >
      <component
        :is="item.icon"
        :class="[
          'w-6 h-6 sm:w-8 sm:h-8 transition-colors duration-200',
          item.page === page
            ? 'text-primary'
            : 'text-muted-foreground group-hover:text-foreground'
        ]"
      />
      <!-- Active indicator dot -->
      <span
        v-if="item.page === page"
        class="absolute -bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-primary"
      />
    </component>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'
import { Link, usePage as useInertiaPage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'

// Icons
import DocumentIcon from '~icons/fluent/document-multiple24-filled'
import MeetingIcon from '~icons/fluent/device-meeting-room-remote24-filled'
import { Building2 } from 'lucide-vue-next'

// Types
type SearchPage = 'documents' | 'meetings' | 'contacts'

interface PageConfig {
  page: SearchPage
  icon: Component
  titleKey: string
  ariaLabelKey: string
  routeName: string
  isGlobal: boolean // If true, uses www subdomain
}

// Props
interface Props {
  page: SearchPage
}

defineProps<Props>()

// Get current page props
const inertiaPage = useInertiaPage()

// Page configurations
const pages = computed<PageConfig[]>(() => [
  {
    page: 'documents',
    icon: DocumentIcon,
    titleKey: 'search.document_search_title',
    ariaLabelKey: 'search.go_to_documents',
    routeName: 'documents',
    isGlobal: true
  },
  {
    page: 'meetings',
    icon: MeetingIcon,
    titleKey: 'search.meeting_search_title',
    ariaLabelKey: 'search.go_to_meetings',
    routeName: 'publicMeetings.index',
    isGlobal: false
  },
  {
    page: 'contacts',
    icon: Building2,
    titleKey: 'search.institution_search_title',
    ariaLabelKey: 'search.go_to_contacts',
    routeName: 'contacts',
    isGlobal: false
  }
])

// Generate URL for page navigation
const getPageUrl = (item: PageConfig): string => {
  const locale = inertiaPage.props.app?.locale || 'lt'
  
  // Use the route helper with appropriate subdomain
  if (item.isGlobal) {
    // Global routes use www subdomain
    return route(item.routeName, { subdomain: 'www', lang: locale })
  } else {
    // Tenant-specific routes preserve current subdomain
    const subdomain = inertiaPage.props.app?.subdomain || 'www'
    return route(item.routeName, { subdomain, lang: locale })
  }
}
</script>
