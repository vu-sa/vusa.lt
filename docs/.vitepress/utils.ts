/**
 * Deep merge two objects
 */
export function mergeObjects(target: Record<string, any>, source: Record<string, any>): Record<string, any> {
  const merged = { ...target };
  
  for (const key in source) {
    if (isObject(source[key]) && isObject(target[key])) {
      merged[key] = mergeObjects(target[key], source[key]);
    } else {
      merged[key] = source[key];
    }
  }
  
  return merged;
}

function isObject(item: any): boolean {
  return item && typeof item === 'object' && !Array.isArray(item);
}