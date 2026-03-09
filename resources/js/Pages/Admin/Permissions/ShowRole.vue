<template>
  <AdminContentPage :title="role.name" :back-url="route('roles.index')">
    <Card>
      <CardHeader>
        <CardTitle>{{ role.name }}</CardTitle>
      </CardHeader>
      <CardContent>
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
      </CardContent>
      <CardFooter>
        <Button @click="router.visit(route('roles.edit', role))">
          Redaguoti
        </Button>
      </CardFooter>
    </Card>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { router } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";

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
