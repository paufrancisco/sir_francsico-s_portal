<template>
    <div class="p-6">
        <h1 class="text-lg font-semibold text-slate-800 mb-4">Grade Correction Requests</h1>

        <p class="text-xs text-slate-400 mb-4">
            {{ counts.total }} total &middot; {{ counts.pending }} pending &middot; {{ counts.approved }} approved &middot; {{ counts.rejected }} rejected &middot; {{ counts.cancelled }} cancelled
        </p>

        <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit mb-4">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="activeTab = tab.value"
                class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                :class="activeTab === tab.value ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
            >
                {{ tab.label }} <span class="text-slate-400">({{ tab.value === 'pending' ? counts.pending : tab.value === 'archived' ? counts.archived : counts.resolved }})</span>
            </button>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Search student</label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Pangalan ng estudyante..."
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700"
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Section</label>
                    <select
                        v-model="sectionFilter"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 bg-white"
                    >
                        <option value="all">All Sections</option>
                        <option v-for="s in sectionsList" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>

                <div v-if="activeTab === 'resolved'">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Status</label>
                    <select
                        v-model="statusFilter"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 bg-white"
                    >
                        <option value="all">All</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        @click="resetFilters"
                        class="text-sm font-medium border border-slate-300 text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-50 transition"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <div v-if="selectedIds.length > 0" class="flex items-center gap-2 mb-3">
            <span class="text-xs text-slate-500">{{ selectedIds.length }} napili</span>
            <button
                v-if="activeTab !== 'archived'"
                @click="bulkArchive"
                :disabled="bulkProcessing"
                class="text-xs font-medium px-3 py-1.5 rounded-lg bg-slate-700 text-white disabled:opacity-50"
            >
                {{ bulkProcessing ? 'Nagpo-process...' : 'I-archive ang napili' }}
            </button>
            <button
                v-else
                @click="bulkUnarchive"
                :disabled="bulkProcessing"
                class="text-xs font-medium px-3 py-1.5 rounded-lg bg-[#003399] text-white disabled:opacity-50"
            >
                {{ bulkProcessing ? 'Nagpo-process...' : 'I-restore mula sa archive' }}
            </button>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs">
                    <tr>
                        <th class="px-4 py-3 w-8">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleSelectAll"
                                class="rounded border-slate-300"
                            />
                        </th>
                        <th class="text-left px-4 py-3">Student</th>
                        <th class="text-left px-4 py-3">Section</th>
                        <th class="text-left px-4 py-3">Notes</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-center px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="c in filteredCorrections" :key="c.id">
                        <td class="px-4 py-3">
                            <input
                                type="checkbox"
                                :value="c.id"
                                v-model="selectedIds"
                                class="rounded border-slate-300"
                            />
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-700">{{ c.student_name }}</div>
                            <div class="text-xs text-slate-400">{{ c.student_number }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ c.section ?? '—' }}</td>
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
                                Pending
                            </span>
                            <span
                                v-else-if="c.decision === 'approved'"
                                class="text-xs font-medium px-2 py-0.5 rounded-full bg-[#EAF3DE] text-[#3B6D11]"
                            >
                                Approved
                            </span>
                            <span
                                v-else-if="c.decision === 'cancelled'"
                                class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-500"
                            >
                                Cancelled
                            </span>
                            <span v-else-if="c.decision === 'rejected'" class="text-xs font-medium px-2 py-0.5 rounded-full bg-[#FBEAEA] text-[#9B1C1C]">
                                Rejected
                            </span>
                            <span v-else class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-500" :title="`decision: ${c.decision ?? 'null'}`">
                                Unknown
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400 whitespace-nowrap">
                            <template v-if="c.status === 'pending'">
                                <div class="text-slate-500">Submitted</div>
                                <div>{{ formatDateTime(c.created_at) }}</div>
                            </template>
                            <template v-else>
                                <div class="text-slate-500">{{ decisionLabel(c.decision) }}</div>
                                <div>{{ formatDateTime(c.resolved_at ?? c.updated_at) }}</div>
                            </template>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
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
                                <button
                                    @click="openHistory(c)"
                                    title="View history ng changes"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 3v5h5"/>
                                        <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/>
                                        <path d="M12 7v5l4 2"/>
                                    </svg>
                                </button>
                                <button
                                    @click="deleteCorrection(c)"
                                    :disabled="deletingId === c.id"
                                    title="Delete"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition disabled:opacity-40"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
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

        <!-- View History Modal -->
        <div v-if="showHistoryModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeHistoryModal">
            <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-700">{{ historyCorrection?.student_name }}</div>
                        <div class="text-xs text-slate-400">History ng correction request</div>
                    </div>
                    <button @click="closeHistoryModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Type</span>
                        <span class="font-medium text-slate-700">
                            {{ historyCorrection?.type === 'confirmed' ? 'Confirmed' : 'Recheck' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Section</span>
                        <span class="font-medium text-slate-700">{{ historyCorrection?.section ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Isinumite</span>
                        <span class="font-medium text-slate-700">{{ formatDateTime(historyCorrection?.created_at) }}</span>
                    </div>

                    <div v-if="historyCorrection?.notes">
                        <span class="text-slate-500 block mb-1">Notes</span>
                        <p class="text-slate-700 bg-slate-50 rounded-lg px-2.5 py-1.5">{{ historyCorrection.notes }}</p>
                    </div>

                    <a
                        v-if="historyCorrection?.attachment_url"
                        :href="historyCorrection.attachment_url"
                        target="_blank"
                        class="text-[11px] text-[#003399] inline-block"
                    >
                        📎 View attachment
                    </a>

                    <div v-if="historyCorrection?.edited_items?.length">
                        <span class="text-slate-500 block mb-1">Mga in-propose na item</span>
                        <div class="divide-y divide-slate-100 border-t border-slate-100">
                            <div
                                v-for="item in historyCorrection.edited_items"
                                :key="item.category + item.title"
                                class="flex items-center justify-between py-1.5"
                            >
                                <span class="text-slate-600">{{ item.title }}</span>
                                <span class="font-medium text-slate-700">→ {{ item.claimed_score }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Status</span>
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="historyCorrection?.status === 'pending'
                                    ? 'bg-[#E6F1FB] text-[#003399]'
                                    : historyCorrection?.decision === 'approved'
                                        ? 'bg-[#EAF3DE] text-[#3B6D11]'
                                        : historyCorrection?.decision === 'cancelled'
                                            ? 'bg-slate-100 text-slate-500'
                                            : historyCorrection?.decision === 'rejected'
                                                ? 'bg-[#FBEAEA] text-[#9B1C1C]'
                                                : 'bg-slate-100 text-slate-500'"
                            >
                                {{ historyCorrection?.status === 'pending' ? 'Pending' : decisionLabel(historyCorrection?.decision) }}
                            </span>
                        </div>
                        <p v-if="historyCorrection?.decision === 'cancelled'" class="text-[11px] text-slate-400 mt-1">
                            Ang estudyante mismo ang nag-cancel ng sarili niyang request.
                        </p>
                        <p v-else-if="historyCorrection?.decision === 'rejected'" class="text-[11px] text-slate-400 mt-1">
                            Na-reject ni admin ang request na ito.
                        </p>
                        <div v-if="historyCorrection?.status !== 'pending'" class="flex items-center justify-between mt-2">
                            <span class="text-slate-500">
                                {{ historyCorrection?.decision === 'approved'
                                    ? 'Na-approve noong'
                                    : historyCorrection?.decision === 'cancelled'
                                        ? 'Na-cancel noong'
                                        : historyCorrection?.decision === 'rejected'
                                            ? 'Na-reject noong'
                                            : 'Na-resolve noong' }}
                            </span>
                            <span class="font-medium text-slate-700">
                                {{ formatDateTime(historyCorrection?.resolved_at ?? historyCorrection?.updated_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref, watch } from 'vue';
import axios from 'axios';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    corrections: { type: Array, default: () => [] },
});

const localCorrections = ref([...props.corrections]);

const counts = computed(() => {
    const all = localCorrections.value;
    const active = all.filter((c) => !c.archived);
    const approved = active.filter((c) => c.status !== 'pending' && c.decision === 'approved').length;
    const cancelled = active.filter((c) => c.status !== 'pending' && c.decision === 'cancelled').length;
    const rejected = active.filter((c) => c.status !== 'pending' && c.decision === 'rejected').length;
    const resolved = active.filter((c) => c.status !== 'pending').length;
    return {
        total: all.length,
        pending: active.filter((c) => c.status === 'pending').length,
        resolved,
        approved,
        cancelled,
        rejected,
        archived: all.filter((c) => c.archived).length,
    };
});

// Explicit label for a decision value — used instead of an "else = Rejected"
// fallback so an unexpected/missing value from the API shows up as "Unknown"
// rather than silently displaying as Rejected.
const decisionLabel = (decision) => {
    if (decision === 'approved') return 'Approved';
    if (decision === 'cancelled') return 'Cancelled';
    if (decision === 'rejected') return 'Rejected';
    return 'Unknown';
};

// ---- Tabs ----
const tabs = [
    { value: 'pending', label: 'Pending' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'archived', label: 'Archived' },
];
const activeTab = ref('pending');
watch(activeTab, () => { selectedIds.value = []; });

// ---- Filters ----
const sectionFilter = ref('all');
const statusFilter = ref('all'); // only applies within the Resolved tab: approved | rejected | cancelled
const searchQuery = ref('');

const sectionsList = computed(() =>
    [...new Set(localCorrections.value.map((c) => c.section).filter(Boolean))].sort()
);

const filteredCorrections = computed(() => {
    let list;
    if (activeTab.value === 'archived') {
        list = localCorrections.value.filter((c) => c.archived);
    } else if (activeTab.value === 'pending') {
        list = localCorrections.value.filter((c) => c.status === 'pending' && !c.archived);
    } else {
        list = localCorrections.value.filter((c) => c.status !== 'pending' && !c.archived);
    }

    if (activeTab.value === 'resolved' && statusFilter.value !== 'all') {
        list = list.filter((c) => c.decision === statusFilter.value);
    }

    if (sectionFilter.value !== 'all') {
        list = list.filter((c) => c.section === sectionFilter.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.trim().toLowerCase();
        list = list.filter((c) => c.student_name?.toLowerCase().includes(q));
    }

    return list;
});

const resetFilters = () => {
    sectionFilter.value = 'all';
    statusFilter.value = 'all';
    searchQuery.value = '';
};
// ---- End filters ----

// ---- Bulk select + archive ----
const selectedIds = ref([]);
const bulkProcessing = ref(false);

const allSelected = computed(() =>
    filteredCorrections.value.length > 0
    && filteredCorrections.value.every((c) => selectedIds.value.includes(c.id))
);

const toggleSelectAll = () => {
    if (allSelected.value) {
        const ids = new Set(filteredCorrections.value.map((c) => c.id));
        selectedIds.value = selectedIds.value.filter((id) => !ids.has(id));
    } else {
        const current = new Set(selectedIds.value);
        filteredCorrections.value.forEach((c) => current.add(c.id));
        selectedIds.value = Array.from(current);
    }
};

const bulkArchive = async () => {
    if (selectedIds.value.length === 0) return;
    bulkProcessing.value = true;
    try {
        await axios.post('/paulo/grade-corrections/archive', { ids: selectedIds.value });
        localCorrections.value = localCorrections.value.map((c) =>
            selectedIds.value.includes(c.id) ? { ...c, archived: true } : c
        );
        selectedIds.value = [];
    } catch (e) {
        alert(e.response?.data?.message ?? 'Hindi na-archive, subukan ulit.');
    } finally {
        bulkProcessing.value = false;
    }
};

const bulkUnarchive = async () => {
    if (selectedIds.value.length === 0) return;
    bulkProcessing.value = true;
    try {
        await axios.post('/paulo/grade-corrections/unarchive', { ids: selectedIds.value });
        localCorrections.value = localCorrections.value.map((c) =>
            selectedIds.value.includes(c.id) ? { ...c, archived: false } : c
        );
        selectedIds.value = [];
    } catch (e) {
        alert(e.response?.data?.message ?? 'Hindi na-restore, subukan ulit.');
    } finally {
        bulkProcessing.value = false;
    }
};
// ---- End bulk select + archive ----

const formatDateTime = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('en-PH', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

// ---- Edit Grades / Review modal ----

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
// ---- End Edit Grades / Review modal ----

// ---- View History modal ----
const showHistoryModal = ref(false);
const historyCorrection = ref(null);

const openHistory = (correction) => {
    historyCorrection.value = correction;
    showHistoryModal.value = true;
};

const closeHistoryModal = () => {
    showHistoryModal.value = false;
    historyCorrection.value = null;
};
// ---- End View History modal ----

// ---- Delete ----
const deletingId = ref(null);

const deleteCorrection = async (correction) => {
    const confirmed = confirm(`Sigurado ka bang gusto mong burahin ang request ni ${correction.student_name}? Hindi na ito mababawi.`);
    if (!confirmed) return;

    deletingId.value = correction.id;
    try {
        await axios.delete(`/paulo/grade-corrections/${correction.id}`);
        localCorrections.value = localCorrections.value.filter((c) => c.id !== correction.id);
    } catch (e) {
        alert(e.response?.data?.message ?? 'Hindi na-delete, subukan ulit.');
    } finally {
        deletingId.value = null;
    }
};
// ---- End Delete ----
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