<template>
    <div class="space-y-3">
        <div class="flex justify-end gap-2">
            <button
                v-if="selectedGradeRows.length > 0"
                @click="deleteSelectedGradeRows"
                class="bg-red-50 text-red-600 text-xs font-medium px-4 py-2 rounded-lg hover:bg-red-100 transition"
            >
                Clear grades ({{ selectedGradeRows.length }})
            </button>
            <label class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': periodLoading }">
                {{ uploading ? 'Uploading...' : periodLoading ? 'Loading...' : 'Import grades (Excel)' }}
                <input type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="uploadGrades" :disabled="uploading || periodLoading" />
            </label>
        </div>
        <p class="text-[11px] text-slate-400">
            Import the grading sheet (Input tab). Periods are detected automatically from the scores found in the file — Column D = Student No.
        </p>

        <!-- Period tabs (view only) -->
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit">
                <button
                    v-for="p in periods"
                    :key="p.value"
                    @click="switchPeriod(p.value)"
                    :disabled="periodLoading"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition disabled:cursor-not-allowed"
                    :class="currentPeriod === p.value ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    {{ p.label }}
                </button>
            </div>
            <svg
                v-if="periodLoading"
                width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin text-[#003399]"
            >
                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
            </svg>
        </div>

        <p class="text-[11px] text-slate-400">
            Grade = (PT/Lab % × 30%) + (Quiz % × 20%) + (Exam % × 50%)
        </p>

        <!-- Search + missing-grade filter -->
        <div class="flex items-center gap-2">
            <div class="relative flex-1">
                <svg
                    width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"
                >
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input
                    v-model="gradeSearch"
                    type="text"
                    placeholder="Search by student number or name..."
                    class="w-full text-sm border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:border-[#003399]"
                />
            </div>
            <select
                v-model="missingOnly"
                class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-600 bg-white"
            >
                <option :value="false">All students</option>
                <option :value="true">Has missing grade</option>
            </select>
            <select
                v-model="sortOrder"
                class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-600 bg-white"
            >
                <option value="rank">Sort: Rank</option>
                <option value="grade_desc">Grade: Highest to lowest</option>
                <option value="grade_asc">Grade: Lowest to highest</option>
                <option value="name_asc">Name: A–Z</option>
                <option value="name_desc">Name: Z–A</option>
            </select>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-x-auto max-w-full">
            <p v-if="gradesBreakdown.length === 0" class="text-xs text-slate-400 px-4 py-6">
                No grades imported yet.
            </p>
            <p v-else-if="filteredGradesBreakdown.length === 0" class="text-xs text-slate-400 px-4 py-6">
                No matching students.
            </p>
            <table v-else class="w-full text-sm border-collapse">
                <thead>
                    <!-- Group headings -->
                    <tr class="text-xs text-slate-500">
                        <th rowspan="2" class="bg-slate-50 px-3 py-2 w-8 text-left">
                            <input
                                type="checkbox"
                                :checked="allGradeRowsSelected"
                                @change="toggleSelectAllGradeRows"
                                class="rounded border-slate-300"
                            />
                        </th>
                        <th rowspan="2" class="bg-slate-50 px-3 py-2 text-left">Rank</th>
                        <th rowspan="2" class="bg-slate-50 px-3 py-2 text-left">ID No.</th>
                        <th rowspan="2" class="bg-slate-50 px-3 py-2 text-left">Name</th>

                        <th
                            v-if="tpItems.length"
                            :colspan="tpItems.length + 1"
                            class="bg-blue-100 text-blue-900 px-2 py-1.5 text-center font-semibold tracking-wide border-l border-white"
                        >
                            PT/LAB <span class="font-normal">30%</span>
                        </th>
                        <th
                            v-if="quizItems.length"
                            :colspan="quizItems.length + 1"
                            class="bg-green-100 text-green-900 px-2 py-1.5 text-center font-semibold tracking-wide border-l border-white"
                        >
                            QUIZZES <span class="font-normal">20%</span>
                        </th>
                        <th
                            v-if="examItems.length"
                            :colspan="examItems.length + 1"
                            class="bg-yellow-100 text-yellow-900 px-2 py-1.5 text-center font-semibold tracking-wide border-l border-white"
                        >
                            EXAM <span class="font-normal">50%</span>
                        </th>
                        <th colspan="2" class="bg-[#003399] text-white px-2 py-1.5 text-center font-semibold tracking-wide border-l border-white">
                            {{ periodLabel.toUpperCase() }}
                        </th>

                        <th rowspan="2" class="bg-slate-50 px-3 py-2 text-left">Status</th>
                        <th rowspan="2" class="bg-slate-50 px-3 py-2 text-center">Action</th>
                    </tr>

                    <!-- Column headings -->
                    <tr class="text-[11px] text-slate-600">
                        <!-- PT/Lab -->
                        <th
                            v-for="item in tpItems"
                            :key="'h-' + item.category + item.title"
                            class="bg-blue-50 px-2 py-1 text-center w-14 border-l border-white"
                        >
                            <div class="font-semibold">{{ shortLabel(item) }}</div>
                            <div class="text-[10px] font-normal text-slate-400">{{ itemMax(item) ?? '' }}</div>
                        </th>
                        <th v-if="tpItems.length" class="bg-blue-100 px-2 py-1 text-center w-14 border-l border-white">
                            <div class="font-semibold">TTL</div>
                            <div class="text-[10px] font-normal text-slate-400">{{ groupMax(tpItems) }}</div>
                        </th>

                        <!-- Quizzes -->
                        <th
                            v-for="item in quizItems"
                            :key="'h-' + item.category + item.title"
                            class="bg-green-50 px-2 py-1 text-center w-14 border-l border-white"
                        >
                            <div class="font-semibold">{{ shortLabel(item) }}</div>
                            <div class="text-[10px] font-normal text-slate-400">{{ itemMax(item) ?? '' }}</div>
                        </th>
                        <th v-if="quizItems.length" class="bg-green-100 px-2 py-1 text-center w-14 border-l border-white">
                            <div class="font-semibold">TTL</div>
                            <div class="text-[10px] font-normal text-slate-400">{{ groupMax(quizItems) }}</div>
                        </th>

                        <!-- Exam -->
                        <th
                            v-for="item in examItems"
                            :key="'h-' + item.category + item.title"
                            class="bg-yellow-50 px-2 py-1 text-center w-14 border-l border-white"
                        >
                            <div class="font-semibold">{{ shortLabel(item) }}</div>
                            <div class="text-[10px] font-normal text-slate-400">{{ itemMax(item) ?? '' }}</div>
                        </th>
                        <th v-if="examItems.length" class="bg-yellow-100 px-2 py-1 text-center w-16 border-l border-white">
                            <div class="font-semibold">EQV</div>
                            <div class="text-[10px] font-normal text-slate-400">%</div>
                        </th>

                        <!-- Grade -->
                        <th class="bg-[#e6ecf7] text-[#003399] px-2 py-1 text-center w-20 border-l border-white font-semibold">Grade</th>
                        <th class="bg-[#e6ecf7] text-[#003399] px-2 py-1 text-center w-16 border-l border-white font-semibold">EQV</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in sortedGradesBreakdown" :key="row.id" class="border-t border-slate-100">
                        <td class="px-3 py-2">
                            <input
                                type="checkbox"
                                :value="row.id"
                                v-model="selectedGradeRows"
                                class="rounded border-slate-300"
                            />
                        </td>
                        <td class="px-3 py-2 text-slate-500">{{ row.rank }}</td>
                        <td class="px-3 py-2 text-slate-500 whitespace-nowrap">{{ row.student_number }}</td>
                        <td class="px-3 py-2 text-slate-700 font-medium whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5">
                                <span
                                    :class="row.total_percentage < 60 ? 'text-red-600 bg-red-50 px-2 py-0.5 rounded-full' : ''"
                                >
                                    {{ row.name }}
                                </span>
                                <span
                                    v-if="rowHasMissingGrade(row)"
                                    title="Has missing grade"
                                    class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"
                                ></span>
                            </span>
                        </td>

                        <!-- PT/Lab -->
                        <td
                            v-for="item in tpItems"
                            :key="'c-' + item.category + item.title"
                            class="px-2 py-2 text-center bg-blue-50/40 border-l border-slate-100"
                        >
                            <span v-if="cell(row, item)" :class="isLowScore(cell(row, item)) ? 'text-red-600 font-medium' : 'text-slate-700'">
                                {{ cell(row, item).score }}
                            </span>
                        </td>
                        <td v-if="tpItems.length" class="px-2 py-2 text-center font-medium text-slate-700 bg-blue-100/40 border-l border-slate-100">
                            <span v-if="row.category_totals?.tp != null">{{ row.category_totals.tp }}</span>
                        </td>

                        <!-- Quizzes -->
                        <td
                            v-for="item in quizItems"
                            :key="'c-' + item.category + item.title"
                            class="px-2 py-2 text-center bg-green-50/40 border-l border-slate-100"
                        >
                            <span v-if="cell(row, item)" :class="isLowScore(cell(row, item)) ? 'text-red-600 font-medium' : 'text-slate-700'">
                                {{ cell(row, item).score }}
                            </span>
                        </td>
                        <td v-if="quizItems.length" class="px-2 py-2 text-center font-medium text-slate-700 bg-green-100/40 border-l border-slate-100">
                            <span v-if="row.category_totals?.long_quiz != null">{{ row.category_totals.long_quiz }}</span>
                        </td>

                        <!-- Exam -->
                        <td
                            v-for="item in examItems"
                            :key="'c-' + item.category + item.title"
                            class="px-2 py-2 text-center bg-yellow-50/40 border-l border-slate-100"
                        >
                            <span v-if="cell(row, item)" :class="isLowScore(cell(row, item)) ? 'text-red-600 font-medium' : 'text-slate-700'">
                                {{ cell(row, item).score }}
                            </span>
                        </td>
                        <td v-if="examItems.length" class="px-2 py-2 text-center text-slate-600 bg-yellow-100/40 border-l border-slate-100">
                            <span v-if="row.category_percentages?.exam != null">{{ fmt(row.category_percentages.exam) }}</span>
                        </td>

                        <!-- Grade + equivalent -->
                        <td
                            class="px-2 py-2 text-center font-semibold whitespace-nowrap border-l border-slate-100"
                            :class="row.total_percentage < 60 ? 'text-red-600' : 'text-[#003399]'"
                        >
                            {{ fmt(row.total_percentage) }}
                        </td>
                        <td
                            class="px-2 py-2 text-center font-semibold border-l border-slate-100"
                            :class="row.equivalent === 5 ? 'text-red-600' : 'text-blue-700'"
                        >
                            <span v-if="row.equivalent != null">{{ fmt(row.equivalent) }}</span>
                            <span v-else class="text-slate-300 font-normal">—</span>
                        </td>

                        <!-- Status: confirmed = static check icon, recheck = clickable pencil -->
                        <td class="px-3 py-2">
                            <span
                                v-if="row.pending_correction?.type === 'confirmed'"
                                title="Student confirmed these grades"
                                class="inline-flex items-center gap-1 text-xs font-medium text-[#3B6D11] whitespace-nowrap"
                            >
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                                Confirmed
                            </span>
                            <button
                                v-else-if="row.pending_correction?.type === 'recheck'"
                                @click="openCorrectionReview(row)"
                                :title="correctionBadgeLabel(row.pending_correction)"
                                class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap hover:opacity-80 transition"
                                :class="correctionBadgeClass(row.pending_correction)"
                            >
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                                </svg>
                                {{ correctionBadgeLabel(row.pending_correction) }}
                            </button>
                            <span v-else class="text-xs text-slate-300">—</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    @click="openEditGrades(row)"
                                    title="Edit"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button
                                    @click="deleteGradeRow(row)"
                                    title="Delete"
                                    class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Edit Grades modal -->
        <div v-if="editGradesModalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeEditGrades">
            <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-xl">

                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold text-slate-700">{{ editStudentName }}</div>
                    <button @click="closeEditGrades" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <p v-if="editGradesLoading" class="text-xs text-slate-400 py-4">Loading...</p>

                <p v-else-if="editGrades.length === 0" class="text-xs text-slate-400 py-4">
                    No grades recorded yet.
                </p>

                <div v-else class="divide-y divide-slate-100 border-t border-slate-100">
                    <div
                        v-for="g in editGrades"
                        :key="g.category + '|' + g.title"
                        class="flex items-center gap-2 py-2 text-sm"
                    >
                        <span class="flex-1 min-w-0 truncate text-slate-600">{{ g.title ?? g.category }}</span>
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
                        <button
                            @click="saveEditGrade(g)"
                            :disabled="editGradesSavingId === (g.category + '|' + g.title) || Number(g.editValue) === Number(g.score)"
                            title="Save"
                            class="shrink-0 text-slate-500 hover:text-[#003399] disabled:opacity-30 disabled:cursor-not-allowed transition"
                        >
                            <svg
                                v-if="editGradesSavingId !== (g.category + '|' + g.title)"
                                width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                            >
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/>
                                <path d="M17 21v-8H7v8"/>
                                <path d="M7 3v5h8"/>
                            </svg>
                            <svg
                                v-else
                                width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin"
                            >
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <p v-if="editGradesErrorMsg" class="text-xs text-red-500 mt-3">{{ editGradesErrorMsg }}</p>
            </div>
        </div>

        <GradeCorrectionModal
            :open="correctionModalOpen"
            :loading="correctionLoadingGrades"
            :student-name="correctionStudentName"
            :notes="correctionNotes"
            :attachment-url="correctionAttachmentUrl"
            :grades="correctionGrades"
            :error-msg="correctionErrorMsg"
            :resolving="correctionResolving"
            :status="correctionStatus"
            :decision="correctionDecision"
            :badge-label="correctionBadgeLabel"
            :badge-class="correctionBadgeClass"
            @close="closeCorrectionModal"
            @approve="approveCorrectionInline"
            @reject="rejectCorrectionInline"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useGradesTab } from '@/composables/useGradesTab';
import { useGradeCorrections } from '@/composables/useGradeCorrections';
import GradeCorrectionModal from './GradeCorrectionModal.vue';

const props = defineProps({
    section: Object,
    gradeItems: { type: Array, default: () => [] },
    gradesBreakdown: { type: Array, default: () => [] },
    currentPeriod: String,
    periods: { type: Array, required: true },
    periodLoading: { type: Boolean, default: false },
    switchPeriod: { type: Function, required: true },
});

const sectionId = props.section.id;
const gradesBreakdownRef = computed(() => props.gradesBreakdown);
const gradeItemsRef = computed(() => props.gradeItems);
const currentPeriodRef = computed(() => props.currentPeriod);

const {
    uploading,
    uploadGrades,
    gradeSearch,
    missingOnly,
    rowHasMissingGrade,
    isLowScore,
    filteredGradesBreakdown,
    selectedGradeRows,
    allGradeRowsSelected,
    toggleSelectAllGradeRows,
    deleteSelectedGradeRows,
    editGradesModalOpen,
    editGradesLoading,
    editStudentName,
    editGrades,
    editGradesSavingId,
    editGradesErrorMsg,
    openEditGrades,
    saveEditGrade,
    closeEditGrades,
    deleteGradeRow,
} = useGradesTab(sectionId, gradesBreakdownRef, gradeItemsRef, currentPeriodRef);

const {
    correctionModalOpen,
    correctionLoadingGrades,
    correctionStudentName,
    correctionNotes,
    correctionAttachmentUrl,
    correctionGrades,
    correctionErrorMsg,
    correctionResolving,
    correctionStatus,
    correctionDecision,
    correctionBadgeLabel,
    correctionBadgeClass,
    openCorrectionReview,
    closeCorrectionModal,
    approveCorrectionInline,
    rejectCorrectionInline,
} = useGradeCorrections(sectionId, currentPeriodRef);

// ---- Excel-style table helpers ----
// Same as Excel: always PTL1-4, Q1-4 and EX, even if empty.
// Also adds any other item that exists in the database but isn't in the standard list.
const buildColumns = (category, fixedTitles) => {
    const fixed = fixedTitles.map(title => ({ category, title }));
    const extras = props.gradeItems.filter(
        i => i.category === category && !fixedTitles.includes(i.title)
    );
    return [...fixed, ...extras];
};

const tpItems = computed(() =>
    buildColumns('tp', [1, 2, 3, 4].map(n => `PT/Lab ${n}`))
);
const quizItems = computed(() =>
    buildColumns('long_quiz', [1, 2, 3, 4].map(n => `Quiz ${n}`))
);
const examItems = computed(() => buildColumns('exam', ['Exam']));

const periodLabel = computed(
    () => props.periods.find(p => p.value === props.currentPeriod)?.label ?? ''
);

// ---- Table sorting (display-only; doesn't touch rank, which stays server-computed) ----
const sortOrder = ref('rank');

const sortedGradesBreakdown = computed(() => {
    const rows = [...filteredGradesBreakdown.value];

    switch (sortOrder.value) {
        case 'grade_desc':
            return rows.sort((a, b) => b.total_percentage - a.total_percentage);
        case 'grade_asc':
            return rows.sort((a, b) => a.total_percentage - b.total_percentage);
        case 'name_asc':
            return rows.sort((a, b) => a.name.localeCompare(b.name));
        case 'name_desc':
            return rows.sort((a, b) => b.name.localeCompare(a.name));
        default:
            return rows.sort((a, b) => a.rank - b.rank);
    }
});

const keyOf = (item) => item.category + '|' + item.title;

const cell = (row, item) => row.scores?.[keyOf(item)] ?? null;

// Max score of an item, taken from the first student that has a grade for it
const itemMax = (item) => {
    for (const row of props.gradesBreakdown) {
        const c = cell(row, item);
        if (c) return Number(c.max_score);
    }
    return null;
};

const groupMax = (items) => {
    const total = items.reduce((sum, item) => sum + (itemMax(item) ?? 0), 0);
    return total > 0 ? total : '';
};

// "PT/Lab 1" -> PTL1, "Quiz 2" -> Q2, "Exam" -> EX
const shortLabel = (item) => {
    const n = (item.title.match(/\d+/) || [''])[0];
    if (item.category === 'tp') return 'PTL' + n;
    if (item.category === 'long_quiz') return 'Q' + n;
    if (item.category === 'exam') return 'EX' + n;
    return item.title;
};

const fmt = (v) => Number(v).toFixed(2);
</script>

<style scoped>
.grade-score-input {
    -moz-appearance: textfield;
    appearance: textfield;
}
.grade-score-input::-webkit-outer-spin-button,
.grade-score-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    appearance: none;
    margin: 0;
}
</style>