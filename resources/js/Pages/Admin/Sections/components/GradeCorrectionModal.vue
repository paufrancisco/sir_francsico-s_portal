<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/70 backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div class="grade-modal w-full max-h-[90dvh] flex flex-col overflow-hidden rounded-2xl bg-[#1e2d42] border border-white/15 ring-1 ring-black/40 shadow-2xl shadow-black/60" :data-cols="cols">

            <!-- Header: laging visible -->
            <div class="shrink-0 flex items-start justify-between gap-3 px-5 pt-5 pb-3 border-b border-white/10">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="text-sm font-semibold text-white break-words">{{ studentName }}</div>
                        <span
                            class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                            :class="badgeClass({ status, decision })"
                        >
                            {{ badgeLabel({ status, decision }) }}
                        </span>
                    </div>
                    <div class="text-xs text-[#9fb0c6] mt-0.5 break-words">{{ notes ?? 'No notes' }}</div>
                    <a
                        v-if="attachmentUrl"
                        :href="attachmentUrl"
                        target="_blank"
                        class="text-[11px] text-sky-300 hover:text-sky-200 mt-1 inline-block"
                    >
                        📎 View attachment
                    </a>
                </div>
                <button
                    @click="$emit('close')"
                    class="shrink-0 w-7 h-7 rounded-lg text-[#9fb0c6] hover:text-white hover:bg-white/10 transition"
                >✕</button>
            </div>

            <p v-if="loading" class="text-xs text-[#9fb0c6] px-5 py-4">Loading...</p>

            <p v-else-if="grades.length === 0" class="text-xs text-[#9fb0c6] px-5 py-4">
                No grades recorded yet.
            </p>

            <template v-else>
                <!-- Body: dito lang nag-i-scroll -->
                <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-5 py-3">
                    <p class="text-[11px] text-amber-200 bg-amber-400/10 border border-amber-400/25 rounded-lg px-2.5 py-1.5 mb-3">
                        Items with a proposed change from the student are highlighted. You can still adjust them before approving.
                    </p>

                    <div class="grade-grid border-t border-white/10" :style="{ '--cols': cols }">
                        <div
                            v-for="g in grades"
                            :key="g.id"
                            class="flex items-center gap-2 py-2 px-2 text-sm border-b border-white/10"
                            :class="g.hasProposal ? 'bg-amber-400/10 rounded-lg' : ''"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="text-[#dbe5f2] break-words">{{ g.title ?? g.category }}</div>
                                <div v-if="g.hasProposal" class="text-[11px] text-amber-200 mt-0.5">
                                    Original: <span class="font-medium">{{ Number(g.score).toFixed(2) }}</span>
                                    → Proposal: <span class="font-medium">{{ Number(g.editValue).toFixed(2) }}</span>
                                </div>
                            </div>
                            <input
                                type="number"
                                v-model="g.editValue"
                                :max="g.max_score"
                                min="0"
                                step="0.01"
                                class="grade-score-input shrink-0 w-20 sm:w-[92px] px-2 py-1 text-right font-medium text-white bg-[#121e30] border border-white/15 hover:border-white/30 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/50 rounded-lg transition"
                            />
                            <span class="w-12 sm:w-14 shrink-0 text-right text-[#9fb0c6]">/{{ g.max_score }}</span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-[#dbe5f2] mb-1">
                                Comment
                                <span class="font-normal text-[#9fb0c6]">(optional for approve, required for reject)</span>
                            </label>
                            <textarea
                                v-model="comment"
                                rows="3"
                                maxlength="1000"
                                placeholder="Explain your decision to the student..."
                                class="w-full px-2.5 py-2 text-sm text-white bg-[#121e30] border border-white/15 hover:border-white/30 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/50 rounded-lg placeholder:text-[#6b7f99]"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#dbe5f2] mb-1">
                                Attachment
                                <span class="font-normal text-[#9fb0c6]">(optional, image or PDF, max 5MB)</span>
                            </label>
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                @change="onFileChange"
                                class="block w-full text-xs text-[#9fb0c6] file:mr-2 file:rounded-lg file:border-0 file:bg-white/10 file:px-2.5 file:py-1.5 file:text-xs file:text-white hover:file:bg-white/20"
                            />
                        </div>
                    </div>
                </div>

                <!-- Footer: laging visible, kasama ang errors -->
                <div class="shrink-0 px-5 py-3 border-t border-white/10">
                    <p v-if="localError" class="text-xs text-red-300 mb-2">{{ localError }}</p>
                    <p v-if="errorMsg" class="text-xs text-red-300 mb-2">{{ errorMsg }}</p>

                    <div class="flex gap-2">
                        <button
                            @click="submitApprove"
                            :disabled="resolving"
                            class="flex-1 text-sm font-semibold py-2.5 rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 border border-emerald-400/40 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ resolving ? 'Processing...' : (decision === 'approved' ? 'Re-approve' : 'Approve') }}
                        </button>
                        <button
                            @click="submitReject"
                            :disabled="resolving"
                            class="flex-1 text-sm font-semibold py-2.5 rounded-xl text-red-200 bg-red-500/15 hover:bg-red-500/25 border border-red-400/40 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ decision === 'rejected' ? 'Already rejected' : 'Reject' }}
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
    open: Boolean,
    loading: Boolean,
    studentName: String,
    notes: String,
    attachmentUrl: String,
    grades: { type: Array, default: () => [] },
    errorMsg: String,
    resolving: Boolean,
    status: String,
    decision: String,
    badgeLabel: Function,
    badgeClass: Function,
});

const emit = defineEmits(['close', 'approve', 'reject']);

// Wider modal and more columns as the number of items grows
const cols = computed(() => (props.grades.length > 12 ? 3 : props.grades.length > 6 ? 2 : 1));

const comment = ref('');
const attachment = ref(null);
const localError = ref('');
const fileInput = ref(null);

// Start every review with a clean form.
watch(() => props.open, (isOpen) => {
    if (!isOpen) return;
    comment.value = '';
    attachment.value = null;
    localError.value = '';
    if (fileInput.value) fileInput.value.value = '';
});

const MAX_FILE_BYTES = 5 * 1024 * 1024;

const onFileChange = (e) => {
    localError.value = '';
    const file = e.target.files?.[0] ?? null;

    if (file && file.size > MAX_FILE_BYTES) {
        localError.value = 'The attachment must be 5MB or smaller.';
        e.target.value = '';
        attachment.value = null;
        return;
    }

    attachment.value = file;
};

const submitApprove = () => {
    localError.value = '';
    emit('approve', { comment: comment.value.trim(), attachment: attachment.value });
};

const submitReject = () => {
    localError.value = '';

    if (!comment.value.trim()) {
        localError.value = 'A comment is required when rejecting a request.';
        return;
    }

    emit('reject', { comment: comment.value.trim(), attachment: attachment.value });
};
</script>

<style scoped>
.grade-modal { max-width: 24rem; }
.grade-grid { display: grid; grid-template-columns: minmax(0, 1fr); column-gap: 1rem; }
@media (min-width: 768px) {
    .grade-modal[data-cols="2"] { max-width: 44rem; }
    .grade-modal[data-cols="3"] { max-width: 64rem; }
    .grade-grid { grid-template-columns: repeat(var(--cols), minmax(0, 1fr)); }
}
.grade-score-input {
    -moz-appearance: textfield;
}
.grade-score-input::-webkit-outer-spin-button,
.grade-score-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>