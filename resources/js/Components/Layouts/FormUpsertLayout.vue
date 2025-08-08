<template>
  <div class="space-y-4">
    <Alert v-if="$page.props.errors && Object.keys($page.props.errors).length > 0 && open" variant="destructive"
      class="mb-4">
      <AlertCircle class="h-4 w-4" />
      <AlertTitle class="font-bold">
        {{ $t('Pataisykite klaidas') }}
      </AlertTitle>
      <AlertDescription>
        <ul class="list-disc pl-5">
          <li v-for="(error, index) in $page.props.errors" :key="index">
            {{ error }}
          </li>
        </ul>
      </AlertDescription>
      <Button variant="ghost" size="icon" class="absolute top-2 right-2" @click="toggleDialog">
        <X class="h-4 w-4" />
      </Button>
    </Alert>

    <Card>
      <CardContent class="p-6">
        <slot />
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '../ui/alert';
import { Button } from '../ui/button';
import { ref, watch } from 'vue';
import { AlertCircle, X } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { CardContent, Card } from '../ui/card';
import { trans as $t } from "laravel-vue-i18n";
// No props needed - this is a pure layout component

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
</script>
