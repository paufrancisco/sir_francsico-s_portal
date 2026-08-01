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
            Header format: <code>Long Quiz: Quiz 1 (50)</code>, <code>TP: Project 1 (100)</code>, <code>Exam: Midterm (100)</code> — Column A = Student Number.
        </p>

        <!-- Period tabs -->
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

        <p class="text-[11px] text-slate-500">
            Will be imported as <span class="font-semibold text-[#003399]">{{ periods.find(p => p.value === currentPeriod)?.label }}</span> — select the correct tab above before clicking "Import grades".
        </p>

        <p class="text-[11px] text-slate-400">
            Total % = (Quiz % × 20%) + (TP % × 30%) + (Exam % × 50%)
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
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-x-auto max-w-full">
            <p v-if="gradesBreakdown.length === 0" class="text-xs text-slate-400 px-4 py-6">
                No grades imported yet.
            </p>
            <p v-else-if="filteredGradesBreakdown.length === 0" class="text-xs text-slate-400 px-4 py-6">
                No matching students.
            </p>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-xs text-slate-500">
                        <th class="px-3 py-2 w-8">
                            <input
                                type="checkbox"
                                :checked="allGradeRowsSelected"
                                @change="toggleSelectAllGradeRows"
                                class="rounded border-slate-300"
                            />
                        </th>
                        <th class="px-3 py-2">Rank</th>
                        <th class="px-3 py-2">ID No.</th>
                        <th class="px-3 py-2">Name</th>
                        <th v-for="item in gradeItems" :key="item.category + item.title" class="px-2 py-2 text-center w-16">
                            {{ item.title }}
                        </th>
                        <th class="px-2 py-2 text-center w-16">Quiz %</th>
                        <th class="px-2 py-2 text-center w-16">TP %</th>
                        <th class="px-2 py-2 text-center w-16">Exam %</th>
                        <th class="px-3 py-2">Total %</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in filteredGradesBreakdown" :key="row.id" class="border-t border-slate-100">
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
                        <td v-for="item in gradeItems" :key="item.category + item.title" class="px-2 py-2 text-slate-600 text-center">
                            <template v-if="row.scores[item.category + '|' + item.title]">
                                <div
                                    class="leading-tight inline-block rounded px-1"
                                    :class="isLowScore(row.scores[item.category + '|' + item.title]) ? 'bg-red-50' : ''"
                                >
                                    <div class="font-medium" :class="isLowScore(row.scores[item.category + '|' + item.title]) ? 'text-red-600' : ''">
                                        {{ row.scores[item.category + '|' + item.title].score }}
                                    </div>
                                    <div class="text-[10px]" :class="isLowScore(row.scores[item.category + '|' + item.title]) ? 'text-red-400' : 'text-slate-400'">
                                        /{{ row.scores[item.category + '|' + item.title].max_score }}
                                    </div>
                                </div>
                            </template>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="px-2 py-2 text-center text-slate-500">
                            <span v-if="row.category_percentages?.long_quiz !== null && row.category_percentages?.long_quiz !== undefined">{{ row.category_percentages.long_quiz }}%</span>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="px-2 py-2 text-center text-slate-500">
                            <span v-if="row.category_percentages?.tp !== null && row.category_percentages?.tp !== undefined">{{ row.category_percentages.tp }}%</span>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="px-2 py-2 text-center text-slate-500">
                            <span v-if="row.category_percentages?.exam !== null && row.category_percentages?.exam !== undefined">{{ row.category_percentages.exam }}%</span>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="px-3 py-2 font-semibold whitespace-nowrap" :class="row.total_percentage < 60 ? 'text-red-600' : 'text-[#003399]'">{{ row.total_percentage }}%</td>
                        <td class="px-3 py-2">
                            <button
                                v-if="row.pending_correction"
                                @click="openCorrectionReview(row)"
                                class="text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap hover:opacity-80 transition"
                                :class="correctionBadgeClass(row.pending_correction)"
                            >
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
import { computed } from 'vue';
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