<docs>
This layout also handles form errors. If there are any errors, it will display them in an alert.
</docs>

<template>
  <NDialogProvider>
    <Alert v-if="$page.props.errors && Object.keys($page.props.errors).length > 0 && open" variant="destructive"
      class="mb-4" type="error">
      <IFluentInfo24Regular />
      <AlertTitle class="font-bold dark:text-red-700">
        {{ $t('Pataisykite klaidas') }}
      </AlertTitle>
      <AlertDescription>
        <ul class="list-disc pl-5 dark:text-red-700">
          <li v-for="(error, index) in $page.props.errors" :key="index">
            {{ error }}
          </li>
        </ul>
      </AlertDescription>
      <XIcon class="absolute top-2 right-2 cursor-pointer dark:text-red-700" @click="toggleDialog" />
    </Alert>

    <Card>
      <CardContent>
        <slot />
      </CardContent>
    </Card>
  </NDialogProvider>
</template>

<script setup lang="ts">
import { NDialogProvider } from 'naive-ui';
import { Alert, AlertDescription, AlertTitle } from '../ui/alert';
import { ref, watch } from 'vue';
import { XIcon } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { CardContent, Card } from '../ui/card';
import { useComponentBreadcrumbs } from '@/Composables/useBreadcrumbs';
import type { BreadcrumbItem } from '@/Composables/useBreadcrumbs';

// Define props for the component
const props = defineProps<{
  breadcrumbs?: BreadcrumbItem[];
}>();

const open = ref(true);

function toggleDialog() {
  open.value = !open.value;
}

watch(
  () => usePage().props.errors,
  (newErrors) => {
    if (newErrors && Object.keys(newErrors).length > 0) {
      open.value = true;
    } else {
      open.value = false;
    }
  },
  { immediate: true }
);

// Use the improved breadcrumbs composable
// This handles all breadcrumb lifecycle automatically
// useComponentBreadcrumbs(() => props.breadcrumbs);
</script>
