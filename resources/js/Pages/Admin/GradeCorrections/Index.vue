<template>
    <div class="p-6">
        <h1 class="text-lg font-semibold text-slate-800 mb-4">Grade Correction Requests</h1>

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
                    <tr v-for="c in corrections" :key="c.id">
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
                        <td class="px-4 py-3 text-slate-600 max-w-xs">{{ c.notes ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="c.status === 'resolved' ? 'bg-slate-100 text-slate-500' : 'bg-[#E6F1FB] text-[#003399]'"
                            >
                                {{ c.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">
                            {{ new Date(c.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    @click="openGrades(c)"
                                    title="Edit Grades"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button
                                    v-if="c.status === 'pending'"
                                    @click="resolve(c.id)"
                                    title="Mark resolved"
                                    class="p-1.5 rounded-lg text-[#003399] hover:bg-[#E6F1FB] transition"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6 9 17l-5-5"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="corrections.length === 0">
                        <td colspan="7" class="text-center text-slate-400 text-sm py-8">
                            Wala pang grade correction requests.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Edit Grades Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeModal">
            <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-xl">

                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-700">{{ activeStudentName }}</div>
                        <div class="text-xs text-slate-400">{{ activeNotes ?? 'Walang notes' }}</div>
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <p v-if="loadingGrades" class="text-xs text-slate-400 py-4">Naglo-load...</p>

                <p v-else-if="grades.length === 0" class="text-xs text-slate-400 py-4">
                    Wala pang na-record na grades.
                </p>

                <div v-else class="divide-y divide-slate-100 border-t border-slate-100">
                    <div
                        v-for="g in grades"
                        :key="g.id"
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
                            @click="saveGrade(g)"
                            :disabled="savingId === g.id || Number(g.editValue) === Number(g.score)"
                            title="Save"
                            class="shrink-0 text-slate-500 hover:text-[#003399] disabled:opacity-30 disabled:cursor-not-allowed transition"
                        >
                            <svg
                                v-if="savingId !== g.id"
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

                <p v-if="errorMsg" class="text-xs text-red-500 mt-3">{{ errorMsg }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    corrections: { type: Array, default: () => [] },
});

const resolve = (id) => {
    router.patch(`/paulo/grade-corrections/${id}/resolve`, {}, { preserveScroll: true });
};

// ---- Edit Grades modal ----
const showModal = ref(false);
const loadingGrades = ref(false);
const activeStudentName = ref('');
const activeNotes = ref('');
const grades = ref([]);
const savingId = ref(null);
const errorMsg = ref('');

const openGrades = async (correction) => {
    showModal.value = true;
    loadingGrades.value = true;
    errorMsg.value = '';
    activeStudentName.value = correction.student_name;
    activeNotes.value = correction.notes;
    grades.value = [];

    try {
        const res = await axios.get(`/paulo/students/${correction.student_id}/grades`);
        grades.value = res.data.grades.map((g) => ({ ...g, editValue: g.score }));
    } catch (e) {
        errorMsg.value = 'Hindi na-load ang grades ng estudyante.';
    } finally {
        loadingGrades.value = false;
    }
};

const saveGrade = async (grade) => {
    savingId.value = grade.id;
    errorMsg.value = '';

    try {
        const res = await axios.patch(`/paulo/grades/${grade.id}`, { score: grade.editValue });
        grade.score = res.data.grade.score;
    } catch (e) {
        errorMsg.value = e.response?.data?.message
            || Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
            || 'Hindi na-save ang grade.';
    } finally {
        savingId.value = null;
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