import { createApp } from 'vue';
import { i18nVue } from 'laravel-vue-i18n';

import InstitutionActivityReply from '@/Components/Institutions/InstitutionActivityReply.vue';

const element = document.getElementById('activity-reply');
const config = document.getElementById('activity-reply-config');
if (element && config) {
  const props = JSON.parse(config.textContent ?? '{}');
  createApp(InstitutionActivityReply, props).use(i18nVue, { lang: props.locale, resolve: () => Promise.resolve(props.translations) }).mount(element);
}
