const DB_NAME = 'cashier-offline-v1';
const DB_VERSION = 1;

let openRequest = null;

export function openOfflineDb() {
    if (openRequest) {
        return openRequest;
    }

    openRequest = new Promise((resolve, reject) => {
        if (typeof indexedDB === 'undefined') {
            reject(new Error('IndexedDB is not available in this environment.'));
            return;
        }

        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            if (!db.objectStoreNames.contains('receipts')) {
                const receiptsStore = db.createObjectStore('receipts', { keyPath: 'id' });
                receiptsStore.createIndex('state', 'state', { unique: false });
                receiptsStore.createIndex('sync_status', 'sync_status', { unique: false });
                receiptsStore.createIndex('updated_at', 'updated_at', { unique: false });
            }

            if (!db.objectStoreNames.contains('sync_queue')) {
                const syncQueueStore = db.createObjectStore('sync_queue', { keyPath: 'id' });
                syncQueueStore.createIndex('status', 'status', { unique: false });
                syncQueueStore.createIndex('updated_at', 'updated_at', { unique: false });
            }

            if (!db.objectStoreNames.contains('meta')) {
                db.createObjectStore('meta', { keyPath: 'key' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error || new Error('Failed to open IndexedDB.'));
    });

    return openRequest;
}

export async function getAllFromStore(storeName) {
    return withStore(storeName, 'readonly', (store) => store.getAll());
}

export async function getFromStore(storeName, key) {
    return withStore(storeName, 'readonly', (store) => store.get(key));
}

export async function putInStore(storeName, value) {
    const serializableValue = toSerializableValue(value);
    return withStore(storeName, 'readwrite', (store) => store.put(serializableValue));
}

export async function deleteFromStore(storeName, key) {
    return withStore(storeName, 'readwrite', (store) => store.delete(key));
}

async function withStore(storeName, mode, operation) {
    const db = await openOfflineDb();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(storeName, mode);
        const store = transaction.objectStore(storeName);
        const request = operation(store);

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error || new Error(`Operation failed on ${storeName}.`));
    });
}

function toSerializableValue(value) {
    try {
        return JSON.parse(JSON.stringify(value));
    } catch {
        return value;
    }
}
