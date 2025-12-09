<template>
  <PageContent :title="$t('settings.title')" :back-url="route('administration')">
    <div class="space-y-6">
      <!-- Description -->
      <p class="text-muted-foreground">
        {{ $t('settings.description') }}
      </p>

      <!-- General Settings Category -->
      <section>
        <h2 class="mb-4 text-xl font-semibold">
          {{ $t('settings.categories.general') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <!-- Form Settings -->
          <Link :href="route('settings.forms.edit')">
            <Card class="h-full transition-all duration-200 hover:shadow-md hover:ring-1 hover:ring-primary/20">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <NIcon :component="Icons.FORM" />
                  {{ $t('settings.pages.forms.title') }}
                </CardTitle>
                <CardDescription>
                  {{ $t('settings.pages.forms.description') }}
                </CardDescription>
              </CardHeader>
            </Card>
          </Link>

          <!-- Meeting Settings -->
          <Link :href="route('settings.meetings.edit')">
            <Card class="h-full transition-all duration-200 hover:shadow-md hover:ring-1 hover:ring-primary/20">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <NIcon :component="Icons.MEETING" />
                  {{ $t('settings.pages.meetings.title') }}
                </CardTitle>
                <CardDescription>
                  {{ $t('settings.pages.meetings.description') }}
                </CardDescription>
              </CardHeader>
            </Card>
          </Link>

          <!-- Atstovavimas Settings -->
          <Link :href="route('settings.atstovavimas.edit')">
            <Card class="h-full transition-all duration-200 hover:shadow-md hover:ring-1 hover:ring-primary/20">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <NIcon :component="Icons.INSTITUTION" />
                  {{ $t('settings.pages.atstovavimas.title') }}
                </CardTitle>
                <CardDescription>
                  {{ $t('settings.pages.atstovavimas.description') }}
                </CardDescription>
              </CardHeader>
            </Card>
          </Link>
        </div>
      </section>

      <!-- Authorization Settings Category (Super Admin Only) -->
      <section v-if="isSuperAdmin">
        <h2 class="mb-4 text-xl font-semibold">
          {{ $t('settings.categories.authorization') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <!-- Settings Authorization -->
          <Link :href="route('settings.authorization.edit')">
            <Card class="h-full transition-all duration-200 hover:shadow-md hover:ring-1 hover:ring-primary/20">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <NIcon :component="Icons.ROLE" />
                  {{ $t('settings.pages.authorization.title') }}
                </CardTitle>
                <CardDescription>
                  {{ $t('settings.pages.authorization.description') }}
                </CardDescription>
              </CardHeader>
            </Card>
          </Link>
        </div>
      </section>
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { NIcon } from "naive-ui";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import { Card, CardHeader, CardTitle, CardDescription } from "@/Components/ui/card";
import Icons from "@/Types/Icons/regular";

defineProps<{
  isSuperAdmin: boolean;
}>();
</script>
