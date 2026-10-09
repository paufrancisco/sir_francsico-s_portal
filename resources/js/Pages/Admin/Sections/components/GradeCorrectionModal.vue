<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/70 backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div class="w-full max-w-sm rounded-2xl p-5 bg-[#1e2d42] border border-white/15 ring-1 ring-black/40 shadow-2xl shadow-black/60">

            <div class="flex items-start justify-between mb-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="text-sm font-semibold text-white">{{ studentName }}</div>
                        <span
                            class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                            :class="badgeClass({ status, decision })"
                        >
                            {{ badgeLabel({ status, decision }) }}
                        </span>
                    </div>
                    <div class="text-xs text-[#9fb0c6] mt-0.5">{{ notes ?? 'No notes' }}</div>
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
                    class="ml-3 shrink-0 w-7 h-7 rounded-lg text-[#9fb0c6] hover:text-white hover:bg-white/10 transition"
                >✕</button>
            </div>

            <p v-if="loading" class="text-xs text-[#9fb0c6] py-4">Loading...</p>

            <p v-else-if="grades.length === 0" class="text-xs text-[#9fb0c6] py-4">
                No grades recorded yet.
            </p>

            <template v-else>
                <p class="text-[11px] text-amber-200 bg-amber-400/10 border border-amber-400/25 rounded-lg px-2.5 py-1.5 mb-3">
                    Items with a proposed change from the student are highlighted. You can still adjust them before approving.
                </p>

                <div class="divide-y divide-white/10 border-t border-white/10">
                    <div
                        v-for="g in grades"
                        :key="g.id"
                        class="flex items-center gap-2 py-2 text-sm"
                        :class="g.hasProposal ? 'bg-amber-400/10 -mx-2 px-2 rounded-lg' : ''"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="truncate text-[#dbe5f2]">{{ g.title ?? g.category }}</div>
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
                            style="width: 92px;"
                            class="grade-score-input shrink-0 px-2 py-1 text-right font-medium text-white bg-[#121e30] border border-white/15 hover:border-white/30 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/50 rounded-lg transition"
                        />
                        <span class="w-14 shrink-0 text-right text-[#9fb0c6]">/{{ g.max_score }}</span>
                    </div>
                </div>

                <p v-if="errorMsg" class="text-xs text-red-300 mt-3">{{ errorMsg }}</p>

                <div class="flex gap-2 mt-4 pt-4 border-t border-white/10">
                    <button
                        @click="$emit('approve')"
                        :disabled="resolving"
                        class="flex-1 text-sm font-semibold py-2.5 rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 border border-emerald-400/40 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ resolving ? 'Processing...' : (decision === 'approved' ? 'Re-approve' : 'Approve') }}
                    </button>
                    <button
                        @click="$emit('reject')"
                        :disabled="resolving"
                        class="flex-1 text-sm font-semibold py-2.5 rounded-xl text-red-200 bg-red-500/15 hover:bg-red-500/25 border border-red-400/40 transition disabled:opacity-50 disabled:cursor-not-allowed"
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