<template>
  <SmartLink class="plain">
    <Card class="border shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-md dark:border-zinc-200/20" as="button">
      <div class="relative h-32 w-full">
        <img v-if="institution.image_url" class="size-full object-cover" :src="institution.image_url"
          :alt="institution.name">
        <img v-if="institution.logo_url" class="absolute -bottom-4 left-8 size-16 rounded-full border shadow-sm"
          :src="institution.logo_url">
      </div>
      <CardHeader class="mt-2">
        <CardTitle class="font-bold">
          {{ institution.name }}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div v-html="institution.description" />
      </CardContent>
      <CardFooter>
        <a v-if="institution.facebook_url" :href="institution.facebook_url" target="_blank" rel="noopener noreferrer">
          <NButton tertiary size="small" circle @click.stop>
            <template #icon>
              <IMdiFacebook />
            </template>
          </NButton>
        </a>
        <a v-if="institution.instagram_url" :href="institution.instagram_url" target="_blank" rel="noopener noreferrer">
          <NButton tertiary size="small" circle @click.stop>
            <template #icon>
              <IMdiInstagram />
            </template>
          </NButton>
        </a>
        <a v-if="institution.website" :href="institution.website" target="_blank" rel="noopener noreferrer">
          <NButton tertiary size="small" circle @click.stop>
            <template #icon>
              <IFluentGlobe20Regular />
            </template>
          </NButton>
        </a>
        <a v-if="institution.email" :href="`mailto:${institution.email}`">
          <NButton tertiary size="small" circle @click.stop>
            <template #icon>
              <IFluentMail20Regular />
            </template>
          </NButton>
        </a>
        <a v-if="institution.phone" :href="`tel:${institution.phone}`">
          <NButton tertiary size="small" circle @click.stop>
            <template #icon>
              <IFluentPhone20Regular />
            </template>
          </NButton>
        </a>
      </CardFooter>
    </Card>
  </SmartLink>
</template>

<script setup lang="ts">
import Card from '../ShadcnVue/ui/card/Card.vue';
import CardContent from '../ShadcnVue/ui/card/CardContent.vue';
import CardFooter from '../ShadcnVue/ui/card/CardFooter.vue';
import CardHeader from '../ShadcnVue/ui/card/CardHeader.vue';
import CardTitle from '../ShadcnVue/ui/card/CardTitle.vue';
import SmartLink from '../Public/SmartLink.vue';

defineProps<{
  institution: App.Entities.Institution;
}>()

</script>
