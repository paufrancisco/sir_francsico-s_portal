import { reactive, ref } from 'vue';

// Expects POST: student_number, password, optional frame
// No frame = sign-in check only (returns the current frame).
// With frame = saves it. Response: { message, student: { id, name, photo_url, frame } }
const FRAME_URL = '/portal/student-frame';

export const FRAME_OPTIONS = [
    { key: 'gold', label: 'Gold' },
    { key: 'aurora', label: 'Aurora' },
    { key: 'neon', label: 'Neon' },
    { key: 'ice', label: 'Ice' },
    { key: 'rose', label: 'Rose' },
    { key: 'royal', label: 'Royal' },
    { key: 'flame', label: 'Flame' },
    { key: 'rainbow', label: 'Rainbow' },
];

// Frames saved during this page session, keyed by student id.
// The photo card reads this first, then falls back to the server value.
const appliedFrames = reactive({});

export function frameFor(student, fallback = 'gold') {
    const id = student?.id ?? student?.student_id;
    return (id != null && appliedFrames[id]) || student?.frame || fallback;
}

function apiError(data, fallback, res) {
    if (res?.status === 429) {
        const wait = parseInt(res.headers.get('Retry-After') ?? '', 10);
        return wait > 0
            ? `Too many attempts. Try again in ${wait} second${wait === 1 ? '' : 's'}.`
            : 'Too many attempts. Please wait a moment and try again.';
    }
    return Object.values(data.errors ?? {})[0]?.[0] || data.message || fallback;
}

function csrfHeaders() {
    const meta = document.querySelector('meta[name="csrf-token"]')?.content;
    if (meta) return { 'X-CSRF-TOKEN': meta };
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}

export function useStudentFrame() {
    const frameModalOpen = ref(false);
    const frameStep = ref('login'); // 'login' | 'pick'
    const frameLoading = ref(false);
    const frameError = ref('');
    const frameSuccess = ref('');
    const showFramePassword = ref(false);
    const frameForm = reactive({ student_number: '', password: '' });
    const selectedFrame = ref('gold');
    const frameOwner = ref(null);

    async function post(payload) {
        const res = await fetch(FRAME_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...csrfHeaders(),
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        return { res, data };
    }

    function resetFrameForm() {
        frameForm.student_number = '';
        frameForm.password = '';
        frameError.value = '';
        frameSuccess.value = '';
        showFramePassword.value = false;
        frameStep.value = 'login';
        selectedFrame.value = 'gold';
        frameOwner.value = null;
    }

    function openFrameModal() {
        resetFrameForm();
        frameModalOpen.value = true;
    }

    function closeFrameModal() {
        frameModalOpen.value = false;
        resetFrameForm();
    }

    async function signInForFrame() {
        frameError.value = '';
        if (!frameForm.student_number.trim() || !frameForm.password) {
            frameError.value = 'Enter your student number and password.';
            return;
        }

        frameLoading.value = true;
        try {
            const { res, data } = await post({
                student_number: frameForm.student_number.trim(),
                password: frameForm.password,
            });
            if (!res.ok) {
                frameError.value = apiError(data, 'Could not sign in. Check your login and try again.', res);
                return;
            }
            frameOwner.value = data.student ?? null;
            selectedFrame.value = data.student?.frame ?? 'gold';
            frameStep.value = 'pick';
        } catch (e) {
            frameError.value = 'Network error. Please try again.';
        } finally {
            frameLoading.value = false;
        }
    }

    async function saveFrame() {
        frameError.value = '';
        frameSuccess.value = '';

        frameLoading.value = true;
        try {
            const { res, data } = await post({
                student_number: frameForm.student_number.trim(),
                password: frameForm.password,
                frame: selectedFrame.value,
            });
            if (!res.ok) {
                frameError.value = apiError(data, 'Could not save. Check your login and try again.', res);
                return;
            }
            frameSuccess.value = data.message || 'Frame saved.';
            if (data.student?.id != null) {
                appliedFrames[data.student.id] = data.student.frame ?? 'gold';
            }
        } catch (e) {
            frameError.value = 'Network error. Please try again.';
        } finally {
            frameLoading.value = false;
        }
    }

    return {
        frameModalOpen, frameStep, frameLoading, frameError, frameSuccess,
        showFramePassword, frameForm, selectedFrame, frameOwner,
        openFrameModal, closeFrameModal, signInForFrame, saveFrame,
    };
}
