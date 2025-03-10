<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <section>
      <h1 class="font-extrabold text-4xl">
        {{ $page.props.app.locale === 'lt' ? 'Registracija į kuratorių programą' : 'Registration for the Mentor program' }}
      </h1>
      <div class="typography max-w-prose">
        <p v-if="$page.props.app.locale === 'lt'">
          Kuratorių programa – VU SA įgyvendinamas projektas, kurio dalyviai – vyresnių kursų savanoriai studentai –
          padeda
          pirmakursiams sklandžiai įsilieti į universiteto gyvenimą.
        </p>
        <p v-else>
          It is a VU SR program, whose participants are volunteer students in their senior years, helps first-year students
          integrate smoothly into university life.
        </p>
        <p>
          {{ $page.props.app.locale === 'lt' ? 'Daugiau apie programą' : 'More about the programme' }} - <SmartLink class="font-bold"
            :href="route('contacts.alias', { institution: 'kuratoriu-programa', lang: 'lt', subdomain: 'www' })">
            {{ $t('čia') }}
          </SmartLink>.
        </p>
      </div>
    </section>
    <img :src="$page.props.app.locale === 'lt' ? '/logos/kuratorius.png' : '/logos/mentor.svg'" alt="Kuratorius" class="w-full aspect-video -order-1 md:order-1 object-contain dark:invert">
  </div>
  <h2 class="my-4 underline">
    {{ $page.props.app.locale === 'lt' ? '2025 m. registracijos formos' : '2025 registration forms' }}
  </h2>
  <section class="grid mt-2 grid-cols-2 lg:grid-cols-4 gap-8">
    <a v-for="tenant in mergedTenants" :key="tenant.id" class="group" target="_blank" :href="tenant.form">
      <img :src="tenant.primary_institution?.image_url"
        class="h-40 rounded-xl object-cover shadow-md w-full transition group-hover:shadow-xl">
      <p class="mt-2 text-center text-lg font-extrabold leading-tight ">
        {{ $page.props.app.locale === 'lt' ? "VU" + getFacultyName(tenant) : "VU " + tenant.englishName }}
      </p>
    </a>
  </section>
</template>

<script setup lang="ts">
import SmartLink from '@/Components/Public/SmartLink.vue';
import { getFacultyName } from '@/Utils/String';
import { computed } from 'vue';

const props = defineProps<{
  forms: Record<App.Entities.Tenant["alias"], string>;
  tenants: App.Entities.Tenant[];
  englishTenantNames: Record<App.Entities.Tenant["alias"], string>;
}>()

const mergedTenants = computed(() => {
  return props.tenants.map(tenant => {
    return {
      ...tenant,
      form: props.forms[tenant.alias],
      englishName: props.englishTenantNames[tenant.alias]
    }
  })
})

</script>
