<template>
    <div v-if="open" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="$emit('close')">
        <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-xl">

            <div class="flex items-center justify-between mb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="text-sm font-semibold text-slate-700">{{ studentName }}</div>
                        <span
                            class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                            :class="badgeClass({ status, decision })"
                        >
                            {{ badgeLabel({ status, decision }) }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-400">{{ notes ?? 'No notes' }}</div>
                    <a
                        v-if="attachmentUrl"
                        :href="attachmentUrl"
                        target="_blank"
                        class="text-[11px] text-[#003399] mt-0.5 inline-block"
                    >
                        📎 View attachment
                    </a>
                </div>
                <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <p v-if="loading" class="text-xs text-slate-400 py-4">Loading...</p>

            <p v-else-if="grades.length === 0" class="text-xs text-slate-400 py-4">
                No grades recorded yet.
            </p>

            <template v-else>
                <p class="text-[11px] text-amber-700 bg-amber-50 rounded-lg px-2.5 py-1.5 mb-2">
                    Items with a proposed change from the student are highlighted. You can still adjust them before approving.
                </p>

                <div class="divide-y divide-slate-100 border-t border-slate-100">
                    <div
                        v-for="g in grades"
                        :key="g.id"
                        class="flex items-center gap-2 py-2 text-sm"
                        :class="g.hasProposal ? 'bg-amber-50 -mx-2 px-2 rounded-lg' : ''"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="truncate text-slate-600">{{ g.title ?? g.category }}</div>
                            <div v-if="g.hasProposal" class="text-[11px] text-amber-700 mt-0.5">
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
                            style="width: 92px;"
                            class="grade-score-input shrink-0 px-1.5 text-right font-medium text-slate-700 bg-transparent border border-transparent hover:border-slate-200 focus:border-[#003399] focus:outline-none rounded transition"
                        />
                        <span class="w-14 shrink-0 text-right text-slate-400">/{{ g.max_score }}</span>
                    </div>
                </div>

                <p v-if="errorMsg" class="text-xs text-red-500 mt-3">{{ errorMsg }}</p>

                <div class="flex gap-2 mt-4 pt-3 border-t border-slate-100">
                    <button
                        @click="$emit('approve')"
                        :disabled="resolving"
                        class="flex-1 text-white text-xs font-semibold py-2 rounded-lg disabled:opacity-50"
                        style="background:#003399;"
                    >
                        {{ resolving ? 'Processing...' : (decision === 'approved' ? 'Re-apply approve' : 'Approve') }}
                    </button>
                    <button
                        @click="$emit('reject')"
                        :disabled="resolving"
                        class="flex-1 border border-red-200 text-red-600 text-xs font-semibold py-2 rounded-lg disabled:opacity-50"
                    >
                        {{ decision === 'rejected' ? 'Already rejected' : 'Reject' }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
defineProps({
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

defineEmits(['close', 'approve', 'reject']);
</script>

<style scoped>
.grade-score-input {
    -moz-appearance: textfield;
}
.grade-score-input::-webkit-outer-spin-button,
.grade-score-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>