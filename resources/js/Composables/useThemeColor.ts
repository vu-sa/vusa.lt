const DARK_THEME_COLOR = '#252528';
const LIGHT_THEME_COLOR = '#ffffff';

function syncThemeColor(): void {
  const themeColor = document.querySelector<HTMLMetaElement>('meta[name="theme-color"]');

  if (themeColor) {
    themeColor.content = document.documentElement.classList.contains('dark')
      ? DARK_THEME_COLOR
      : LIGHT_THEME_COLOR;
  }
}

export function initThemeColor(): void {
  syncThemeColor();

  new MutationObserver(syncThemeColor).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  });
}
