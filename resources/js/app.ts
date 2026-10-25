import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

import { initThemeColor } from './Composables/useThemeColor';
import { Ziggy } from './ziggy';
import { ziggyConfig, type ZiggyContext } from './Utils/ziggyConfig';

const scriptEl = document.querySelector('script[data-page][type="application/json"]');
const initialPage = JSON.parse(scriptEl?.textContent ?? '{}');
const page = usePage();
globalThis.Ziggy = ziggyConfig(Ziggy, () => (page.props?.ziggyContext ?? initialPage.props.ziggyContext) as ZiggyContext);
globalThis.route = route;

initThemeColor();

if (initialPage.component.startsWith('Admin/')) {
  import('./admin');
}
else if (initialPage.component.startsWith('Public/')) {
  import('./public');
}
