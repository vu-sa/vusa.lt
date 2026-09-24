<template>
  <SupportRequestForm
        :types
        :areas
        :roles
        :support-request
        :back-url="route('supportRequests.show', supportRequest.id)"
  />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import SupportRequestForm from '@/Components/SupportRequests/SupportRequestForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import type {
  SupportRequestItem,
  SupportRequestRoleOption,
  SupportRequestTaxonomyItem,
} from '@/Types/supportRequests';

const props = defineProps<{
  supportRequest: SupportRequestItem;
  availableStatuses: Array<{ value: string; label: string; badgeVariant: string }>;
  assignees: Array<{ id: string; name: string; profile_photo_path?: string | null }>;
  permissions: {
    can_update: boolean;
    can_update_status: boolean;
    can_assign: boolean;
    can_delete: boolean;
    can_restore: boolean;
  };
  types: SupportRequestTaxonomyItem[];
  areas: SupportRequestTaxonomyItem[];
  roles: SupportRequestRoleOption[];
  service?: SupportRequestTaxonomyItem;
}>();

usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createRouteBreadcrumb($t('vusa.lt pagalba'), 'mySupportRequests.index'),
  BreadcrumbHelpers.createRouteBreadcrumb(props.supportRequest.title, 'supportRequests.show', props.supportRequest.id),
  BreadcrumbHelpers.createBreadcrumbItem($t('Redaguoti')),
]);
</script>
