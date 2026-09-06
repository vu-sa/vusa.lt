import type { ExternalToast } from 'vue-sonner';

export const publicToastAppearance = {
  class: 'public-toast',
} satisfies Pick<ExternalToast, 'class'>;
