import {
    deleteFromStore,
    getAllFromStore,
    getFromStore,
    putInStore,
} from '@/offline/db';

const LEGACY_CART_STORAGE_KEY = 'cashier-cart-v1';
const LEGACY_MIGRATION_META_KEY = 'legacy_cart_migrated_v1';

export function isOfflineReceiptsEnabled() {
    return String(import.meta.env.VITE_OFFLINE_RECEIPTS_ENABLED ?? 'true') !== 'false';
}

export async function createLocalReceipt(receipt) {
    const now = new Date().toISOString();
    const created = {
        ...receipt,
        state: receipt?.state ?? 'open',
        sync_status: receipt?.sync_status ?? 'not_needed',
        is_local: true,
        created_at: receipt?.created_at ?? now,
        updated_at: now,
    };

    await putInStore('receipts', created);
    notifyReceiptsUpdated();
    return created;
}

export async function updateLocalReceipt(receiptId, updates) {
    const existing = await getFromStore('receipts', receiptId);
    if (!existing) {
        return null;
    }

    const updated = {
        ...existing,
        ...updates,
        updated_at: new Date().toISOString(),
    };

    await putInStore('receipts', updated);
    notifyReceiptsUpdated();
    return updated;
}

export async function getLocalReceipt(receiptId) {
    return getFromStore('receipts', receiptId);
}

export async function listOpenLocalReceipts() {
    const receipts = await getAllFromStore('receipts');
    return receipts
        .filter((receipt) => receipt?.state === 'open')
        .sort(byNewestCreatedAt);
}

export async function listUnsyncedCompletedReceipts() {
    const receipts = await getAllFromStore('receipts');

    return receipts
        .filter((receipt) => {
            if (receipt?.state !== 'completed') {
                return false;
            }

            return receipt?.sync_status === 'pending' || receipt?.sync_status === 'failed' || receipt?.sync_status === 'syncing';
        })
        .sort(byNewestUpdatedAt);
}

export async function listPendingSyncReceipts() {
    const queue = await getAllFromStore('sync_queue');
    const candidateQueue = queue
        .filter((entry) => entry?.status === 'pending')
        .sort(byNewestUpdatedAt);

    const receipts = [];
    for (const entry of candidateQueue) {
        const receipt = await getFromStore('receipts', entry.receipt_id);
        if (receipt) {
            receipts.push(receipt);
        }
    }

    return receipts;
}

export async function completeLocalReceipt(receiptId, payload) {
    const now = new Date().toISOString();
    const existing = await getFromStore('receipts', receiptId);

    if (!existing) {
        return null;
    }

    const completed = {
        ...existing,
        ...payload,
        state: 'completed',
        sync_status: 'pending',
        completed_at: now,
        updated_at: now,
    };

    await putInStore('receipts', completed);
    await putInStore('sync_queue', {
        id: completed.id,
        receipt_id: completed.id,
        status: 'pending',
        last_error: null,
        retry_count: 0,
        created_at: now,
        updated_at: now,
    });
    notifyReceiptsUpdated();
    return completed;
}

export async function deleteLocalReceipt(receiptId) {
    await deleteFromStore('receipts', receiptId);
    await deleteFromStore('sync_queue', receiptId);
    notifyReceiptsUpdated();
}

export async function markSyncing(receiptIds = []) {
    if (!Array.isArray(receiptIds) || receiptIds.length === 0) {
        return;
    }

    const now = new Date().toISOString();
    for (const receiptId of receiptIds) {
        const queueEntry = await getFromStore('sync_queue', receiptId);
        if (queueEntry) {
            await putInStore('sync_queue', {
                ...queueEntry,
                status: 'syncing',
                updated_at: now,
            });
        }

        const receipt = await getFromStore('receipts', receiptId);
        if (receipt) {
            await putInStore('receipts', {
                ...receipt,
                sync_status: 'syncing',
                updated_at: now,
            });
        }
    }

    notifyReceiptsUpdated();
}

export async function markSynced(clientReceiptId, result) {
    const receipt = await getFromStore('receipts', clientReceiptId);
    if (!receipt) {
        return;
    }

    await putInStore('receipts', {
        ...receipt,
        sync_status: 'synced',
        server_transaction_id: result?.transaction_id ?? null,
        server_transaction_code: result?.transaction_code ?? null,
        updated_at: new Date().toISOString(),
    });
    await deleteFromStore('sync_queue', clientReceiptId);
    notifyReceiptsUpdated();
}

export async function markFailed(clientReceiptId, errorMessage) {
    const now = new Date().toISOString();
    const queueEntry = await getFromStore('sync_queue', clientReceiptId);
    const receipt = await getFromStore('receipts', clientReceiptId);

    if (queueEntry) {
        await putInStore('sync_queue', {
            ...queueEntry,
            status: 'failed',
            last_error: errorMessage || 'Sync failed',
            retry_count: Number(queueEntry.retry_count || 0) + 1,
            updated_at: now,
        });
    }

    if (receipt) {
        await putInStore('receipts', {
            ...receipt,
            sync_status: 'failed',
            sync_error: errorMessage || 'Sync failed',
            updated_at: now,
        });
    }

    notifyReceiptsUpdated();
}

export async function retrySync(clientReceiptId) {
    const now = new Date().toISOString();
    const queueEntry = await getFromStore('sync_queue', clientReceiptId);
    const receipt = await getFromStore('receipts', clientReceiptId);

    if (!receipt) {
        return false;
    }

    await putInStore('sync_queue', {
        id: clientReceiptId,
        receipt_id: clientReceiptId,
        status: 'pending',
        last_error: null,
        retry_count: Number(queueEntry?.retry_count || 0),
        created_at: queueEntry?.created_at ?? now,
        updated_at: now,
    });

    await putInStore('receipts', {
        ...receipt,
        sync_status: 'pending',
        sync_error: null,
        updated_at: now,
    });

    notifyReceiptsUpdated();
    return true;
}

export async function migrateLegacyCartState() {
    if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') {
        return;
    }

    const existingFlag = await getFromStore('meta', LEGACY_MIGRATION_META_KEY);
    if (existingFlag?.value === true) {
        return;
    }

    try {
        const raw = window.localStorage.getItem(LEGACY_CART_STORAGE_KEY);
        if (!raw) {
            await putInStore('meta', { key: LEGACY_MIGRATION_META_KEY, value: true });
            return;
        }

        const parsed = JSON.parse(raw);
        const itemsByReceipt = parsed?.itemsByReceipt || {};
        const migratedAt = new Date().toISOString();

        const migrationEntries = Object.entries(itemsByReceipt)
            .filter(([, items]) => Array.isArray(items) && items.length > 0)
            .filter(([receiptKey]) => !String(receiptKey).startsWith('transaction:'));

        for (const [receiptKey, items] of migrationEntries) {
            const suffix = String(receiptKey).replace('transaction-code:', '').replace('transaction:', '');
            const id = `temp:migrated-${suffix || Date.now()}`;
            const existing = await getFromStore('receipts', id);
            if (existing) {
                continue;
            }

            await putInStore('receipts', {
                id,
                transaction_id: suffix || id,
                state: 'open',
                sync_status: 'not_needed',
                is_local: true,
                customer: null,
                transaction_items: [],
                items: items,
                adjustment_type: null,
                adjustment_percent: 0,
                adjustment_amount: 0,
                subtotal: 0,
                total: 0,
                created_at: migratedAt,
                updated_at: migratedAt,
            });
        }
    } catch {
        // Ignore malformed legacy state; app remains usable.
    }

    await putInStore('meta', { key: LEGACY_MIGRATION_META_KEY, value: true });
    notifyReceiptsUpdated();
}

function notifyReceiptsUpdated() {
    if (typeof window === 'undefined') {
        return;
    }

    window.dispatchEvent(new CustomEvent('offline-receipts:updated'));
}

function byNewestCreatedAt(a, b) {
    return new Date(b?.created_at || 0).getTime() - new Date(a?.created_at || 0).getTime();
}

function byNewestUpdatedAt(a, b) {
    return new Date(b?.updated_at || 0).getTime() - new Date(a?.updated_at || 0).getTime();
}
