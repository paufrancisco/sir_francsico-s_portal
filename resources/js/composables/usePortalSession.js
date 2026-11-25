import axios from 'axios';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const student = ref(null);
let initialized = false;

export function usePortalSession() {
    if (!initialized) {
        student.value = usePage().props.auth?.student ?? null;
        initialized = true;
    }

    const login = async (studentNumber, password) => {
        const { data } = await axios.post('/portal/login', {
            student_number: studentNumber.trim(),
            password,
        });

        if (data.student) student.value = data.student;

        return data;
    };

    const logout = async () => {
        await axios.post('/portal/logout');
        student.value = null;
    };

    return { student, login, logout };
}
