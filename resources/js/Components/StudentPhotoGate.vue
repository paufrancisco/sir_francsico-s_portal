<script setup>
import { ref } from 'vue';

defineProps({ gate: { type: Object, required: true } });
const emit = defineEmits(['close', 'submit']);

const file = ref(null);
const preview = ref('');

const onPick = (e) => {
    const f = e.target.files?.[0] ?? null;
    file.value = f;
    preview.value = f ? URL.createObjectURL(f) : '';
};
</script>

<template>
    <div v-if="gate.open" class="pg-overlay">
        <div class="pg-panel">
            <h3 class="pg-title">Upload your photo first</h3>
            <p class="pg-text">
                You need a photo before you can sign in. Use a JPG or PNG; it is shrunk to under 40KB automatically.
            </p>
            <img v-if="preview" :src="preview" alt="Preview" class="pg-preview" />
            <input type="file" accept="image/jpeg,image/png" class="pg-file" @change="onPick" />
            <p v-if="gate.error" class="pg-error">{{ gate.error }}</p>
            <div class="pg-actions">
                <button type="button" class="pg-btn pg-btn-ghost" :disabled="gate.loading" @click="emit('close')">
                    Cancel
                </button>
                <button
                    type="button"
                    class="pg-btn pg-btn-primary"
                    :disabled="!file || gate.loading"
                    @click="emit('submit', file)"
                >
                    {{ gate.loading ? 'Uploading…' : 'Upload & continue' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pg-overlay { position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(8, 20, 30, 0.72); backdrop-filter: blur(4px); font-family: var(--font-body, system-ui, sans-serif); }
.pg-panel { width: 100%; max-width: 384px; padding: 20px; border: 1px solid var(--surface-border, #234A61); border-radius: 16px; background: var(--surface, #12293A); color: var(--text-body, #CFE3EE); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); }
.pg-title { margin: 0; font-family: var(--font-mono, ui-monospace, monospace); font-size: 1.125rem; font-weight: 700; color: var(--text-heading, #EAF4FA); }
.pg-text { margin: 6px 0 0; font-size: 0.875rem; line-height: 1.45; }
.pg-preview { display: block; width: 128px; height: 128px; margin: 14px auto 0; border: 2px solid var(--navy, #1C6EA4); border-radius: 50%; object-fit: cover; }
.pg-file { display: block; width: 100%; margin-top: 14px; font-size: 0.875rem; color: var(--text-body, #CFE3EE); }
.pg-file::file-selector-button { margin-right: 10px; padding: 6px 12px; border: 1px solid var(--surface-border, #234A61); border-radius: 8px; background: var(--chip-bg, #1B3A4E); color: var(--text-heading, #EAF4FA); cursor: pointer; }
.pg-error { margin: 8px 0 0; font-size: 0.875rem; color: var(--coral, #f85149); }
.pg-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; }
.pg-btn { padding: 8px 14px; border: 1px solid transparent; border-radius: 10px; font-size: 0.875rem; font-weight: 600; cursor: pointer; }
.pg-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.pg-btn-ghost { background: transparent; color: var(--text-muted, #5E8AA0); }
.pg-btn-ghost:hover:not(:disabled) { background: var(--chip-bg, #1B3A4E); color: var(--text-heading, #EAF4FA); }
.pg-btn-primary { background: var(--navy, #1C6EA4); color: #ffffff; }
.pg-btn-primary:hover:not(:disabled) { filter: brightness(1.12); }
</style>
