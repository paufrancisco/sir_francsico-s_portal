import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * @param {number|string} sectionId
 * @param {import('vue').Ref<Array>} gradesBreakdown
 * @param {import('vue').Ref<Array>} gradeItems
 * @param {import('vue').Ref<string>} currentPeriod
 */
export function useGradesTab(sectionId, gradesBreakdown, gradeItems, currentPeriod) {
    // ---- Import grades (Excel) ----
    const uploading = ref(false);

    const uploadGrades = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        uploading.value = true;
        router.post(`/paulo/sections/${sectionId}/grades/import`, { file, period: currentPeriod.value }, {
            forceFormData: true,
            onFinish: () => { uploading.value = false; e.target.value = ''; },
        });
    };

    // ---- Search + missing-grade filter ----
    const gradeSearch = ref('');
    const missingOnly = ref(false);

    const rowHasMissingGrade = (row) =>
        gradeItems.value.some((item) => {
            const entry = row.scores[item.category + '|' + item.title];
            return !entry || Number(entry.score) === 0;
        });

    const isLowScore = (entry) => {
        if (!entry || Number(entry.max_score) === 0) return false;
        return (Number(entry.score) / Number(entry.max_score)) * 100 < 60;
    };

    const filteredGradesBreakdown = computed(() => {
        const q = gradeSearch.value.trim().toLowerCase();

        return gradesBreakdown.value.filter((row) => {
            const matchesSearch = !q
                || row.name.toLowerCase().includes(q)
                || String(row.student_number ?? '').toLowerCase().includes(q);
            const matchesFilter = !missingOnly.value || rowHasMissingGrade(row);
            return matchesSearch && matchesFilter;
        });
    });

    // ---- Checkboxes + bulk delete ----
    const selectedGradeRows = ref([]);

    const allGradeRowsSelected = computed(() =>
        filteredGradesBreakdown.value.length > 0
        && filteredGradesBreakdown.value.every((r) => selectedGradeRows.value.includes(r.id))
    );

    const toggleSelectAllGradeRows = () => {
        if (allGradeRowsSelected.value) {
            const filteredIds = new Set(filteredGradesBreakdown.value.map((r) => r.id));
            selectedGradeRows.value = selectedGradeRows.value.filter((id) => !filteredIds.has(id));
        } else {
            const currentIds = new Set(selectedGradeRows.value);
            filteredGradesBreakdown.value.forEach((r) => currentIds.add(r.id));
            selectedGradeRows.value = Array.from(currentIds);
        }
    };

    const deleteSelectedGradeRows = () => {
        if (!confirm(`Are you sure you want to clear the grades of the ${selectedGradeRows.value.length} selected students?`)) {
            return;
        }

        router.delete(`/paulo/sections/${sectionId}/grades`, {
            data: { student_ids: selectedGradeRows.value },
            preserveScroll: true,
            onSuccess: () => { selectedGradeRows.value = []; },
        });
    };

    // ---- Edit grades modal (per row) ----
    const editGradesModalOpen = ref(false);
    const editGradesLoading = ref(false);
    const editStudentName = ref('');
    const editGrades = ref([]);
    const editGradesSavingId = ref(null);
    const editGradesErrorMsg = ref('');

    const openEditGrades = async (row) => {
        editGradesModalOpen.value = true;
        editGradesLoading.value = true;
        editGradesErrorMsg.value = '';
        editStudentName.value = row.name;
        editGrades.value = [];

        try {
            const res = await axios.get(`/paulo/sections/${sectionId}/students/${row.id}/grades`, {
                params: { period: currentPeriod.value },
            });
            editGrades.value = res.data.grades.map((g) => ({
                ...g,
                editValue: g.score ?? '',
            }));
        } catch (e) {
            editGradesErrorMsg.value = "Failed to load the student's grades.";
        } finally {
            editGradesLoading.value = false;
        }
    };

    const saveEditGrade = async (grade) => {
        const key = grade.category + '|' + grade.title;
        editGradesSavingId.value = key;
        editGradesErrorMsg.value = '';

        try {
            if (grade.id) {
                const res = await axios.patch(`/paulo/grades/${grade.id}`, { score: grade.editValue });
                grade.score = res.data.grade.score;
            } else {
                const res = await axios.post(`/paulo/grades`, {
                    student_id: grade.student_id,
                    section_id: grade.section_id,
                    category: grade.category,
                    period: grade.period ?? currentPeriod.value,
                    title: grade.title,
                    score: grade.editValue,
                    max_score: grade.max_score,
                });
                grade.id = res.data.grade.id;
                grade.score = res.data.grade.score;
            }
            router.reload({ only: ['gradesBreakdown'] });
        } catch (e) {
            editGradesErrorMsg.value = e.response?.data?.message
                || Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
                || 'Failed to save the grade.';
        } finally {
            editGradesSavingId.value = null;
        }
    };

    const closeEditGrades = () => {
        editGradesModalOpen.value = false;
    };

    const deleteGradeRow = (row) => {
        if (!confirm(`Are you sure you want to delete all of ${row.name}'s grades in this section?`)) {
            return;
        }

        router.delete(`/paulo/sections/${sectionId}/students/${row.id}/grades`, {
            preserveScroll: true,
        });
    };

    return {
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
    };
}