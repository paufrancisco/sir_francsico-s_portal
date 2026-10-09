<template>
    <div v-if="open" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4" @click.self="$emit('close')">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-1">
                <div class="text-sm font-semibold text-slate-800">Status history</div>
                <button type="button" @click="$emit('close')" class="text-slate-400 hover:text-slate-600" aria-label="Close">✕</button>
            </div>
            <p class="text-xs text-slate-400 mb-4">{{ studentName }}</p>
            <p v-if="events.length === 0" class="text-xs text-slate-400">No activity yet for this period.</p>
            <ol v-else class="space-y-4">
                <li v-for="(e, i) in events" :key="i" class="flex gap-3">
                    <span class="mt-1 w-2.5 h-2.5 rounded-full shrink-0" :class="e.dot"></span>
                    <div class="min-w-0">
                        <div class="text-sm font-medium" :class="e.text">{{ e.label }}</div>
                        <div class="text-[11px] text-slate-400">{{ e.date ? formatDate(e.date) : 'Waiting for admin decision' }}</div>
                        <p v-if="e.notes" class="text-xs text-slate-500 mt-1 break-words">{{ e.notes }}</p>
                    </div>
                </li>
            </ol>
            <div v-if="clearableIds.length" class="mt-5 pt-4 border-t border-slate-200/60 flex items-center justify-between gap-3">
                <p v-if="clearError" class="text-xs text-red-500">{{ clearError }}</p>
                <span v-else class="text-[11px] text-slate-400">Pending requests are kept.</span>
                <button
                    type="button"
                    @click="clearHistory"
                    :disabled="clearing"
                    class="shrink-0 text-xs font-medium px-3 py-1.5 rounded-lg border border-red-300/60 text-red-500 hover:bg-red-500/10 disabled:opacity-50 transition"
                >
                    {{ clearing ? 'Clearing...' : 'Clear history' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    open: { type: Boolean, default: false },
    studentName: { type: String, default: '' },
    history: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

// Only resolved entries can be cleared; a pending request stays.
const clearableIds = computed(() =>
    props.history.filter((h) => h.status !== 'pending').map((h) => h.id)
);
const clearing = ref(false);
const clearError = ref('');

const clearHistory = async () => {
    if (!clearableIds.value.length) return;
    if (!confirm('Clear the history of ' + props.studentName + '? It will be moved to the Archived tab in Grade Corrections and can be restored there.')) return;

    clearing.value = true;
    clearError.value = '';
    try {
        await axios.post('/paulo/grade-corrections/archive', { ids: clearableIds.value });
        router.reload({ only: ['gradesBreakdown'] });
        emit('close');
    } catch (e) {
        clearError.value = e.response?.data?.message ?? 'Failed to clear, please try again.';
    } finally {
        clearing.value = false;
    }
};

const formatDate = (d) =>
    new Date(d).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });

// Full class names so Tailwind can see them at build time
const STYLES = {
    green: { dot: 'bg-green-500', text: 'text-green-600' },
    amber: { dot: 'bg-amber-500', text: 'text-amber-500' },
    red: { dot: 'bg-red-500', text: 'text-red-500' },
    sky: { dot: 'bg-sky-500', text: 'text-sky-600' },
};

const mk = (at, date, label, color, notes) => ({ at, date, label, notes, ...STYLES[color] });

const events = computed(() => {
    const out = [];
    for (const h of props.history) {
        if (h.type === 'confirmed') {
            out.push(mk(h.created_at, h.created_at, 'Confirmed', 'green', null));
            continue;
        }
        if (h.status === 'pending') {
            out.push(mk(h.created_at, null, 'Pending', 'amber', null));
        } else if (h.decision === 'rejected') {
            out.push(mk(h.resolved_at ?? h.created_at, h.resolved_at, 'Rejected', 'red', null));
        } else {
            out.push(mk(h.resolved_at ?? h.created_at, h.resolved_at, 'Approved', 'green', null));
        }
        out.push(mk(h.created_at, h.created_at, 'Proposed correction', 'sky', h.notes));
    }
    // Newest first. At equal timestamps the decision stays above the proposal.
    return out
        .map((e, i) => ({ ...e, i }))
        .sort((a, b) => new Date(b.at) - new Date(a.at) || a.i - b.i);
});
</script>
