import { config } from "@vue/test-utils"

// import { trans as $t } from 'laravel-vue-i18n'
//import { Ziggy } from './resources/js/ziggy';
import { createHeadManager } from '@inertiajs/core';
import { route } from 'ziggy-js';

// config.global.mocks.route = (name) => route(name, undefined, undefined, Ziggy);
// config.global.mocks.$t = $t;

// fixes TypeError: Cannot read properties of undefined (reading 'createProvider')
const mockedHeadManager = createHeadManager(
  false,
  () => '',
  () => '',
);
config.global.mocks.$headManager = mockedHeadManager;
