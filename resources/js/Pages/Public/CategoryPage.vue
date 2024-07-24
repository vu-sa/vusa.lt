<template>
  <div>
    <section class="pt-8 last:pb-2">
      <article class="grid grid-cols-1 gap-x-12">
        <h1 class="col-span-full col-start-1 inline-flex gap-4">
          <span class="text-gray-900 dark:text-white">{{ category.name }}</span>
        </h1>
        <div class="flex max-w-prose flex-col gap-2 py-4 text-base leading-7">
          <p v-if="category.description" class="typography">
            {{ category.description }}
          </p>
          <div class="grid content-stretch gap-4 lg:grid-cols-2">
            <SmartLink v-for="page in category.pages" :key="page.id"
              :href="route('page', { permalink: page.permalink, lang: page.lang, subdomain: page.tenant.alias === 'vusa' ? 'www' : page.tenant.alias })">
              <Card
                class="h-full border shadow-sm transition-shadow hover:shadow-lg dark:border-zinc-800/40">
                <CardHeader class="h-full justify-center">
                  <CardTitle>
                    {{ page.title }}
                  </CardTitle>
                </CardHeader>
              </Card>
            </SmartLink>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Card, CardHeader, CardTitle } from '@/Components/ShadcnVue/ui/card';
import SmartLink from '@/Components/Public/SmartLink.vue';

defineProps<{
  category: App.Entities.Category;
}>();
</script>
