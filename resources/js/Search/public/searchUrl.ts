export function readSearchParams(): URLSearchParams {
  return new URLSearchParams(typeof window === 'undefined' ? '' : window.location.search);
}

export function replaceSearchParams(params: URLSearchParams, preserveHash = false): void {
  if (typeof window === 'undefined') return;

  const url = new URL(window.location.href);
  url.search = params.toString();
  if (!preserveHash) url.hash = '';
  window.history.replaceState(window.history.state, '', url);
}
