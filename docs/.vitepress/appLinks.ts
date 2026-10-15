/** Platform links are written `[Pranešimų nustatymai](app:/mano/profile/notifications)`; guide links stay plain paths. */
export function appLinkPath(href: string): string | null {
  return href.startsWith('app:/') ? href.slice('app:'.length) : null
}
