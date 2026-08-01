<script setup>
import PasswordInput from './PasswordInput.vue';

defineProps({
    form: { type: Object, required: true }, // { new_password, confirm_password }
    error: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    action: { type: String, default: 'continue' }, // e.g. "view your grades", "ask a question", "set an appointment"
});
defineEmits(['submit', 'cancel']);
</script>

<template>
    <div class="space-y-3">
        <p class="text-xs text-[var(--text-muted)]">This is your first login — you need to change your password before you can {{ action }}.</p>
        <PasswordInput v-model="form.new_password" placeholder="New password" />
        <PasswordInput v-model="form.confirm_password" placeholder="Confirm new password" />
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
        <div class="flex gap-2">
            <button
                @click="$emit('submit')"
                :disabled="loading"
                class="flex-1 text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50"
                style="background:var(--navy);"
            >
                {{ loading ? 'Updating...' : 'Update password' }}
            </button>
            <button @click="$emit('cancel')" class="text-xs text-[var(--text-muted)] px-3">Cancel</button>
        </div>
    </div>
</template>