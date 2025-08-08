<template>
  <AdminContentPage :title="role.name" :back-url="route('roles.index')">
    <NCard title="Rolės informacija">
      <template #header>
        <h3>{{ role.name }}</h3>
      </template>
      
      <div class="mb-4">
        <strong>Rolės pavadinimas:</strong> {{ role.name }}
      </div>
      
      <div class="mb-4">
        <strong>Sargybos pavadinimas:</strong> {{ role.guard_name }}
      </div>
      
      <div v-if="role.permissions && role.permissions.length > 0" class="mb-4">
        <strong>Teisės:</strong>
        <ul class="list-disc list-inside mt-2">
          <li v-for="permission in role.permissions" :key="permission.id">
            {{ permission.name }}
          </li>
        </ul>
      </div>
      
      <div v-else class="mb-4">
        <strong>Teisės:</strong> Nėra priskirtų teisių
      </div>
      
      <template #action>
        <NButton type="primary" @click="router.visit(route('roles.edit', role))">
          Redaguoti
        </NButton>
      </template>
    </NCard>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { NButton, NCard } from "naive-ui";
import { router } from "@inertiajs/vue3";

interface Role {
  id: string;
  name: string;
  guard_name: string;
  permissions?: Array<{
    id: string;
    name: string;
  }>;
}

interface Props {
  role: Role;
}

defineProps<Props>();
</script>
