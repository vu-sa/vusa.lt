/**
 * Strip HTML tags from a string and return plain text.
 *
 * Uses a temporary DOM element, so it should only run in the browser.
 */
export function stripHtml(html?: string | null): string {
  if (!html) {
    return '';
  }

  const tmp = document.createElement('div');
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || '';
}
