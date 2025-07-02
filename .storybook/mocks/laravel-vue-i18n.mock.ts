import { fn } from 'storybook/test';

// Mock the trans function from laravel-vue-i18n
export const trans = fn((key: string) => key);

// Export as $t for components that use it that way
export const $t = trans;