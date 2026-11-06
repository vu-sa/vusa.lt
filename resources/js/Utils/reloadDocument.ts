/** A full browser reload; its own module so specs can mock it (jsdom cannot navigate). */
export function reloadDocument(): void {
  window.location.reload();
}
