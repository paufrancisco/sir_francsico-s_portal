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

    // ---- Edit / delete state (per-message inline confirm) ----
    const editingMessageId = ref(null); // which message bubble has its confirm form open
    const editAction = ref(null); // 'edit' | 'delete'
    const editForm = reactive({ student_number: '', password: '', body: '' });
    const editError = ref('');
    const editLoading = ref(false);

    function openEditForm(message) {
        editingMessageId.value = message.id;
        editAction.value = 'edit';
        editForm.student_number = '';
        editForm.password = '';
        editForm.body = message.body;
        editError.value = '';
    }

    function openDeleteForm(message) {
        editingMessageId.value = message.id;
        editAction.value = 'delete';
        editForm.student_number = '';
        editForm.password = '';
        editError.value = '';
    }

    function cancelEditForm() {
        editingMessageId.value = null;
        editAction.value = null;
        editForm.student_number = '';
        editForm.password = '';
        editForm.body = '';
        editError.value = '';
    }

    async function submitEditMessage(message, student) {
        editError.value = '';

        if (!editForm.student_number.trim() || !editForm.password) {
            editError.value = 'Enter your student number and password.';
            return;
        }
        if (!editForm.body.trim()) {
            editError.value = 'Message cannot be empty.';
            return;
        }

        editLoading.value = true;
        try {
            const res = await fetch(`${SEND_URL}/${message.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...csrfHeaders(),
                },
                body: JSON.stringify({
                    student_number: editForm.student_number.trim(),
                    password: editForm.password,
                    body: editForm.body.trim(),
                }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                editError.value =
                    data.message ||
                    Object.values(data.errors ?? {})[0]?.[0] ||
                    'Could not update. Check your login and try again.';
                return;
            }

            cancelEditForm();
            loadMessages(student, true);
        } catch (e) {
            editError.value = 'Network error. Please try again.';
        } finally {
            editLoading.value = false;
        }
    }

    async function submitDeleteMessage(message, student) {
        editError.value = '';

        if (!editForm.student_number.trim() || !editForm.password) {
            editError.value = 'Enter your student number and password.';
            return;
        }

        editLoading.value = true;
        try {
            const res = await fetch(`${SEND_URL}/${message.id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...csrfHeaders(),
                },
                body: JSON.stringify({
                    student_number: editForm.student_number.trim(),
                    password: editForm.password,
                }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                editError.value =
                    data.message ||
                    Object.values(data.errors ?? {})[0]?.[0] ||
                    'Could not delete. Check your login and try again.';
                return;
            }

            cancelEditForm();
            loadMessages(student, true);
        } catch (e) {
            editError.value = 'Network error. Please try again.';
        } finally {
            editLoading.value = false;
        }
    }
    // ---- End edit / delete state ----

    return {
        msgModalOpen, msgForm, msgError, msgSuccess, msgLoading, showMsgPassword,
        lastPostedSender,
        openMessageModal, closeMessageModal, submitMessage,
        messageState, loadMessages,

        // edit / delete
        editingMessageId, editAction, editForm, editError, editLoading,
        openEditForm, openDeleteForm, cancelEditForm, submitEditMessage, submitDeleteMessage,
    };
}