export function scopedGlobals() {
  const originals: Array<{ target: object; name: string; descriptor: PropertyDescriptor | undefined }> = [];

  function stubProperty(target: object, name: string, value: unknown) {
    originals.push({ target, name, descriptor: Object.getOwnPropertyDescriptor(target, name) });
    Object.defineProperty(target, name, { configurable: true, writable: true, value });
  }

  function stubGlobal(name: string, value: unknown) {
    stubProperty(globalThis, name, value);
  }

  function restoreGlobals() {
    for (const { target, name, descriptor } of originals.reverse()) {
      if (descriptor) {
        Object.defineProperty(target, name, descriptor);
      }
      else {
        Reflect.deleteProperty(target, name);
      }
    }
    originals.length = 0;
  }

  return { stubGlobal, stubProperty, restoreGlobals };
}
