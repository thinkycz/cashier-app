<script setup>
defineProps({
    modelValue: {
        type: [String, Number, Boolean, null],
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <select
        :value="modelValue"
        class="w-full rounded-lg border-slate-300 bg-white text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <slot />
    </select>
</template>
