const el = document.getElementById('app');
const initialPage = JSON.parse(el.dataset.page);

if (initialPage.component.startsWith('Admin/')) {
  import('./admin');
}
else if (initialPage.component.startsWith('Public/')) {
  import('./public');
}
