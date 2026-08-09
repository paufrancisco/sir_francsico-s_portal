import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

/**
 * @param {number|string} sectionId
 * @param {import('vue').Ref<Array>} students - reactive ref/computed of the students prop
 */
export function useMasterlist(sectionId, students) {
    const studentSearch = ref('');

    const filteredStudents = computed(() => {
        const q = studentSearch.value.trim().toLowerCase();
        if (!q) return students.value;

        return students.value.filter((s) =>
            s.student_number.toLowerCase().includes(q) ||
            s.full_name.toLowerCase().includes(q)
        );
    });

    // ---- Checkboxes + bulk delete ----
    const selectedStudents = ref([]);

    const allStudentsSelected = computed(() =>
        filteredStudents.value.length > 0
        && filteredStudents.value.every((s) => selectedStudents.value.includes(s.id))
    );

    const toggleSelectAllStudents = () => {
        if (allStudentsSelected.value) {
            const filteredIds = new Set(filteredStudents.value.map((s) => s.id));
            selectedStudents.value = selectedStudents.value.filter((id) => !filteredIds.has(id));
        } else {
            const currentIds = new Set(selectedStudents.value);
            filteredStudents.value.forEach((s) => currentIds.add(s.id));
            selectedStudents.value = Array.from(currentIds);
        }
    };

    const deleteSelectedStudents = () => {
        if (!confirm(`Are you sure you want to delete the ${selectedStudents.value.length} selected students?`)) {
            return;
        }

        router.delete(`/paulo/sections/${sectionId}/students`, {
            data: { student_ids: selectedStudents.value },
            preserveScroll: true,
            onSuccess: () => { selectedStudents.value = []; },
        });
    };

    const deleteStudent = (student) => {
        if (!confirm(`Are you sure you want to delete ${student.full_name}?`)) {
            return;
        }

        router.delete(`/paulo/sections/${sectionId}/students/${student.id}`, {
            preserveScroll: true,
        });
    };

    // ---- Add student modal ----
    const addStudentModalOpen = ref(false);
    const addStudentForm = useForm({
        student_number: '',
        full_name: '',
        password: '',
    });

    const openAddStudent = () => {
        addStudentForm.reset();
        addStudentForm.clearErrors();
        addStudentModalOpen.value = true;
    };

    const closeAddStudent = () => {
        addStudentModalOpen.value = false;
    };

    const submitAddStudent = () => {
        addStudentForm.post(`/paulo/sections/${sectionId}/students`, {
            preserveScroll: true,
            onSuccess: () => { addStudentModalOpen.value = false; },
        });
    };

    // ---- Edit student modal ----
    const editStudentModalOpen = ref(false);
    const editingStudentId = ref(null);
    const editStudentForm = useForm({
        student_number: '',
        full_name: '',
        password: '',
    });

    const openEditStudent = (student) => {
        editingStudentId.value = student.id;
        editStudentForm.student_number = student.student_number;
        editStudentForm.full_name = student.full_name;
        editStudentForm.password = '';
        editStudentForm.clearErrors();
        editStudentModalOpen.value = true;
    };

    const closeEditStudent = () => {
        editStudentModalOpen.value = false;
    };

    const submitEditStudent = () => {
        editStudentForm.transform((data) => ({
            ...data,
            _method: 'patch',
        })).post(`/paulo/sections/${sectionId}/students/${editingStudentId.value}`, {
            preserveScroll: true,
            onSuccess: () => { editStudentModalOpen.value = false; },
        });
    };

    const initials = (name) => {
        if (!name) return '?';
        return name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((n) => n[0].toUpperCase())
            .join('');
    };

    return {
        studentSearch,
        filteredStudents,
        selectedStudents,
        allStudentsSelected,
        toggleSelectAllStudents,
        deleteSelectedStudents,
        deleteStudent,
        addStudentModalOpen,
        addStudentForm,
        openAddStudent,
        closeAddStudent,
        submitAddStudent,
        editStudentModalOpen,
        editStudentForm,
        openEditStudent,
        closeEditStudent,
        submitEditStudent,
        initials,
    };
}