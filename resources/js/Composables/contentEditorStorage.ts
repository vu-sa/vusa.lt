export interface RecoveryCopy {
  snapshot: Record<string, unknown>;
  updated_at: string;
  revision?: number;
}

async function database(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('vusa-content-editor', 1);
    request.onupgradeneeded = () => request.result.createObjectStore('drafts');
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

export async function recoveryStorage(key: string, operation: 'get' | 'put' | 'delete', value?: RecoveryCopy): Promise<RecoveryCopy | undefined> {
  const db = await database();
  try {
    return await new Promise((resolve, reject) => {
      const transaction = db.transaction('drafts', operation === 'get' ? 'readonly' : 'readwrite');
      const store = transaction.objectStore('drafts');
      const request = operation === 'get' ? store.get(key) : operation === 'put' ? store.put(value, key) : store.delete(key);
      transaction.oncomplete = () => resolve(operation === 'get' ? request.result : undefined);
      transaction.onerror = () => reject(transaction.error);
      transaction.onabort = () => reject(transaction.error);
    });
  }
  finally {
    db.close();
  }
}
