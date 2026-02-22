import axios from 'axios';
import {
    isOfflineReceiptsEnabled,
    listPendingSyncReceipts,
    listUnsyncedCompletedReceipts,
    markFailed,
    markSynced,
    markSyncing,
    migrateLegacyCartState,
} from '@/offline/receiptRepository';

const SYNC_INTERVAL_MS = 30000;
let syncInFlight = false;
let intervalId = null;

export function startOfflineSyncEngine() {
    if (!isOfflineReceiptsEnabled() || typeof window === 'undefined') {
        return () => {};
    }

    migrateLegacyCartState().catch(() => {});
    runSync().catch(() => {});

    const onlineHandler = () => {
        runSync().catch(() => {});
    };

    window.addEventListener('online', onlineHandler);
    intervalId = window.setInterval(() => {
        runSync().catch(() => {});
    }, SYNC_INTERVAL_MS);

    return () => {
        window.removeEventListener('online', onlineHandler);
        if (intervalId) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    };
}

export async function runSync() {
    if (!isOfflineReceiptsEnabled() || typeof window === 'undefined') {
        return;
    }

    if (!window.navigator.onLine || syncInFlight) {
        return;
    }

    syncInFlight = true;

    try {
        const pendingReceipts = await listPendingSyncReceipts();

        if (pendingReceipts.length === 0) {
            return;
        }

        const receiptIds = pendingReceipts.map((receipt) => receipt.id);
        await markSyncing(receiptIds);

        const payload = {
            receipts: pendingReceipts.map((receipt) => ({
                client_receipt_id: receipt.id,
                client_created_at: receipt.created_at ?? null,
                checkout_method: receipt.checkout_method,
                source_transaction_id: receipt.source_transaction_id ?? null,
                subtotal: receipt.subtotal,
                total: receipt.total,
                adjustment_type: receipt.adjustment_type ?? null,
                adjustment_percent: Number(receipt.adjustment_percent || 0),
                customer_ref: {
                    id: receipt.customer?.id ?? null,
                    name: receipt.customer?.display_name || receipt.customer?.company_name || null,
                },
                notes: receipt.notes ?? null,
                items: Array.isArray(receipt.items)
                    ? receipt.items.map((item) => ({
                        product_id: normalizeProductId(item.product_id ?? item.product?.id),
                        product_name: item.product?.name || 'Unknown product',
                        packages: Number(item.packages || 1),
                        quantity: Number(item.quantity || 1),
                        base_unit_price: Number(item.base_unit_price || item.unit_price || 0),
                        unit_price: Number(item.unit_price || 0),
                        vat_rate: Number(item.vat_rate || 0),
                        total: Number(item.total || 0),
                    }))
                    : [],
            })),
        };

        const { data } = await axios.post('/api/offline-receipts/sync', payload, {
            timeout: 10000,
        });
        const results = Array.isArray(data?.results) ? data.results : [];

        for (const result of results) {
            const clientReceiptId = result?.client_receipt_id;
            if (!clientReceiptId) {
                continue;
            }

            if (result.status === 'synced') {
                await markSynced(clientReceiptId, result);
            } else {
                await markFailed(clientReceiptId, result?.message || 'Sync rejected');
            }
        }

        const unmatchedIds = receiptIds.filter((id) => {
            return !results.some((result) => result?.client_receipt_id === id);
        });

        for (const receiptId of unmatchedIds) {
            await markFailed(receiptId, 'No sync result returned by server');
        }
    } catch {
        const unsyncedReceipts = await listUnsyncedCompletedReceipts();
        for (const receipt of unsyncedReceipts) {
            await markFailed(receipt.id, 'Network error during sync');
        }
    } finally {
        syncInFlight = false;
    }
}

function normalizeProductId(value) {
    const parsed = Number(value);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}
