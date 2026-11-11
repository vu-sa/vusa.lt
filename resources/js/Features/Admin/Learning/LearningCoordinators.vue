<script setup lang="ts">
import PeopleContactList from '@/Components/Avatars/PeopleContactList.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import type { LearningCoordinator } from '@/Types/learning';

defineProps<{ coordinators: LearningCoordinator[] }>();
</script>

<template>
  <PeopleContactList v-if="coordinators.length" :people="coordinators" compact data-slot="learning-coordinators">
    <template #details="{ person }">
      <p v-if="person.duty" class="line-clamp-2 text-xs text-muted-foreground">
        <InflectedDutyName :name="person.duty" :holder="person" :use-original-duty-name="person.use_original_duty_name" />
      </p>
      <p v-if="coordinators.length > 1 && person.institutions.length" class="line-clamp-2 text-xs text-muted-foreground">
        {{ $t('Kuruoja') }}: {{ person.institutions.join(', ') }}
      </p>
    </template>
  </PeopleContactList>
</template>
