import { reactive, ref } from 'vue';

// Expects POST: student_number, password, body
// Backend verifies the credentials, then always posts to the sender's OWN card
// (recipient_id === sender_id) — there is no separate recipient to pick.
const SEND_URL = '/portal/classmate-messages';
export const MESSAGE_MAX_LENGTH = 300;

// GET on the same route returns { messages: [{ id, sender_name, body, created_at }] }
// Query: recipient_id (or recipient_name when there's no id).

// Meta tag if the layout has one, otherwise the XSRF-TOKEN cookie that Laravel sets (what axios uses)
function csrfHeaders() {
    const meta = document.querySelector('meta[name="csrf-token"]')?.content;
    if (meta) return { 'X-CSRF-TOKEN': meta };
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}

export function useClassmateMessages() {
    const msgModalOpen = ref(false);
    const msgError = ref('');
    const msgSuccess = ref('');
    const msgLoading = ref(false);
    const showMsgPassword = ref(false);
    const msgForm = reactive({ student_number: '', password: '', body: '' });

    // Set only after a successful post — the sender that the backend verified.
    // Used to know whose card's messages to refresh.
    const lastPostedSender = ref(null);

    // Messages shown under each photo, cached per student
    const messageStates = reactive({});

    function studentKey(student) {
        return student?.id ?? student?.student_id ?? student?.name ?? null;
    }

    function messageState(student) {
        const key = studentKey(student);
        return (key !== null && messageStates[key]) || { status: 'idle', items: [] };
    }

    async function loadMessages(student, force = false) {
        const key = studentKey(student);
        if (key === null) return;

        const current = messageStates[key];
        if (!force && current && (current.status === 'ready' || current.status === 'loading')) return;

        messageStates[key] = { status: 'loading', items: current?.items ?? [] };
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const params = new URLSearchParams();
            const id = student.id ?? student.student_id;
            if (id) params.set('recipient_id', id);
            else params.set('recipient_name', student.name);

            const res = await fetch(`${SEND_URL}?${params}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error('load failed');
            const data = await res.json();
            messageStates[key] = { status: 'ready', items: data.messages ?? [] };
        } catch (e) {
            messageStates[key] = { status: 'error', items: current?.items ?? [] };
        } finally {
            clearTimeout(timeout);
        }
    }

    function resetForm() {
        msgForm.student_number = '';
        msgForm.password = '';
        msgForm.body = '';
        msgError.value = '';
        msgSuccess.value = '';
        showMsgPassword.value = false;
    }

    // Walang target/recipient na kailangang ipasa — kahit sino ang nag-login,
    // sarili niyang card ang pupuntahan ng post.
    function openMessageModal() {
        resetForm();
        lastPostedSender.value = null;
        msgModalOpen.value = true;
    }

    function closeMessageModal() {
        msgModalOpen.value = false;
        resetForm();
    }

    async function submitMessage() {
        msgError.value = '';

        if (!msgForm.student_number.trim() || !msgForm.password) {
            msgError.value = 'Enter your student number and password.';
            return;
        }
        if (!msgForm.body.trim()) {
            msgError.value = 'Write a message first.';
            return;
        }

        msgLoading.value = true;
        try {
            const res = await fetch(SEND_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...csrfHeaders(),
                },
                body: JSON.stringify({
                    student_number: msgForm.student_number.trim(),
                    password: msgForm.password,
                    body: msgForm.body.trim(),
                }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                msgError.value =
                    data.message ||
                    Object.values(data.errors ?? {})[0]?.[0] ||
                    'Could not post. Check your login and try again.';
                return;
            }

            msgSuccess.value = data.message || 'Posted.';
            // Backend tells us exactly who posted — this IS the recipient card now.
            lastPostedSender.value = data.sender ?? null;
            msgForm.password = '';
            msgForm.body = '';
            // Refresh the sender's own card so the new post shows up right away.
            if (lastPostedSender.value) {
                loadMessages(lastPostedSender.value, true);
            }
        } catch (e) {
            msgError.value = 'Network error. Please try again.';
        } finally {
            msgLoading.value = false;
        }
    }

    return {
        msgModalOpen, msgForm, msgError, msgSuccess, msgLoading, showMsgPassword,
        lastPostedSender,
        openMessageModal, closeMessageModal, submitMessage,
        messageState, loadMessages,
    };
}