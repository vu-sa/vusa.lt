export function stripHtml(html?: string | null): string {
  if (!html) return '';

  const template = document.createElement('template');
  template.innerHTML = html;
  template.content.querySelectorAll('script,style').forEach(element => element.remove());
  return template.content.textContent ?? '';
}
