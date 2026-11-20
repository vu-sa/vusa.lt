<template>
  <PeopleSearchField v-model="selected" input-id="support-request-involved-users" :aria-label="$t('Susiję nariai')" />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import PeopleSearchField from '@/Features/Admin/AdminSearch/Components/Select/PeopleSearchField.vue';
import type { SupportRequestUser } from '@/Types/supportRequests';

const selected = defineModel<SupportRequestUser[]>({ required: true });
</script>
