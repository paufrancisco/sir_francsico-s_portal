<template>
    <div class="p-6">
        <h1 class="text-lg font-semibold text-slate-800 mb-4">Grade Correction Requests</h1>

        <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit mb-4">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="activeTab = tab.value"
                class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                :class="activeTab === tab.value ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
            >
                {{ tab.label }} <span class="text-slate-400">({{ counts[tab.value] }})</span>
            </button>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs">
                    <tr>
                        <th class="text-left px-4 py-3">Student</th>
                        <th class="text-left px-4 py-3">Section</th>
                        <th class="text-left px-4 py-3">Type</th>
                        <th class="text-left px-4 py-3">Notes</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-center px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="c in filteredCorrections" :key="c.id">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-700">{{ c.student_name }}</div>
                            <div class="text-xs text-slate-400">{{ c.student_number }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ c.section ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="c.type === 'confirmed' ? 'bg-[#EAF3DE] text-[#3B6D11]' : 'bg-[#FAEEDA] text-[#854F0B]'"
                            >
                                {{ c.type === 'confirmed' ? 'Confirmed' : 'Recheck' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 max-w-xs">
                            {{ c.notes ?? '—' }}
                            <a
                                v-if="c.attachment_url"
                                :href="c.attachment_url"
                                target="_blank"
                                class="flex items-center gap-1 text-[11px] text-[#003399] mt-1"
                            >
                                📎 View attachment
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                v-if="c.status === 'pending'"
                                class="text-xs font-medium px-2 py-0.5 rounded-full bg-[#E6F1FB] text-[#003399]"
                            >
                                pending
                            </span>
                            <span
                                v-else-if="c.decision === 'approved'"
                                class="text-xs font-medium px-2 py-0.5 rounded-full bg-[#EAF3DE] text-[#3B6D11]"
                            >
                                approved
                            </span>
                            <span
                                v-else-if="c.decision === 'rejected'"
                                class="text-xs font-medium px-2 py-0.5 rounded-full bg-[#FBEAEA] text-[#9B1C1C]"
                            >
                                rejected
                            </span>
                            <span v-else class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                resolved
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">
                            {{ new Date(c.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    @click="openGrades(c)"
                                    title="Review / Edit Grades"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filteredCorrections.length === 0">
                        <td colspan="7" class="text-center text-slate-400 text-sm py-8">
                            Wala pang grade correction requests dito.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Edit Grades / Review Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeModal">
            <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-xl">

                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-700">{{ activeStudentName }}</div>
                        <div class="text-xs text-slate-400">{{ activeNotes ?? 'Walang notes' }}</div>
                        <a
                            v-if="activeAttachmentUrl"
                            :href="activeAttachmentUrl"
                            target="_blank"
                            class="text-[11px] text-[#003399] mt-0.5 inline-block"
                        >
                            📎 View attachment
                        </a>
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <p v-if="loadingGrades" class="text-xs text-slate-400 py-4">Naglo-load...</p>

                <p v-else-if="grades.length === 0" class="text-xs text-slate-400 py-4">
                    Wala pang na-record na grades.
                </p>

                <template v-else>
                    <p class="text-[11px] text-amber-700 bg-amber-50 rounded-lg px-2.5 py-1.5 mb-2">
                        Naka-highlight yung mga item na may proposed change galing sa student. Pwede mo pang i-adjust bago mag-Approve.
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

                    <div v-if="activeStatus === 'pending'" class="flex gap-2 mt-4 pt-3 border-t border-slate-100">
                        <button
                            @click="approveCorrection"
                            :disabled="resolving"
                            class="flex-1 text-white text-xs font-semibold py-2 rounded-lg disabled:opacity-50"
                            style="background:#003399;"
                        >
                            {{ resolving ? 'Nagpo-process...' : 'Approve' }}
                        </button>
                        <button
                            @click="rejectCorrection"
                            :disabled="resolving"
                            class="flex-1 border border-red-200 text-red-600 text-xs font-semibold py-2 rounded-lg disabled:opacity-50"
                        >
                            Reject
                        </button>
                    </div>
                    <p v-else class="text-xs text-slate-400 mt-4 pt-3 border-t border-slate-100 text-center">
                        Na-resolve na ito ({{ activeDecision }}).
                    </p>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref } from 'vue';
import axios from 'axios';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    corrections: { type: Array, default: () => [] },
});

const tabs = [
    { value: 'pending', label: 'Pending' },
    { value: 'resolved', label: 'Resolved' },
];
const activeTab = ref('pending');

const localCorrections = ref([...props.corrections]);

const counts = computed(() => ({
    pending: localCorrections.value.filter((c) => c.status === 'pending').length,
    resolved: localCorrections.value.filter((c) => c.status !== 'pending').length,
}));

const filteredCorrections = computed(() =>
    activeTab.value === 'pending'
        ? localCorrections.value.filter((c) => c.status === 'pending')
        : localCorrections.value.filter((c) => c.status !== 'pending')
);

// ---- Edit Grades / Review modal ----
const showModal = ref(false);
const loadingGrades = ref(false);
const activeCorrectionId = ref(null);
const activeStudentName = ref('');
const activeNotes = ref('');
const activeAttachmentUrl = ref(null);
const activeStatus = ref('pending');
const activeDecision = ref(null);
const activeCorrectionItems = ref([]); // proposed items galing sa student
const grades = ref([]);
const errorMsg = ref('');
const resolving = ref(false);

const openGrades = async (correction) => {
    showModal.value = true;
    loadingGrades.value = true;
    errorMsg.value = '';
    activeCorrectionId.value = correction.id;
    activeStudentName.value = correction.student_name;
    activeNotes.value = correction.notes;
    activeAttachmentUrl.value = correction.attachment_url ?? null;
    activeStatus.value = correction.status;
    activeDecision.value = correction.decision;
    activeCorrectionItems.value = correction.edited_items ?? []; // [{category, title, claimed_score}]
    grades.value = [];

    try {
        const res = await axios.get(`/paulo/students/${correction.student_id}/grades`);
        grades.value = res.data.grades.map((g) => {
            const proposal = activeCorrectionItems.value.find(
                (p) => p.category === g.category && p.title === g.title
            );
            return {
                ...g,
                editValue: proposal ? proposal.claimed_score : g.score,
                hasProposal: !!proposal,
            };
        });
    } catch (e) {
        errorMsg.value = 'Hindi na-load ang grades ng estudyante.';
    } finally {
        loadingGrades.value = false;
    }
};

const approveCorrection = async () => {
    resolving.value = true;
    errorMsg.value = '';

    try {
        // 1. I-save lahat ng may pagbabago (proposed man o admin-adjusted)
        const changed = grades.value.filter((g) => Number(g.editValue) !== Number(g.score));
        for (const g of changed) {
            await axios.patch(`/paulo/grades/${g.id}`, { score: g.editValue });
        }

        // 2. I-mark ang correction bilang approved
        const { data } = await axios.patch(`/paulo/grade-corrections/${activeCorrectionId.value}/resolve`, {
            decision: 'approved',
        });
        applyResolution(data.correction);
        closeModal();
    } catch (e) {
        errorMsg.value = e.response?.data?.message
            || Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
            || 'Hindi na-approve, subukan ulit.';
    } finally {
        resolving.value = false;
    }
};

const rejectCorrection = async () => {
    resolving.value = true;
    errorMsg.value = '';

    try {
        // Reject = walang binago sa grades, i-mark lang bilang rejected
        const { data } = await axios.patch(`/paulo/grade-corrections/${activeCorrectionId.value}/resolve`, {
            decision: 'rejected',
        });
        applyResolution(data.correction);
        closeModal();
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'Hindi na-reject, subukan ulit.';
    } finally {
        resolving.value = false;
    }
};

const applyResolution = (updatedCorrection) => {
    const idx = localCorrections.value.findIndex((c) => c.id === activeCorrectionId.value);
    if (idx !== -1) {
        localCorrections.value[idx] = { ...localCorrections.value[idx], ...updatedCorrection };
    }
};

const closeModal = () => {
    showModal.value = false;
};
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