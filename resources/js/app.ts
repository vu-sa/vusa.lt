import { initThemeColor } from './Composables/useThemeColor';

const scriptEl = document.querySelector('script[data-page][type="application/json"]');
const initialPage = JSON.parse(scriptEl?.textContent ?? '{}');

initThemeColor();

if (initialPage.component.startsWith('Admin/')) {
  import('./admin');
}
else if (initialPage.component.startsWith('Public/')) {
  import('./public');
}
