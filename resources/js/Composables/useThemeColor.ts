const DARK_THEME_COLOR = '#252528';
const LIGHT_THEME_COLOR = '#ffffff';

function syncThemeColor(isDark = document.documentElement.classList.contains('dark')): void {
  const themeColor = document.querySelector<HTMLMetaElement>('meta[name="theme-color"]');

  if (!themeColor) {
    return;
  }

  const content = isDark ? DARK_THEME_COLOR : LIGHT_THEME_COLOR;

  if (themeColor.content === content) {
    return;
  }

  // Reinsertion makes mobile browsers re-evaluate their surrounding UI in the same interaction.
  const replacement = themeColor.cloneNode() as HTMLMetaElement;
  replacement.content = content;
  themeColor.replaceWith(replacement);
}

export function applyThemeImmediately(isDark: boolean): void {
  document.documentElement.classList.toggle('dark', isDark);
  syncThemeColor(isDark);
}

export function initThemeColor(): void {
  syncThemeColor();

  new MutationObserver(() => syncThemeColor()).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  });
}
