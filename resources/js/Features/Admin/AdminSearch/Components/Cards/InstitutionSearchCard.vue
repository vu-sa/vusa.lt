<template>
  <Card
    class="group hover:shadow-md transition-all duration-200 cursor-pointer"
    @click="navigateToShow"
  >
    <CardContent class="p-4">
      <div class="flex items-start gap-3">
        <!-- Icon -->
        <div
          class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400 group-hover:bg-purple-500/15 transition-colors overflow-hidden"
        >
          <Building2 class="size-5" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <!-- Title and Tenant -->
          <div class="flex items-start justify-between gap-2">
            <h3 class="font-medium text-foreground truncate group-hover:text-primary transition-colors">
              {{ institution.name_lt || institution.name_en || $t('Be pavadinimo') }}
            </h3>
            <Badge v-if="institution.tenant_shortname" variant="outline" class="text-xs shrink-0">
              {{ institution.tenant_shortname }}
            </Badge>
          </div>

          <!-- Short name / Alias -->
          <p
            v-if="institution.short_name_lt || institution.alias"
            class="text-sm text-muted-foreground truncate mt-0.5"
          >
            {{ institution.short_name_lt || institution.alias }}
          </p>

          <!-- Meta info -->
          <div class="flex flex-wrap items-center gap-2 mt-2">
            <!-- Contact email indicator -->
            <Badge v-if="institution.email" variant="secondary" class="text-xs">
              <Mail class="size-3 mr-1" />
              {{ $t('El. paštas') }}
            </Badge>
          </div>
        </div>
      </div>
    </CardContent>

    <!-- Actions Footer -->
    <CardFooter class="px-4 py-3 bg-muted/30 border-t justify-end gap-2">
      <Button
        variant="ghost"
        size="sm"
        @click.stop="navigateToShow"
      >
        <Eye class="size-4 mr-1" />
        {{ $t('Peržiūrėti') }}
      </Button>
      <Button
        variant="ghost"
        size="sm"
        @click.stop="navigateToEdit"
      >
        <Pencil class="size-4 mr-1" />
        {{ $t('Redaguoti') }}
      </Button>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Building2,
  Eye,
  Mail,
  Pencil,
} from 'lucide-vue-next'

import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { Card, CardContent, CardFooter } from '@/Components/ui/card'
import type { InstitutionSearchResult } from '@/Composables/useAdminSearch'

interface Props {
  institution: InstitutionSearchResult
}

const props = defineProps<Props>()

// Navigation handlers
const navigateToShow = () => {
  router.visit(route('institutions.show', { institution: props.institution.id }))
}

const navigateToEdit = () => {
  router.visit(route('institutions.edit', { institution: props.institution.id }))
}
</script>
