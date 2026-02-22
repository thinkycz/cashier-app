<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useCartStore } from '@/stores/cart';
import {
    completeLocalReceipt,
    createLocalReceipt,
    deleteLocalReceipt,
    isOfflineReceiptsEnabled,
    listOpenLocalReceipts,
    listUnsyncedCompletedReceipts,
    retrySync,
    updateLocalReceipt,
} from '@/offline/receiptRepository';
import { runSync } from '@/offline/syncEngine';
import axios from 'axios';

const cart = useCartStore();
const DEFAULT_MANUAL_VAT_RATE = 21;

const props = defineProps({
    products: Array,
    openTransactions: Array,
    customers: Array,
});

const searchQuery = ref('');
const serverOpenReceipts = ref([...(props.openTransactions || [])]);
const localOpenReceipts = ref([]);
const syncQueueReceipts = ref([]);
const hiddenServerReceiptIds = ref([]);
const offlineEnabled = isOfflineReceiptsEnabled();
const isOnline = ref(typeof window !== 'undefined' ? window.navigator.onLine : true);
const isCreatingReceipt = ref(false);
const isCheckingOut = ref(false);
const deletingReceiptId = ref(null);
const manualProductName = ref('');
const manualPackages = ref(1);
const manualQuantity = ref(1);
const manualPrice = ref(0);
const selectedManualProduct = ref(null);
const autocompleteOpen = ref(false);
const highlightedSuggestionIndex = ref(-1);
const isManualProductInputFocused = ref(false);
const manualInputContainerRef = ref(null);
const productNameInputRef = ref(null);
const packagesInputRef = ref(null);
const quantityInputRef = ref(null);
const priceInputRef = ref(null);
const manualAutocompleteId = 'manual-product-suggestions';
const showAdjustmentModal = ref(false);
const adjustmentFormType = ref('discount');
const adjustmentFormPercent = ref(0);
const showCustomerModal = ref(false);
const customerOptions = ref([...(props.customers || [])]);
const customerSearch = ref('');
const selectedCustomerOption = ref(null);
const customerAutocompleteOpen = ref(false);
const highlightedCustomerSuggestionIndex = ref(-1);
const customerModalError = ref('');
const isSavingCustomer = ref(false);
const customerInputContainerRef = ref(null);
const isCustomerInputFocused = ref(false);
const customerAutocompleteId = 'customer-suggestions';
const showCheckoutModal = ref(false);
const selectedCheckoutMethod = ref(null);
const checkoutPaidAmount = ref(0);
const checkoutModalError = ref('');
const checkoutInfoMessage = ref('');
const cashPaidInputRef = ref(null);
const checkoutConfirmButtonRef = ref(null);
const showBillPreviewModal = ref(false);
const billPreviewUrl = ref('');
const embeddedBillPreviewUrl = ref('');
const billPreviewFrameRef = ref(null);

const openReceipts = computed(() => {
    const filteredServerReceipts = serverOpenReceipts.value.filter((receipt) => {
        return !hiddenServerReceiptIds.value.includes(receipt?.id);
    });
    const merged = [...filteredServerReceipts, ...localOpenReceipts.value];

    return merged.sort((left, right) => {
        return new Date(right?.created_at || 0).getTime() - new Date(left?.created_at || 0).getTime();
    });
});

const pendingSyncCount = computed(() => {
    return syncQueueReceipts.value.filter((receipt) => receipt?.sync_status === 'pending' || receipt?.sync_status === 'failed').length;
});

const normalizeQuery = (value) => String(value || '').trim().toLowerCase();

const manualProductSuggestions = computed(() => {
    const query = normalizeQuery(manualProductName.value);
    if (!query) return [];

    return (props.products || [])
        .filter((product) => {
            const name = normalizeQuery(product?.name);
            const shortName = normalizeQuery(product?.short_name);
            const ean = normalizeQuery(product?.ean);

            return name.includes(query) || shortName.includes(query) || ean.includes(query);
        })
        .slice(0, 8);
});

const showManualAutocomplete = computed(() => {
    return autocompleteOpen.value && isManualProductInputFocused.value && manualProductSuggestions.value.length > 0;
});

const customerSuggestions = computed(() => {
    const query = normalizeQuery(customerSearch.value);

    if (!query) {
        return customerOptions.value.slice(0, 8);
    }

    return customerOptions.value
        .filter((customer) => {
            const companyName = normalizeQuery(customer?.company_name);
            const companyId = normalizeQuery(customer?.company_id);

            return companyName.includes(query) || companyId.includes(query);
        })
        .slice(0, 8);
});

const showCustomerAutocomplete = computed(() => {
    return customerAutocompleteOpen.value && isCustomerInputFocused.value && customerSuggestions.value.length > 0;
});

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;

    const query = searchQuery.value.toLowerCase();
    return props.products.filter((product) => {
        const matchesName = product.name?.toLowerCase().includes(query);
        const matchesEan = product.ean?.toLowerCase().includes(query);
        return matchesName || matchesEan;
    });
});

const cartItemsNewestFirst = computed(() => {
    return [...cart.items].reverse();
});

const activeReceiptLabel = computed(() => {
    return cart.currentTransaction?.transaction_id || 'No active receipt';
});

const hasActiveAdjustment = computed(() => {
    return Boolean(cart.adjustment.type) && Number(cart.adjustment.percent || 0) > 0;
});

const adjustmentChipText = computed(() => {
    if (!hasActiveAdjustment.value) {
        return 'No adjustment';
    }

    const label = cart.adjustment.type === 'discount' ? 'Discount' : 'Surcharge';
    return `${label} ${formatPercent(cart.adjustment.percent)}`;
});

const addToCart = (product) => {
    selectManualProduct(product);
    manualPackages.value = 1;
    manualQuantity.value = 1;
    productNameInputRef.value?.focus();
    packagesInputRef.value?.focus();
};

const canAddManualItem = computed(() => {
    return Boolean(cart.currentTransaction) && manualProductName.value.trim().length > 0;
});

const addManualBillItem = () => {
    if (!canAddManualItem.value) {
        return;
    }

    const selectedProduct = selectedManualProduct.value;

    cart.addManualItem({
        productName: manualProductName.value,
        packages: manualPackages.value,
        quantity: manualQuantity.value,
        unitPrice: manualPrice.value,
        productId: selectedProduct?.id ?? null,
        vatRate: selectedProduct ? Number(selectedProduct.vat_rate || 0) : DEFAULT_MANUAL_VAT_RATE,
    });

    manualProductName.value = '';
    manualPackages.value = 1;
    manualQuantity.value = 1;
    manualPrice.value = 0;
    selectedManualProduct.value = null;
    closeManualAutocomplete();
    productNameInputRef.value?.focus();
};

const canCheckout = computed(() => {
    return Boolean(cart.currentTransaction) && cart.items.length > 0 && !isCheckingOut.value;
});

const checkoutTotal = computed(() => {
    return Number(cart.total || 0);
});

const isCashCheckout = computed(() => {
    return selectedCheckoutMethod.value === 'cash';
});

const parsedCheckoutPaidAmount = computed(() => {
    const numericValue = Number(checkoutPaidAmount.value);

    if (Number.isNaN(numericValue)) {
        return 0;
    }

    return numericValue;
});

const checkoutChangeAmount = computed(() => {
    return parsedCheckoutPaidAmount.value - checkoutTotal.value;
});

const checkoutWarningMessage = computed(() => {
    if (!isCashCheckout.value || parsedCheckoutPaidAmount.value >= checkoutTotal.value) {
        return '';
    }

    return `Paid amount is ${formatPrice(Math.abs(checkoutChangeAmount.value))} less than total.`;
});

const checkoutMethodLabel = computed(() => {
    switch (selectedCheckoutMethod.value) {
    case 'cash':
        return 'Hotove';
    case 'card':
        return 'Kartou';
    case 'order':
        return 'Objednavka';
    default:
        return '';
    }
});

const checkoutSubmitButtonLabel = computed(() => {
    switch (selectedCheckoutMethod.value) {
    case 'card':
        return 'Zaplatit kartou';
    case 'order':
        return 'Vystavit objednavku';
    default:
        return 'Vystavit uctenku';
    }
});

const canSubmitCheckout = computed(() => {
    return canCheckout.value && Boolean(selectedCheckoutMethod.value) && !isCheckingOut.value;
});

const focusPackagesInput = () => {
    packagesInputRef.value?.focus();
};

const focusQuantityInput = () => {
    quantityInputRef.value?.focus();
};

const focusPriceInput = () => {
    priceInputRef.value?.focus();
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK',
    }).format(price || 0);
};

const formatVat = (vatRate) => {
    return Number(vatRate || 0).toFixed(2);
};

const formatPercent = (percent) => {
    return `${Number(percent || 0).toFixed(2)}%`;
};

const productSubtitle = (product) => {
    return product.short_name || '';
};

const clampPercent = (value) => {
    const numericValue = Number(value || 0);
    if (Number.isNaN(numericValue)) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((numericValue + Number.EPSILON) * 100) / 100));
};

const openAdjustmentDialog = () => {
    const activeType = cart.adjustment.type || 'discount';
    const activePercent = clampPercent(cart.adjustment.percent || 0);

    adjustmentFormType.value = activeType;
    adjustmentFormPercent.value = activePercent;
    showAdjustmentModal.value = true;
};

const closeAdjustmentDialog = () => {
    showAdjustmentModal.value = false;
};

const applyAdjustment = () => {
    const normalizedPercent = clampPercent(adjustmentFormPercent.value);
    adjustmentFormPercent.value = normalizedPercent;

    if (normalizedPercent <= 0) {
        cart.clearAdjustment();
    } else {
        cart.setAdjustment({
            type: adjustmentFormType.value,
            percent: normalizedPercent,
        });
    }

    closeAdjustmentDialog();
};

const clearAdjustment = () => {
    cart.clearAdjustment();
    adjustmentFormPercent.value = 0;
    closeAdjustmentDialog();
};

const normalizeCompanyId = (value) => String(value || '').replace(/\D+/g, '');

const customerOptionLabel = (customer) => {
    if (!customer) return '';

    const name = customer.company_name || customerDisplayName(customer);
    const companyId = customer.company_id ? ` (${customer.company_id})` : '';

    return `${name}${companyId}`;
};

const appendOrReplaceCustomerOption = (customer) => {
    if (!customer?.id) return;

    const index = customerOptions.value.findIndex((option) => option.id === customer.id);
    if (index === -1) {
        customerOptions.value = [customer, ...customerOptions.value];
        return;
    }

    customerOptions.value[index] = customer;
};

const closeCustomerAutocomplete = () => {
    customerAutocompleteOpen.value = false;
    highlightedCustomerSuggestionIndex.value = -1;
};

const openCustomerAutocomplete = () => {
    customerAutocompleteOpen.value = true;
    highlightedCustomerSuggestionIndex.value = customerSuggestions.value.length > 0 ? 0 : -1;
};

const selectCustomerOption = (customer) => {
    selectedCustomerOption.value = customer;
    customerSearch.value = customerOptionLabel(customer);
    customerModalError.value = '';
    closeCustomerAutocomplete();
};

const openCustomerDialog = () => {
    if (!cart.currentTransaction) {
        return;
    }

    const currentCustomer = cart.selectedCustomer || cart.currentTransaction?.customer || null;
    selectedCustomerOption.value = currentCustomer;
    customerSearch.value = currentCustomer ? customerOptionLabel(currentCustomer) : '';
    customerModalError.value = '';
    showCustomerModal.value = true;
    openCustomerAutocomplete();
};

const closeCustomerDialog = () => {
    showCustomerModal.value = false;
    customerModalError.value = '';
    isSavingCustomer.value = false;
    closeCustomerAutocomplete();
};

const onCustomerInput = () => {
    if (
        selectedCustomerOption.value
        && normalizeQuery(customerSearch.value) !== normalizeQuery(customerOptionLabel(selectedCustomerOption.value))
    ) {
        selectedCustomerOption.value = null;
    }

    customerModalError.value = '';
    openCustomerAutocomplete();
};

const onCustomerFocus = () => {
    isCustomerInputFocused.value = true;
    openCustomerAutocomplete();
};

const onCustomerBlur = () => {
    isCustomerInputFocused.value = false;
    closeCustomerAutocomplete();
};

const onCustomerKeydown = (event) => {
    if (event.key === 'Tab') {
        closeCustomerAutocomplete();
        return;
    }

    if (event.key === 'Escape') {
        closeCustomerAutocomplete();
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (!customerAutocompleteOpen.value) {
            openCustomerAutocomplete();
            return;
        }

        const maxIndex = customerSuggestions.value.length - 1;
        if (maxIndex < 0) return;
        highlightedCustomerSuggestionIndex.value = Math.min(highlightedCustomerSuggestionIndex.value + 1, maxIndex);
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (!customerAutocompleteOpen.value) {
            openCustomerAutocomplete();
            return;
        }

        if (customerSuggestions.value.length === 0) return;
        highlightedCustomerSuggestionIndex.value = Math.max(highlightedCustomerSuggestionIndex.value - 1, 0);
        return;
    }

    if (event.key === 'Enter') {
        const hasHighlightedSuggestion = showCustomerAutocomplete.value
            && highlightedCustomerSuggestionIndex.value >= 0
            && highlightedCustomerSuggestionIndex.value < customerSuggestions.value.length;

        if (hasHighlightedSuggestion) {
            event.preventDefault();
            selectCustomerOption(customerSuggestions.value[highlightedCustomerSuggestionIndex.value]);
            return;
        }

        event.preventDefault();
        saveSelectedCustomer();
    }
};

const saveSelectedCustomer = async () => {
    const activeTransaction = cart.currentTransaction;
    if (!activeTransaction?.id) {
        customerModalError.value = 'Please select an active receipt first.';
        return;
    }

    if (isLocalTransaction(activeTransaction)) {
        customerModalError.value = 'Customer assignment requires an online receipt.';
        return;
    }

    if (isSavingCustomer.value) {
        return;
    }

    const payload = {};
    const normalizedIco = normalizeCompanyId(customerSearch.value);

    if (selectedCustomerOption.value?.id) {
        payload.customer_id = selectedCustomerOption.value.id;
    } else if (/^\d{8}$/.test(normalizedIco)) {
        payload.company_id = normalizedIco;
    } else {
        customerModalError.value = 'Select an existing customer or enter a valid 8-digit IČO.';
        return;
    }

    isSavingCustomer.value = true;
    customerModalError.value = '';

    try {
        const { data } = await axios.patch(route('dashboard.receipts.customer', activeTransaction.id), payload);
        syncOpenReceiptsFromResponse(data);

        if (data?.customer) {
            appendOrReplaceCustomerOption(data.customer);
            cart.setCustomer(data.customer);
        } else {
            cart.setCustomer(null);
        }

        closeCustomerDialog();
    } catch (error) {
        if (error?.response?.status === 422) {
            const responseMessage = error?.response?.data?.message;
            const firstValidationMessage = Object.values(error?.response?.data?.errors || {})[0]?.[0];
            customerModalError.value = responseMessage || firstValidationMessage || 'Unable to select customer.';
            return;
        }

        customerModalError.value = 'Unable to connect. Please try again.';
    } finally {
        isSavingCustomer.value = false;
    }
};

const removeSelectedCustomer = async () => {
    const activeTransaction = cart.currentTransaction;
    if (!activeTransaction?.id) {
        customerModalError.value = 'Please select an active receipt first.';
        return;
    }

    if (isLocalTransaction(activeTransaction)) {
        customerModalError.value = 'Customer assignment requires an online receipt.';
        return;
    }

    if (isSavingCustomer.value) {
        return;
    }

    isSavingCustomer.value = true;
    customerModalError.value = '';

    try {
        const { data } = await axios.patch(route('dashboard.receipts.customer', activeTransaction.id), {
            clear_customer: true,
        });

        syncOpenReceiptsFromResponse(data);
        cart.setCustomer(null);
        selectedCustomerOption.value = null;
        customerSearch.value = '';
        closeCustomerDialog();
    } catch (error) {
        if (error?.response?.status === 422) {
            customerModalError.value = error?.response?.data?.message || 'Unable to remove customer.';
            return;
        }

        customerModalError.value = 'Unable to connect. Please try again.';
    } finally {
        isSavingCustomer.value = false;
    }
};

const closeManualAutocomplete = () => {
    autocompleteOpen.value = false;
    highlightedSuggestionIndex.value = -1;
};

const openManualAutocomplete = () => {
    autocompleteOpen.value = true;
    highlightedSuggestionIndex.value = manualProductSuggestions.value.length > 0 ? 0 : -1;
};

const selectManualProduct = (product) => {
    if (!product) return;

    selectedManualProduct.value = {
        id: Number(product.id),
        name: product.name || '',
        vat_rate: Number(product.vat_rate || 0),
        price: Number(product.price || 0),
    };
    manualProductName.value = product.name || '';
    manualPrice.value = Number(product.price || 0);
    closeManualAutocomplete();
};

const onManualProductInput = () => {
    if (
        selectedManualProduct.value
        && normalizeQuery(manualProductName.value) !== normalizeQuery(selectedManualProduct.value.name)
    ) {
        selectedManualProduct.value = null;
    }

    autocompleteOpen.value = true;
    highlightedSuggestionIndex.value = manualProductSuggestions.value.length > 0 ? 0 : -1;
};

const onManualProductFocus = () => {
    isManualProductInputFocused.value = true;

    if (manualProductSuggestions.value.length > 0) {
        openManualAutocomplete();
    }
};

const onManualProductBlur = () => {
    isManualProductInputFocused.value = false;
    closeManualAutocomplete();
};

const onManualProductKeydown = (event) => {
    if (event.key === 'Tab') {
        closeManualAutocomplete();
        return;
    }

    if (event.key === 'Escape') {
        closeManualAutocomplete();
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();

        if (!autocompleteOpen.value) {
            openManualAutocomplete();
            return;
        }

        const maxIndex = manualProductSuggestions.value.length - 1;
        if (maxIndex < 0) return;
        highlightedSuggestionIndex.value = Math.min(highlightedSuggestionIndex.value + 1, maxIndex);
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();

        if (!autocompleteOpen.value) {
            openManualAutocomplete();
            return;
        }

        if (manualProductSuggestions.value.length === 0) return;
        highlightedSuggestionIndex.value = Math.max(highlightedSuggestionIndex.value - 1, 0);
        return;
    }

    if (event.key === 'Enter') {
        const hasHighlightedSuggestion = showManualAutocomplete.value
            && highlightedSuggestionIndex.value >= 0
            && highlightedSuggestionIndex.value < manualProductSuggestions.value.length;

        if (hasHighlightedSuggestion) {
            event.preventDefault();
            selectManualProduct(manualProductSuggestions.value[highlightedSuggestionIndex.value]);
            return;
        }

        event.preventDefault();
        focusPackagesInput();
    }
};

const customerDisplayName = (customer) => {
    if (!customer) return 'No customer selected';

    const fullName = [customer.first_name, customer.last_name].filter(Boolean).join(' ').trim();
    return fullName || customer.company_name || 'No customer selected';
};

const handleDocumentClick = (event) => {
    if (!manualInputContainerRef.value?.contains(event.target)) {
        closeManualAutocomplete();
    }

    if (!customerInputContainerRef.value?.contains(event.target)) {
        closeCustomerAutocomplete();
    }
};

const isLocalTransaction = (transaction) => {
    return String(transaction?.id || '').startsWith('temp:') || Boolean(transaction?.is_local);
};

const cloneCartItemsPayload = () => {
    return cart.items.map((item) => {
        const parsedProductId = Number(item.product_id ?? item.product?.id);
        const productId = Number.isInteger(parsedProductId) && parsedProductId > 0 ? parsedProductId : null;

        return {
            line_id: item.line_id,
            product_id: productId,
            product: item.product ? { ...item.product } : null,
            packages: Number(item.packages || 1),
            quantity: Number(item.quantity || 0),
            base_unit_price: Number(item.base_unit_price || item.unit_price || 0),
            unit_price: Number(item.unit_price || 0),
            vat_rate: Number(item.vat_rate ?? DEFAULT_MANUAL_VAT_RATE),
            total: Number(item.total || 0),
        };
    });
};

const buildLocalReceiptSnapshot = (transaction, overrides = {}) => {
    const now = new Date().toISOString();

    return {
        ...transaction,
        id: transaction?.id,
        transaction_id: transaction?.transaction_id || `OFF${Date.now()}`,
        customer: cart.selectedCustomer ? { ...cart.selectedCustomer } : null,
        adjustment_type: transaction?.adjustment_type || null,
        adjustment_percent: Number(transaction?.adjustment_percent || 0),
        adjustment_amount: Number(transaction?.adjustment_amount || 0),
        subtotal: Number(cart.subtotal || 0),
        total: Number(cart.total || 0),
        items: cloneCartItemsPayload(),
        is_local: true,
        updated_at: now,
        ...overrides,
    };
};

const createCompletedLocalReceiptFromServerTransaction = async (serverTransaction, checkoutMethod) => {
    const itemsSnapshot = cloneCartItemsPayload();
    const customerSnapshot = cart.selectedCustomer ? { ...cart.selectedCustomer } : null;
    const subtotalSnapshot = Number(cart.subtotal || 0);
    const totalSnapshot = Number(cart.total || 0);
    const adjustmentTypeSnapshot = serverTransaction?.adjustment_type || null;
    const adjustmentPercentSnapshot = Number(serverTransaction?.adjustment_percent || 0);
    const adjustmentAmountSnapshot = Number(serverTransaction?.adjustment_amount || 0);

    const temporaryLocalTransaction = cart.createLocalTransactionShell();
    const completedLocalReceipt = buildLocalReceiptSnapshot(temporaryLocalTransaction, {
        customer: customerSnapshot,
        adjustment_type: adjustmentTypeSnapshot,
        adjustment_percent: adjustmentPercentSnapshot,
        adjustment_amount: adjustmentAmountSnapshot,
        subtotal: subtotalSnapshot,
        total: totalSnapshot,
        items: itemsSnapshot,
        checkout_method: checkoutMethod,
        state: 'completed',
        sync_status: 'pending',
        status: checkoutMethod,
        completed_at: new Date().toISOString(),
        source_transaction_id: Number(serverTransaction?.id || 0) || null,
        source_transaction_code: serverTransaction?.transaction_id || null,
    });

    await createLocalReceipt({
        ...completedLocalReceipt,
        state: 'open',
        sync_status: 'not_needed',
        status: 'open',
    });

    await completeLocalReceipt(temporaryLocalTransaction.id, completedLocalReceipt);
    hiddenServerReceiptIds.value = [
        ...hiddenServerReceiptIds.value.filter((id) => id !== serverTransaction?.id),
        serverTransaction?.id,
    ].filter(Boolean);
};

const refreshLocalReceiptViews = async () => {
    if (!offlineEnabled) {
        localOpenReceipts.value = [];
        syncQueueReceipts.value = [];
        return;
    }

    localOpenReceipts.value = await listOpenLocalReceipts();
    syncQueueReceipts.value = await listUnsyncedCompletedReceipts();
};

const persistActiveLocalReceipt = async () => {
    const currentTransaction = cart.currentTransaction;
    if (!currentTransaction || !isLocalTransaction(currentTransaction)) {
        return;
    }

    if (currentTransaction.state === 'completed') {
        return;
    }

    const snapshot = buildLocalReceiptSnapshot(currentTransaction, {
        state: 'open',
        sync_status: currentTransaction?.sync_status || 'not_needed',
        status: 'open',
    });

    await updateLocalReceipt(currentTransaction.id, snapshot);
};

const createNewTransaction = async () => {
    if (isCreatingReceipt.value) {
        return;
    }

    isCreatingReceipt.value = true;

    try {
        if (isOnline.value) {
            const { data } = await axios.post(route('dashboard.receipts.store'), {}, { timeout: 5000 });
            syncOpenReceiptsFromResponse(data);
            return;
        }

        throw new Error('offline');
    } catch {
        if (!offlineEnabled) {
            return;
        }

        const transaction = cart.createLocalTransactionShell();
        await createLocalReceipt(buildLocalReceiptSnapshot(transaction, {
            state: 'open',
            sync_status: 'not_needed',
            status: 'open',
            created_at: transaction.created_at,
        }));
        await refreshLocalReceiptViews();
        cart.setTransaction(transaction);
    } finally {
        isCreatingReceipt.value = false;
    }
};

const resetCheckoutModalState = () => {
    selectedCheckoutMethod.value = null;
    checkoutPaidAmount.value = 0;
    checkoutModalError.value = '';
};

const closeCheckoutModal = () => {
    showCheckoutModal.value = false;
    resetCheckoutModalState();
};

const closeBillPreviewModal = () => {
    showBillPreviewModal.value = false;
};

const openBillPreviewModal = (transactionId) => {
    if (!transactionId) {
        return;
    }

    const previewUrl = route('bills.preview', transactionId);
    billPreviewUrl.value = previewUrl;
    embeddedBillPreviewUrl.value = `${previewUrl}?embedded=1`;
    showBillPreviewModal.value = true;
};

const printBillFromPreviewFrame = () => {
    const frameWindow = billPreviewFrameRef.value?.contentWindow;

    if (frameWindow) {
        frameWindow.focus();
        frameWindow.print();
    }
};

const openPreviewInNewWindow = () => {
    if (!billPreviewUrl.value) {
        return;
    }

    window.open(billPreviewUrl.value, '_blank', 'noopener');
};

const openCheckoutModal = async (checkoutMethod) => {
    if (!canCheckout.value) {
        return;
    }

    checkoutInfoMessage.value = '';
    selectedCheckoutMethod.value = checkoutMethod;
    checkoutPaidAmount.value = checkoutTotal.value;
    checkoutModalError.value = '';
    showCheckoutModal.value = true;

    await nextTick();

    if (checkoutMethod === 'cash') {
        cashPaidInputRef.value?.focus();
        cashPaidInputRef.value?.select?.();
        return;
    }

    checkoutConfirmButtonRef.value?.focus();
};

const performCheckout = async (checkoutMethod) => {
    if (!canCheckout.value) {
        return {
            previewable: false,
            transactionId: null,
            offlineCompleted: false,
        };
    }

    const activeTransaction = cart.currentTransaction;
    if (!activeTransaction?.id) {
        return {
            previewable: false,
            transactionId: null,
            offlineCompleted: false,
        };
    }

    isCheckingOut.value = true;

    try {
        if (isLocalTransaction(activeTransaction)) {
            await completeLocalReceipt(activeTransaction.id, buildLocalReceiptSnapshot(activeTransaction, {
                checkout_method: checkoutMethod,
                state: 'completed',
                sync_status: 'pending',
                status: checkoutMethod,
                completed_at: new Date().toISOString(),
            }));

            cart.clearTransactionItems(activeTransaction);
            await refreshLocalReceiptViews();

            if (openReceipts.value.length > 0) {
                cart.setTransaction(openReceipts.value[0]);
            } else {
                cart.setTransaction(null);
            }

            if (isOnline.value) {
                await runSync();
            }

            return {
                previewable: false,
                transactionId: null,
                offlineCompleted: true,
            };
        }

        const { data } = await axios.patch(route('dashboard.receipts.checkout', activeTransaction.id), {
            checkout_method: checkoutMethod,
            subtotal: cart.subtotal,
            total: cart.total,
            adjustment_type: activeTransaction.adjustment_type || null,
            adjustment_percent: Number(activeTransaction.adjustment_percent || 0),
            items: cloneCartItemsPayload().map((item) => ({
                product_id: item.product_id,
                product_name: item.product?.name || 'Unknown product',
                packages: item.packages,
                quantity: item.quantity,
                base_unit_price: item.base_unit_price,
                unit_price: item.unit_price,
                vat_rate: item.vat_rate,
                total: item.total,
            })),
        });
        cart.clearTransactionItems(activeTransaction);
        syncOpenReceiptsFromResponse(data);

        return {
            previewable: Boolean(data?.transaction?.id),
            transactionId: Number(data?.transaction?.id) || null,
            offlineCompleted: false,
        };
    } catch (error) {
        const canFallbackToOffline = offlineEnabled && (!isOnline.value || !error?.response);

        if (!canFallbackToOffline) {
            throw error;
        }

        await createCompletedLocalReceiptFromServerTransaction(activeTransaction, checkoutMethod);
        cart.clearTransactionItems(activeTransaction);
        await refreshLocalReceiptViews();

        if (openReceipts.value.length > 0) {
            cart.setTransaction(openReceipts.value[0]);
        } else {
            cart.setTransaction(null);
        }

        if (isOnline.value) {
            await runSync();
        }

        return {
            previewable: false,
            transactionId: null,
            offlineCompleted: true,
        };
    } finally {
        isCheckingOut.value = false;
    }
};

const submitCheckout = async () => {
    if (!canSubmitCheckout.value) {
        return;
    }

    const activeTransaction = cart.currentTransaction;
    if (!activeTransaction?.id || cart.items.length === 0) {
        checkoutModalError.value = 'Please select an active receipt with at least one item.';
        return;
    }

    const checkoutMethod = selectedCheckoutMethod.value;
    if (!checkoutMethod) {
        checkoutModalError.value = 'Select a checkout method and try again.';
        return;
    }

    checkoutModalError.value = '';

    try {
        const checkoutResult = await performCheckout(checkoutMethod);
        closeCheckoutModal();

        if (checkoutResult?.previewable && checkoutResult?.transactionId) {
            await nextTick();
            openBillPreviewModal(checkoutResult.transactionId);
            return;
        }

        if (checkoutResult?.offlineCompleted) {
            checkoutInfoMessage.value = 'Receipt was completed locally. Bill preview will be available after sync.';
        }
    } catch {
        checkoutModalError.value = 'Unable to checkout. Please try again.';
    }
};

const setActiveReceipt = (transaction) => {
    cart.setTransaction(transaction);
};

const isActiveReceipt = (transaction) => {
    return cart.currentTransaction?.id === transaction.id;
};

const receiptDisplayTotal = (transaction) => {
    return cart.getReceiptTotal(transaction);
};

const receiptSyncLabel = (transaction) => {
    if (!isLocalTransaction(transaction)) {
        return 'Synced';
    }

    switch (transaction?.sync_status) {
    case 'pending':
        return 'Pending sync';
    case 'syncing':
        return 'Syncing';
    case 'failed':
        return 'Sync failed';
    case 'synced':
        return 'Synced';
    default:
        return 'Local draft';
    }
};

const receiptStatusDotClass = (transaction) => {
    if (!isLocalTransaction(transaction) || transaction?.sync_status === 'synced') {
        return 'bg-emerald-500';
    }

    if (transaction?.sync_status === 'failed') {
        return 'bg-rose-500';
    }

    if (
        transaction?.sync_status === 'pending'
        || transaction?.sync_status === 'syncing'
        || transaction?.sync_status === 'not_needed'
        || transaction?.state === 'open'
    ) {
        return 'bg-amber-500';
    }

    return 'bg-amber-500';
};

const syncOpenReceiptsFromResponse = (data) => {
    const openTransactions = Array.isArray(data?.open_transactions) ? data.open_transactions : [];
    serverOpenReceipts.value = openTransactions;

    const activeTransaction = openTransactions.find(
        (transaction) => transaction.id === data?.active_transaction_id,
    ) || cart.currentTransaction || openReceipts.value[0] || null;

    cart.setTransaction(activeTransaction);
};

const deleteReceipt = async (transaction) => {
    if (!transaction?.id || deletingReceiptId.value === transaction.id) {
        return;
    }

    deletingReceiptId.value = transaction.id;

    try {
        if (isLocalTransaction(transaction)) {
            await deleteLocalReceipt(transaction.id);
            cart.clearTransactionItems(transaction);
            await refreshLocalReceiptViews();

            if (!cart.currentTransaction && openReceipts.value.length > 0) {
                cart.setTransaction(openReceipts.value[0]);
            }

            return;
        }

        const { data } = await axios.delete(route('dashboard.receipts.destroy', transaction.id));
        cart.clearTransactionItems(transaction);
        syncOpenReceiptsFromResponse(data);
    } finally {
        deletingReceiptId.value = null;
    }
};

const retryFailedSync = async (receiptId) => {
    const queued = await retrySync(receiptId);
    if (!queued) {
        return;
    }

    await refreshLocalReceiptViews();
    if (isOnline.value) {
        await runSync();
    }
};

const connectionChanged = () => {
    isOnline.value = window.navigator.onLine;
    if (isOnline.value) {
        runSync().catch(() => {});
    }
};

const refreshFromOfflineEvent = () => {
    refreshLocalReceiptViews().catch(() => {});
};

watch(() => ({
    id: cart.currentTransaction?.id || null,
    subtotal: cart.subtotal,
    total: cart.total,
    adjustmentType: cart.currentTransaction?.adjustment_type || null,
    adjustmentPercent: Number(cart.currentTransaction?.adjustment_percent || 0),
    customerId: cart.selectedCustomer?.id || null,
    itemSignature: JSON.stringify(cart.items.map((item) => ({
        line_id: item.line_id,
        product_id: item.product_id ?? item.product?.id,
        packages: item.packages,
        quantity: item.quantity,
        base_unit_price: item.base_unit_price,
        unit_price: item.unit_price,
        vat_rate: item.vat_rate,
        total: item.total,
    }))),
}), () => {
    persistActiveLocalReceipt().catch(() => {});
});

onMounted(async () => {
    await refreshLocalReceiptViews();

    if (cart.currentTransaction) {
        cart.loadTransactionByIdentity(
            cart.currentTransaction.id || cart.currentTransaction.transaction_id,
            openReceipts.value,
        );
    }

    if (openReceipts.value.length > 0 && !cart.currentTransaction) {
        cart.setTransaction(openReceipts.value[0]);
    }

    document.addEventListener('click', handleDocumentClick);
    window.addEventListener('online', connectionChanged);
    window.addEventListener('offline', connectionChanged);
    window.addEventListener('offline-receipts:updated', refreshFromOfflineEvent);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick);
    window.removeEventListener('online', connectionChanged);
    window.removeEventListener('offline', connectionChanged);
    window.removeEventListener('offline-receipts:updated', refreshFromOfflineEvent);
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">{{ activeReceiptLabel }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ customerDisplayName(cart.selectedCustomer) }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                            <span
                                class="h-2.5 w-2.5 rounded-full"
                                :class="isOnline ? 'bg-emerald-500' : 'bg-amber-500'"
                            ></span>
                            {{ isOnline ? 'Online mode' : 'Offline mode' }}
                        </span>
                        <span
                            v-if="offlineEnabled && pendingSyncCount > 0"
                            class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
                        >
                            {{ pendingSyncCount }} pending sync
                        </span>
                    </div>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <button
                        type="button"
                        :disabled="!cart.currentTransaction"
                        class="inline-flex items-center justify-center rounded-md border border-teal-100 bg-teal-50/60 px-4 py-2 text-sm font-medium text-teal-700 transition-all duration-200 hover:-translate-y-px hover:bg-teal-100/70 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openAdjustmentDialog"
                    >
                        Discount / Surcharge
                    </button>
                    <button
                        type="button"
                        :disabled="!cart.currentTransaction"
                        class="inline-flex items-center justify-center rounded-md border border-teal-100 bg-teal-50/60 px-4 py-2 text-sm font-medium text-teal-700 transition-all duration-200 hover:-translate-y-px hover:bg-teal-100/70 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openCustomerDialog"
                    >
                        Select Customer
                    </button>
                    <button
                        type="button"
                        :disabled="isCreatingReceipt"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="createNewTransaction"
                    >
                        {{ isCreatingReceipt ? 'Creating...' : 'New Receipt' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="relative py-6">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 sm:px-6 lg:grid-cols-[22rem_minmax(0,1fr)] lg:px-8">
                <section class="flex h-full flex-col overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/60">
                    <div class="bg-gradient-to-r from-teal-700 to-cyan-700 px-5 py-4 text-white">
                        <p class="text-xs uppercase tracking-wide text-cyan-100">Current Total</p>
                        <p class="mt-1 text-3xl font-semibold">{{ formatPrice(cart.total) }}</p>
                        <p
                            class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="hasActiveAdjustment ? (cart.adjustment.type === 'discount' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800') : 'bg-cyan-100/30 text-cyan-100'"
                        >
                            {{ adjustmentChipText }}
                        </p>
                    </div>

                    <div class="flex min-h-0 flex-1 flex-col">
                        <div class="space-y-3 px-4 pt-4">
                            <div ref="manualInputContainerRef" class="relative">
                                <label class="mb-1.5 block text-xs font-medium text-slate-600">Product Name</label>
                                <input
                                    ref="productNameInputRef"
                                    v-model="manualProductName"
                                    type="text"
                                    placeholder="Product Name"
                                    role="combobox"
                                    autocomplete="off"
                                    :aria-expanded="showManualAutocomplete"
                                    :aria-controls="manualAutocompleteId"
                                    :aria-activedescendant="highlightedSuggestionIndex >= 0 ? `manual-product-suggestion-${manualProductSuggestions[highlightedSuggestionIndex]?.id}` : undefined"
                                    class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                    @input="onManualProductInput"
                                    @focus="onManualProductFocus"
                                    @blur="onManualProductBlur"
                                    @keydown="onManualProductKeydown"
                                />

                                <div
                                    v-if="showManualAutocomplete"
                                    :id="manualAutocompleteId"
                                    role="listbox"
                                    class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
                                >
                                    <button
                                        v-for="(product, suggestionIndex) in manualProductSuggestions"
                                        :id="`manual-product-suggestion-${product.id}`"
                                        :key="product.id"
                                        type="button"
                                        role="option"
                                        :aria-selected="highlightedSuggestionIndex === suggestionIndex"
                                        class="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-3 py-2 text-left last:border-b-0"
                                        :class="highlightedSuggestionIndex === suggestionIndex ? 'bg-teal-50' : 'bg-white hover:bg-slate-50'"
                                        @mousedown.prevent="selectManualProduct(product)"
                                        @mousemove="highlightedSuggestionIndex = suggestionIndex"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-slate-900">{{ product.name }}</p>
                                            <p v-if="product.short_name" class="truncate text-xs text-slate-500">{{ product.short_name }}</p>
                                            <p v-if="product.ean" class="truncate text-xs font-mono text-slate-500">{{ product.ean }}</p>
                                        </div>
                                        <p class="shrink-0 text-sm font-semibold text-slate-900">{{ formatPrice(product.price) }}</p>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] items-end gap-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Packages</label>
                                    <input
                                        ref="packagesInputRef"
                                        v-model.number="manualPackages"
                                        type="number"
                                        min="1"
                                        class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                        @keydown.enter.prevent="focusQuantityInput"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Quantity</label>
                                    <input
                                        ref="quantityInputRef"
                                        v-model.number="manualQuantity"
                                        type="number"
                                        min="1"
                                        class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                        @keydown.enter.prevent="focusPriceInput"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Base Price</label>
                                    <input
                                        ref="priceInputRef"
                                        v-model.number="manualPrice"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                        @keydown.enter.prevent="addManualBillItem"
                                    />
                                </div>
                                <div class="flex items-end">
                                <button
                                    type="button"
                                    :disabled="!canAddManualItem"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-transparent bg-teal-600 text-white hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="addManualBillItem"
                                    aria-label="Add Item"
                                    title="Add Item"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M10 4a.75.75 0 01.75.75v4.5h4.5a.75.75 0 010 1.5h-4.5v4.5a.75.75 0 01-1.5 0v-4.5h-4.5a.75.75 0 010-1.5h4.5v-4.5A.75.75 0 0110 4z" />
                                    </svg>
                                </button>
                            </div>
                            </div>
                        </div>

                        <div class="mt-4 px-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-teal-700/80">Bill Items</h4>
                        </div>

                        <div class="mt-3 min-h-0 flex-1 max-h-[34rem] space-y-3 overflow-y-auto px-4 pb-4">
                            <article v-if="cart.items.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-slate-50/60 px-4 py-8 text-center text-sm text-slate-500">
                                Cart is empty
                            </article>

                            <article
                                v-for="(item, index) in cartItemsNewestFirst"
                                :key="item.line_id || `${item.product?.id}-${index}`"
                                class="min-h-24 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm shadow-slate-100/70"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ item.product?.name || 'Unknown product' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">Line #{{ cart.items.length - index }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-900">{{ formatPrice(item.total) }}</p>
                                </div>

                                <div class="mt-3 grid grid-cols-3 gap-3 text-xs text-slate-600">
                                    <div>
                                        <p class="text-slate-500">Packages</p>
                                        <p class="mt-1 font-medium text-slate-900">{{ item.packages || 1 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Qty</p>
                                        <p class="mt-1 font-medium text-slate-900">{{ item.quantity }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Unit</p>
                                        <p class="mt-1 font-medium text-slate-900">{{ formatPrice(item.unit_price) }}</p>
                                        <p
                                            v-if="hasActiveAdjustment"
                                            class="mt-0.5 text-[11px] text-slate-500"
                                        >
                                            Base {{ formatPrice(item.base_unit_price) }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div
                            v-if="cart.items.length > 0"
                            class="border-t border-slate-200/80 bg-white/80 px-4 pb-4 pt-3 shadow-[0_-6px_14px_-12px_rgba(15,23,42,0.45)]"
                        >
                            <div class="grid grid-cols-3 gap-3">
                            <button
                                type="button"
                                :disabled="!canCheckout"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="openCheckoutModal('cash')"
                            >
                                Hotove
                            </button>
                            <button
                                type="button"
                                :disabled="!canCheckout"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="openCheckoutModal('card')"
                            >
                                Kartou
                            </button>
                            <button
                                type="button"
                                :disabled="!canCheckout"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="openCheckoutModal('order')"
                            >
                                Objednavka
                            </button>
                            </div>
                            <div
                                v-if="checkoutInfoMessage"
                                class="mt-3 flex items-start justify-between gap-3 rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-800"
                            >
                                <p>{{ checkoutInfoMessage }}</p>
                                <button
                                    type="button"
                                    class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-sky-700 transition-colors hover:bg-sky-100"
                                    aria-label="Dismiss checkout info"
                                    @click="checkoutInfoMessage = ''"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/70 to-cyan-50/60 px-4 py-3">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-teal-700/80">Open Receipts</h4>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-slate-600">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    Synced
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                    Local / Pending
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                                    Failed
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2">
                            <article
                                v-for="transaction in openReceipts"
                                :key="transaction.id"
                                class="relative flex min-w-0 items-stretch rounded-lg border bg-white shadow-sm transition-all"
                                :class="isActiveReceipt(transaction)
                                    ? 'border-teal-300 shadow-teal-100'
                                    : 'border-slate-200 hover:border-slate-300'"
                            >
                                <button
                                    type="button"
                                    class="inline-flex min-w-0 flex-1 items-center gap-3 overflow-hidden rounded-l-lg text-left"
                                    @click="setActiveReceipt(transaction)"
                                >
                                    <div
                                        class="flex h-16 w-14 shrink-0 items-center justify-center rounded-l-lg text-white"
                                        :class="isActiveReceipt(transaction) ? 'bg-teal-600' : 'bg-slate-500'"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M4.5 2.5A1.5 1.5 0 0 0 3 4v12a1.5 1.5 0 0 0 1.5 1.5h11A1.5 1.5 0 0 0 17 16V7.207a1.5 1.5 0 0 0-.44-1.06l-2.707-2.707A1.5 1.5 0 0 0 12.793 3H4.5Zm2.25 5a.75.75 0 0 1 .75-.75h5a.75.75 0 0 1 0 1.5h-5a.75.75 0 0 1-.75-.75Zm0 3a.75.75 0 0 1 .75-.75h5a.75.75 0 0 1 0 1.5h-5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 py-3">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span
                                                class="inline-flex h-2.5 w-2.5 shrink-0 rounded-full"
                                                :class="receiptStatusDotClass(transaction)"
                                                :title="receiptSyncLabel(transaction)"
                                                :aria-label="receiptSyncLabel(transaction)"
                                            ></span>
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ transaction.transaction_id }}</p>
                                        </div>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ formatPrice(receiptDisplayTotal(transaction)) }}</p>
                                    </div>
                                </button>

                                <div class="flex shrink-0 items-center rounded-r-lg px-2" @click.stop>
                                    <Dropdown align="right" width="48" content-classes="py-1 bg-white">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-transparent text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
                                                :title="`Receipt actions for ${transaction.transaction_id}`"
                                                :aria-label="`Receipt actions for ${transaction.transaction_id}`"
                                            >
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path d="M10 4.75a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5ZM10 11.25a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5ZM8.75 16a1.25 1.25 0 1 1 2.5 0 1.25 1.25 0 0 1-2.5 0Z" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <button
                                                v-if="!isActiveReceipt(transaction)"
                                                type="button"
                                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-slate-700 transition duration-150 ease-in-out hover:bg-teal-50 hover:text-teal-700 focus:bg-teal-50 focus:text-teal-700 focus:outline-none"
                                                @click="setActiveReceipt(transaction)"
                                            >
                                                Make Active
                                            </button>
                                            <button
                                                type="button"
                                                :disabled="deletingReceiptId === transaction.id"
                                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-rose-600 transition duration-150 ease-in-out hover:bg-rose-50 hover:text-rose-700 focus:bg-rose-50 focus:text-rose-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                                @click="deleteReceipt(transaction)"
                                            >
                                                {{ deletingReceiptId === transaction.id ? 'Deleting...' : 'Delete Bill' }}
                                            </button>
                                        </template>
                                    </Dropdown>
                                </div>
                            </article>
                            <p v-if="openReceipts.length === 0" class="text-sm text-slate-500">No open receipts.</p>
                        </div>
                    </div>

                    <div
                        v-if="offlineEnabled && syncQueueReceipts.length > 0"
                        class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-4"
                    >
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-amber-700">Offline Sync Queue</h4>
                        <div class="mt-3 space-y-2">
                            <article
                                v-for="receipt in syncQueueReceipts"
                                :key="receipt.id"
                                class="flex items-center justify-between gap-3 rounded-lg border border-amber-200/80 bg-white px-3 py-2"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ receipt.transaction_id || receipt.id }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ receiptSyncLabel(receipt) }}</p>
                                    <p v-if="receipt.sync_error" class="mt-0.5 text-xs text-rose-600">{{ receipt.sync_error }}</p>
                                </div>
                                <button
                                    v-if="receipt.sync_status === 'failed'"
                                    type="button"
                                    class="inline-flex shrink-0 items-center justify-center rounded-md border border-amber-300 bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-200"
                                    @click="retryFailedSync(receipt.id)"
                                >
                                    Retry
                                </button>
                            </article>
                        </div>
                    </div>

                    <div class="rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/70 to-cyan-50/60 px-4 py-3">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-teal-700/80">Find Product</h4>
                        </div>

                        <div class="space-y-4">
                            <div class="flex flex-col gap-3 px-4 pt-4 md:flex-row md:items-center">
                                <div class="relative flex-1">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search products"
                                        class="h-10 w-full rounded-md border border-slate-300 pl-10 pr-3 text-sm text-slate-700 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                                    />
                                </div>

                                <Link
                                    :href="route('products.create')"
                                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700"
                                >
                                    Create Product
                                </Link>
                            </div>

                            <div class="hidden overflow-x-auto lg:block">
                                <table class="min-w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-teal-50/70 to-cyan-50/60">
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Product</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">EAN</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">VAT</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Price</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="product in filteredProducts"
                                            :key="product.id"
                                            class="border-t border-slate-100 transition-colors hover:bg-slate-50"
                                        >
                                            <td class="px-4 py-3 align-top">
                                                <p class="text-sm font-semibold text-slate-900">{{ product.name }}</p>
                                                <p v-if="productSubtitle(product)" class="mt-0.5 text-xs text-slate-500">{{ productSubtitle(product) }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-xs font-mono text-slate-600">{{ product.ean || '-' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                                    {{ formatVat(product.vat_rate) }}%
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <p class="text-sm font-semibold text-slate-900">{{ formatPrice(product.price) }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">incl. VAT</p>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-md border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-100"
                                                    @click="addToCart(product)"
                                                >
                                                    Add
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="filteredProducts.length === 0">
                                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No products found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="space-y-3 px-4 pb-4 lg:hidden">
                                <article v-for="product in filteredProducts" :key="product.id" class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h5 class="text-sm font-semibold text-slate-900">{{ product.name }}</h5>
                                            <p v-if="productSubtitle(product)" class="mt-1 text-xs text-slate-500">{{ productSubtitle(product) }}</p>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">{{ formatPrice(product.price) }}</p>
                                    </div>
                                    <div class="mt-3 grid grid-cols-3 gap-3 text-xs text-slate-600">
                                        <div>
                                            <p class="text-slate-500">EAN</p>
                                            <p class="mt-1 font-mono">{{ product.ean || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-slate-500">VAT</p>
                                            <p class="mt-1 font-medium">{{ formatVat(product.vat_rate) }}%</p>
                                        </div>
                                        <div>
                                            <p class="text-slate-500">Price</p>
                                            <p class="mt-1 font-medium text-slate-900">{{ formatPrice(product.price) }}</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-medium text-teal-700"
                                        @click="addToCart(product)"
                                    >
                                        Add to Cart
                                    </button>
                                </article>
                                <p v-if="filteredProducts.length === 0" class="rounded-lg border border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No products found.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="showCheckoutModal" max-width="2xl" @close="closeCheckoutModal">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Vystavit uctenku</h3>
                        <p class="mt-1 text-sm text-slate-500">Zkontrolujte celkovou castku a potvrdte zpusob uhrady.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
                        @click="closeCheckoutModal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="rounded-lg bg-gradient-to-r from-teal-700 to-cyan-700 px-4 py-4 text-white shadow-sm shadow-teal-200/70">
                        <div class="flex items-end justify-between gap-4">
                            <p class="text-sm font-medium uppercase tracking-wide text-cyan-100">Celkem</p>
                            <p class="text-right text-3xl font-semibold">{{ formatPrice(checkoutTotal) }}</p>
                        </div>
                    </div>

                    <div v-if="isCashCheckout" class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-600">Zaplaceno</label>
                            <input
                                ref="cashPaidInputRef"
                                v-model.number="checkoutPaidAmount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-600">Vratit</label>
                            <div class="flex h-10 items-center rounded-md border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700 shadow-sm">
                                {{ formatPrice(checkoutChangeAmount) }}
                            </div>
                        </div>
                        <p v-if="checkoutWarningMessage" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700">
                            {{ checkoutWarningMessage }}
                        </p>
                    </div>

                    <div v-else class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-600">Zpusob platby</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ checkoutMethodLabel }}</p>
                        <p class="mt-1.5 text-sm text-slate-600">Potvrdte vystaveni dokladu pro tuto platbu.</p>
                    </div>

                    <p v-if="checkoutModalError" class="text-sm font-semibold text-rose-600">{{ checkoutModalError }}</p>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                        @click="closeCheckoutModal"
                    >
                        Zrusit
                    </button>
                    <button
                        ref="checkoutConfirmButtonRef"
                        type="button"
                        :disabled="!canSubmitCheckout"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-gradient-to-r from-teal-600 to-cyan-600 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:from-teal-700 hover:to-cyan-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="submitCheckout"
                    >
                        {{ isCheckingOut ? 'Zpracovani...' : checkoutSubmitButtonLabel }}
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showBillPreviewModal" max-width="2xl" @close="closeBillPreviewModal">
            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="text-lg font-semibold text-slate-900">Nahled uctenky</h3>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-transparent bg-gradient-to-r from-teal-600 to-cyan-600 px-3 py-2 text-sm font-semibold text-white transition-all duration-200 hover:from-teal-700 hover:to-cyan-700"
                            @click="printBillFromPreviewFrame"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0H7m10 0v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2m10-8V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" />
                            </svg>
                            Vytisknout
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                            @click="openPreviewInNewWindow"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h6m-6 0v14h14v-6" />
                            </svg>
                            Otevrit v novem okne
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
                            @click="closeBillPreviewModal"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="dashboard-preview-canvas">
                    <iframe
                        ref="billPreviewFrameRef"
                        :src="embeddedBillPreviewUrl"
                        title="Nahled uctenky"
                        class="dashboard-preview-frame"
                    />
                </div>
            </div>
        </Modal>

        <Modal :show="showCustomerModal" max-width="2xl" @close="closeCustomerDialog">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Find Customer</h3>
                        <p class="mt-1 text-sm text-slate-500">Select an existing customer or enter an 8-digit IČO to fetch from ARES.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
                        @click="closeCustomerDialog"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div ref="customerInputContainerRef" class="relative mt-5">
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-600">Company Name or IČO</label>
                    <div class="relative">
                        <input
                            v-model="customerSearch"
                            type="text"
                            role="combobox"
                            autocomplete="off"
                            :aria-expanded="showCustomerAutocomplete"
                            :aria-controls="customerAutocompleteId"
                            :aria-activedescendant="highlightedCustomerSuggestionIndex >= 0 ? `customer-suggestion-${customerSuggestions[highlightedCustomerSuggestionIndex]?.id}` : undefined"
                            class="h-10 w-full rounded-md border border-slate-200 px-3 pr-10 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                            @input="onCustomerInput"
                            @focus="onCustomerFocus"
                            @blur="onCustomerBlur"
                            @keydown="onCustomerKeydown"
                        />
                        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <div
                        v-if="showCustomerAutocomplete"
                        :id="customerAutocompleteId"
                        role="listbox"
                        class="absolute z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
                    >
                        <button
                            v-for="(customer, customerIndex) in customerSuggestions"
                            :id="`customer-suggestion-${customer.id}`"
                            :key="customer.id"
                            type="button"
                            role="option"
                            :aria-selected="highlightedCustomerSuggestionIndex === customerIndex"
                            class="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-3 py-2 text-left last:border-b-0"
                            :class="highlightedCustomerSuggestionIndex === customerIndex ? 'bg-teal-50' : 'bg-white hover:bg-slate-50'"
                            @mousedown.prevent="selectCustomerOption(customer)"
                            @mousemove="highlightedCustomerSuggestionIndex = customerIndex"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ customer.company_name || customerDisplayName(customer) }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">IČO: {{ customer.company_id || 'N/A' }}</p>
                            </div>
                        </button>
                    </div>
                </div>

                <p v-if="customerModalError" class="mt-3 text-sm font-medium text-rose-600">{{ customerModalError }}</p>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        v-if="cart.selectedCustomer"
                        type="button"
                        :disabled="isSavingCustomer || !cart.currentTransaction"
                        class="inline-flex items-center justify-center rounded-md border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 transition-colors hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="removeSelectedCustomer"
                    >
                        Remove Customer
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                        @click="closeCustomerDialog"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isSavingCustomer || !cart.currentTransaction"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="saveSelectedCustomer"
                    >
                        {{ isSavingCustomer ? 'Saving...' : 'Select Customer' }}
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showAdjustmentModal" @close="closeAdjustmentDialog">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900">Discount / Surcharge</h3>
                <p class="mt-1 text-sm text-slate-500">Set one percentage adjustment for this receipt. Item prices are recalculated dynamically.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-600">Adjustment type</label>
                        <select
                            v-model="adjustmentFormType"
                            class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                        >
                            <option value="discount">Discount</option>
                            <option value="surcharge">Surcharge</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-600">Percentage</label>
                        <input
                            v-model.number="adjustmentFormPercent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                            @blur="adjustmentFormPercent = clampPercent(adjustmentFormPercent)"
                        />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                        @click="clearAdjustment"
                    >
                        Clear
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teal-700"
                        @click="applyAdjustment"
                    >
                        Apply
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.dashboard-preview-canvas {
    margin-top: 1rem;
    height: min(72vh, 860px);
    overflow: hidden;
    border-radius: 0.75rem;
    background: #9aa9bf;
}

.dashboard-preview-frame {
    width: 100%;
    height: 100%;
    border: 0;
    border-radius: 0.75rem;
    background: #9aa9bf;
}
</style>
