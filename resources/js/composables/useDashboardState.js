import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * All reactive state + logic for the Student Dashboard portal
 * (dark mode, class ledger carousel, chat widget, grades modal,
 * grade correction/recheck flow, appointment booking, standalone
 * change-password, forced password change, FAQ).
 *
 * @param {object} props - the props object from Dashboard.vue (defineProps(...))
 */
export function useDashboardState(props) {
    const showFullList = ref(false);
    const chatOpen = ref(false);
    const paused = ref(false);
    const currentIndex = ref(0);
    const slideDirection = ref('slide-next');

    // ---- Dark mode state ----
    const isDarkMode = ref(false);

    onMounted(() => {
        const stored = localStorage.getItem('portalTheme');
        if (stored) isDarkMode.value = stored === 'dark';
        else isDarkMode.value = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
    });

    const toggleDarkMode = () => {
        isDarkMode.value = !isDarkMode.value;
        localStorage.setItem('portalTheme', isDarkMode.value ? 'dark' : 'light');
    };
    // ---- End dark mode state ----

    const activeSectionId = ref(props.sections[0]?.id ?? null);

    const activeSectionLabel = computed(() => {
        const s = props.sections.find((sec) => sec.id === activeSectionId.value);
        if (!s) return '';
        return s.subject ? `${s.subject} - ${s.name}` : s.name;
    });

    const filteredAnnouncements = computed(() => props.announcementsBySection[activeSectionId.value] ?? []);
    const filteredTopics = computed(() => props.topicsBySection[activeSectionId.value] ?? []);
    const filteredStudents = computed(() => props.top10BySection[activeSectionId.value] ?? []);

    const currentStudent = computed(() => filteredStudents.value[currentIndex.value] ?? filteredStudents.value[0]);

    // ---- Snapshot stats (derived, no backend change needed) ----
    const latestAnnouncement = computed(() => filteredAnnouncements.value[0] ?? null);

    watch(activeSectionId, () => { currentIndex.value = 0; });

    const goTo = (i) => {
        slideDirection.value = i > currentIndex.value ? 'slide-next' : 'slide-prev';
        currentIndex.value = i;
    };

    // ---- Chat widget state ----
    const chatStudent = ref(null);
    const chatLogin = ref({ student_number: '', password: '' });
    const chatLoginError = ref('');
    const chatLoginLoading = ref(false);
    const showChatLoginPassword = ref(false);
    const chatMustChangePassword = ref(false);
    const chatMessages = ref([]);
    const chatInput = ref('');
    const chatSending = ref(false);
    const chatScrollEl = ref(null);
    let chatPollTimer;

    const scrollChatToBottom = () => {
        nextTick(() => {
            if (chatScrollEl.value) chatScrollEl.value.scrollTop = chatScrollEl.value.scrollHeight;
        });
    };

    const signInChat = async () => {
        chatLoginLoading.value = true;
        chatLoginError.value = '';
        try {
            const { data } = await axios.post('/portal/chat/verify', chatLogin.value);
            if (data.must_change_password) {
                chatMustChangePassword.value = true;
                passwordChangeContext.value = 'chat';
                return;
            }
            chatStudent.value = data;
            await loadChatHistory();
            chatPollTimer = setInterval(loadChatHistory, 6000);
        } catch (err) {
            chatLoginError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            chatLoginLoading.value = false;
        }
    };

    const loadChatHistory = async () => {
        if (!chatStudent.value) return;
        const { data } = await axios.get('/portal/chat/history', {
            params: { student_id: chatStudent.value.student_id },
        });
        chatMessages.value = data.messages;
        scrollChatToBottom();
    };

    const sendChatMessage = async () => {
        if (!chatInput.value.trim()) return;
        chatSending.value = true;
        const body = chatInput.value;
        chatInput.value = '';
        scrollChatToBottom();
        try {
            const { data } = await axios.post('/portal/chat/send', {
                student_id: chatStudent.value.student_id,
                body,
            });
            chatMessages.value.push(...data.messages);
            scrollChatToBottom();
        } catch (err) {
            chatMessages.value.push({ id: Date.now(), sender: 'ai', body: 'Something went wrong, please try again later.' });
        } finally {
            chatSending.value = false;
        }
    };
    // ---- End chat widget state ----

    // ---- Grades modal state ----
    const gradesModalOpen = ref(false);
    const gradesForm = ref({ student_number: '', password: '' });
    const gradesError = ref('');
    const gradesLoading = ref(false);
    const showGradesLoginPassword = ref(false);
    const gradesResult = ref(null);
    const gradesPeriod = ref('prelim');
    const gradesMustChangePassword = ref(false);

    const periods = [
        { value: 'prelim', label: 'Prelim' },
        { value: 'midterm', label: 'Midterm' },
        { value: 'prefinal', label: 'Pre-Final' },
        { value: 'finals', label: 'Finals' },
    ];

    const switchGradesPeriod = async (period) => {
        if (period === gradesPeriod.value || gradesLoading.value) return;
        gradesPeriod.value = period;
        cancelEditingRecheckForm();
        correctionSuccessMessage.value = '';
        gradesLoading.value = true;
        gradesError.value = '';
        try {
            const { data } = await axios.post('/portal/grades/verify', {
                ...gradesForm.value,
                period,
            });
            gradesResult.value = data;
        } catch (err) {
            gradesError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            gradesLoading.value = false;
        }
    };

    // ---- Grade correction state ----
    const showRecheckForm = ref(false);
    const recheckNotes = ref('');
    const correctionLoading = ref(false);
    const correctionError = ref('');
    const correctionSuccessMessage = ref('');

    // per-item edit state: key is `${category}|${title}`
    const editedItems = ref({});
    const editingItemKey = ref(null);
    const editDraftScore = ref('');

    // attachment (required proof, image only, max 10MB)
    const correctionAttachment = ref(null);
    const correctionAttachmentError = ref('');

    const hasExistingAttachment = computed(() => !!gradesResult.value?.pending_correction?.attachment_url);

    // idle | editing | pending
    const correctionUiState = computed(() => {
        if (showRecheckForm.value) return 'editing';
        return gradesResult.value?.pending_correction ? 'pending' : 'idle';
    });

    const itemKeyOf = (item) => item.category + '|' + item.title;

    const startEditItem = (item) => {
        const key = itemKeyOf(item);
        editingItemKey.value = key;
        editDraftScore.value = editedItems.value[key]
            ? editedItems.value[key].claimed_score
            : (gradesResult.value.scores[key]?.score ?? '');
    };

    const confirmEditItem = (item) => {
        const key = itemKeyOf(item);
        const val = parseFloat(editDraftScore.value);
        if (Number.isNaN(val) || val < 0) {
            correctionError.value = 'Invalid score.';
            return;
        }
        editedItems.value = {
            ...editedItems.value,
            [key]: { category: item.category, title: item.title, claimed_score: val },
        };
        editingItemKey.value = null;
        editDraftScore.value = '';
        correctionError.value = '';
    };

    const cancelEditItem = () => {
        editingItemKey.value = null;
        editDraftScore.value = '';
    };

    const removeEditedItem = (item) => {
        const key = itemKeyOf(item);
        const copy = { ...editedItems.value };
        delete copy[key];
        editedItems.value = copy;
    };

    const onAttachmentChange = (e) => {
        correctionAttachmentError.value = '';
        const file = e.target.files[0];
        if (!file) {
            correctionAttachment.value = null;
            return;
        }
        if (!file.type.startsWith('image/')) {
            correctionAttachmentError.value = 'Only images are accepted.';
            e.target.value = '';
            correctionAttachment.value = null;
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            correctionAttachmentError.value = 'The image must not exceed 10MB.';
            e.target.value = '';
            correctionAttachment.value = null;
            return;
        }
        correctionAttachment.value = file;
    };

    const cancelEditingRecheckForm = () => {
        showRecheckForm.value = false;
        editedItems.value = {};
        editingItemKey.value = null;
        editDraftScore.value = '';
        recheckNotes.value = '';
        correctionAttachment.value = null;
        correctionAttachmentError.value = '';
        correctionError.value = '';
    };

    const startEditExistingCorrection = () => {
        const c = gradesResult.value.pending_correction;
        const map = {};
        (c.edited_items ?? []).forEach((item) => {
            const key = item.category + '|' + item.title;
            map[key] = { category: item.category, title: item.title, claimed_score: item.claimed_score };
        });
        editedItems.value = map;
        recheckNotes.value = c.notes ?? '';
        correctionAttachment.value = null;
        correctionAttachmentError.value = '';
        correctionError.value = '';
        correctionSuccessMessage.value = '';
        showRecheckForm.value = true;
    };

    const refetchGradesResult = async () => {
        try {
            const { data } = await axios.post('/portal/grades/verify', {
                ...gradesForm.value,
                period: gradesPeriod.value,
            });
            if (!data.must_change_password) {
                gradesResult.value = data;
            }
        } catch (err) {
            // silent — keep the current view if refetch fails
        }
    };

    const cancelCorrection = async () => {
        const c = gradesResult.value?.pending_correction;
        if (!c) return;
        correctionLoading.value = true;
        correctionError.value = '';
        try {
            await axios.delete(`/portal/grades/correction/${c.id}`, {
                data: {
                    student_number: gradesForm.value.student_number,
                    password: gradesForm.value.password,
                },
            });
            correctionSuccessMessage.value = 'Your recheck request has been cancelled.';
            await refetchGradesResult();
        } catch (err) {
            correctionError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            correctionLoading.value = false;
        }
    };

    const submitCorrection = async (type) => {
        correctionError.value = '';

        if (type === 'recheck') {
            if (Object.keys(editedItems.value).length === 0) {
                correctionError.value = 'Edit the incorrect score first before submitting.';
                return;
            }
            if (!correctionAttachment.value && !hasExistingAttachment.value) {
                correctionError.value = 'Please attach an image as proof.';
                return;
            }
        }

        correctionLoading.value = true;
        try {
            const formData = new FormData();
            formData.append('student_number', gradesForm.value.student_number);
            formData.append('password', gradesForm.value.password);
            formData.append('type', type);
            formData.append('period', gradesPeriod.value);

            if (type === 'recheck') {
                formData.append('notes', recheckNotes.value);
                formData.append('edited_items', JSON.stringify(
                    Object.values(editedItems.value).map((i) => ({
                        category: i.category,
                        title: i.title,
                        claimed_score: i.claimed_score,
                    }))
                ));
                if (correctionAttachment.value) {
                    formData.append('attachment', correctionAttachment.value);
                }
            }

            const { data } = await axios.post('/portal/grades/correction', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            correctionSuccessMessage.value = data.message;

            if (type === 'recheck') {
                cancelEditingRecheckForm();
            }

            await refetchGradesResult();
        } catch (err) {
            correctionError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            correctionLoading.value = false;
        }
    };

    const openGradesModal = () => {
        gradesModalOpen.value = true;
    };

    const closeGradesModal = () => {
        gradesModalOpen.value = false;
        gradesResult.value = null;
        gradesForm.value = { student_number: '', password: '' };
        gradesError.value = '';
        gradesPeriod.value = 'prelim';
        showGradesLoginPassword.value = false;
        gradesMustChangePassword.value = false;
        cancelEditingRecheckForm();
        correctionSuccessMessage.value = '';
        if (passwordChangeContext.value === 'grades') resetPasswordChangeForm();
    };

    const submitGradesLogin = async () => {
        gradesLoading.value = true;
        gradesError.value = '';
        gradesPeriod.value = 'prelim';
        try {
            const { data } = await axios.post('/portal/grades/verify', {
                ...gradesForm.value,
                period: 'prelim',
            });
            if (data.must_change_password) {
                gradesMustChangePassword.value = true;
                passwordChangeContext.value = 'grades';
                return;
            }
            gradesResult.value = data;
        } catch (err) {
            gradesError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            gradesLoading.value = false;
        }
    };
    // ---- End grades modal state ----

    // ---- Set an appointment modal state ----
    const appointmentModalOpen = ref(false);
    const apptForm = ref({ student_number: '', password: '' });
    const apptLoginError = ref('');
    const apptLoginLoading = ref(false);
    const showApptLoginPassword = ref(false);
    const apptMustChangePassword = ref(false);
    const apptStudent = ref(null);

    const apptSlots = ref([]);
    const apptSlotsLoading = ref(false);
    const selectedSlotId = ref(null);
    const apptReason = ref('');
    const apptExisting = ref(null); // student's current pending/approved appointment, if any
    const apptShowNewForm = ref(false);
    const apptError = ref('');
    const apptActionLoading = ref(false);

    const openAppointmentModal = () => {
        appointmentModalOpen.value = true;
    };

    const closeAppointmentModal = () => {
        appointmentModalOpen.value = false;
        apptStudent.value = null;
        apptForm.value = { student_number: '', password: '' };
        apptLoginError.value = '';
        showApptLoginPassword.value = false;
        apptMustChangePassword.value = false;
        apptSlots.value = [];
        selectedSlotId.value = null;
        apptReason.value = '';
        apptExisting.value = null;
        apptShowNewForm.value = false;
        apptError.value = '';
        if (passwordChangeContext.value === 'appointment') resetPasswordChangeForm();
    };

    const loadAppointmentData = async () => {
        apptSlotsLoading.value = true;
        apptError.value = '';
        try {
            const { data } = await axios.post('/portal/appointments/available', {
                student_number: apptForm.value.student_number,
                password: apptForm.value.password,
            });
            apptSlots.value = data.slots;
            apptExisting.value = data.existing_appointment ?? null;
            apptShowNewForm.value = !apptExisting.value;
        } catch (err) {
            apptError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            apptSlotsLoading.value = false;
        }
    };

    const submitAppointmentLogin = async () => {
        apptLoginLoading.value = true;
        apptLoginError.value = '';
        try {
            const { data } = await axios.post('/portal/appointments/verify', apptForm.value);
            if (data.must_change_password) {
                apptMustChangePassword.value = true;
                passwordChangeContext.value = 'appointment';
                return;
            }
            apptStudent.value = data;
            await loadAppointmentData();
        } catch (err) {
            apptLoginError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            apptLoginLoading.value = false;
        }
    };

    const submitAppointment = async () => {
        apptError.value = '';
        if (!selectedSlotId.value) {
            apptError.value = 'Please pick an available time slot.';
            return;
        }
        apptActionLoading.value = true;
        try {
            const { data } = await axios.post('/portal/appointments/book', {
                student_number: apptForm.value.student_number,
                password: apptForm.value.password,
                faculty_availability_id: selectedSlotId.value,
                reason: apptReason.value,
            });
            apptExisting.value = data.appointment;
            apptShowNewForm.value = false;
            selectedSlotId.value = null;
            apptReason.value = '';
        } catch (err) {
            apptError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            apptActionLoading.value = false;
        }
    };

    const cancelAppointment = async () => {
        if (!apptExisting.value) return;
        apptActionLoading.value = true;
        apptError.value = '';
        try {
            await axios.delete(`/portal/appointments/${apptExisting.value.id}`, {
                data: {
                    student_number: apptForm.value.student_number,
                    password: apptForm.value.password,
                },
            });
            await loadAppointmentData();
        } catch (err) {
            apptError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            apptActionLoading.value = false;
        }
    };
    // ---- End set an appointment modal state ----

    // ---- Standalone "Change password" modal state (voluntary, not forced) ----
    const cpModalOpen = ref(false);
    const cpForm = ref({ student_number: '', current_password: '', new_password: '', confirm_password: '' });
    const cpError = ref('');
    const cpSuccess = ref('');
    const cpLoading = ref(false);
    const showCpCurrentPassword = ref(false);
    const showCpNewPassword = ref(false);
    const showCpConfirmPassword = ref(false);

    const openChangePasswordModal = () => {
        cpModalOpen.value = true;
    };

    const closeChangePasswordModal = () => {
        cpModalOpen.value = false;
        cpForm.value = { student_number: '', current_password: '', new_password: '', confirm_password: '' };
        cpError.value = '';
        cpSuccess.value = '';
        showCpCurrentPassword.value = false;
        showCpNewPassword.value = false;
        showCpConfirmPassword.value = false;
    };

    const submitChangePassword = async () => {
        cpError.value = '';

        if (!cpForm.value.student_number.trim() || !cpForm.value.current_password) {
            cpError.value = 'Please fill in your student number and current password.';
            return;
        }
        if (cpForm.value.new_password.length < 8) {
            cpError.value = 'The new password must be at least 8 characters.';
            return;
        }
        if (cpForm.value.new_password !== cpForm.value.confirm_password) {
            cpError.value = "The new passwords don't match.";
            return;
        }

        cpLoading.value = true;
        try {
            await axios.post('/portal/grades/change-password', {
                student_number: cpForm.value.student_number,
                current_password: cpForm.value.current_password,
                new_password: cpForm.value.new_password,
                new_password_confirmation: cpForm.value.confirm_password,
            });
            cpSuccess.value = 'Your password has been updated.';
        } catch (err) {
            cpError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            cpLoading.value = false;
        }
    };
    // ---- End standalone change password modal state ----

    // ---- Force change password (first login) state ----
    // Shared by the grades, chat, and appointment sign-in flows.
    const passwordChangeContext = ref(null); // 'grades' | 'chat' | 'appointment' | null
    const newPasswordForm = ref({ new_password: '', confirm_password: '' });
    const passwordChangeError = ref('');
    const passwordChangeLoading = ref(false);
    const showNewPassword = ref(false);
    const showConfirmPassword = ref(false);

    const resetPasswordChangeForm = () => {
        passwordChangeContext.value = null;
        newPasswordForm.value = { new_password: '', confirm_password: '' };
        passwordChangeError.value = '';
        showNewPassword.value = false;
        showConfirmPassword.value = false;
    };

    const cancelPasswordChange = () => {
        if (passwordChangeContext.value === 'grades') {
            gradesMustChangePassword.value = false;
        } else if (passwordChangeContext.value === 'chat') {
            chatMustChangePassword.value = false;
        } else if (passwordChangeContext.value === 'appointment') {
            apptMustChangePassword.value = false;
        }
        resetPasswordChangeForm();
    };

    const submitPasswordChange = async () => {
        passwordChangeError.value = '';

        if (newPasswordForm.value.new_password.length < 8) {
            passwordChangeError.value = 'The new password must be at least 8 characters.';
            return;
        }
        if (newPasswordForm.value.new_password !== newPasswordForm.value.confirm_password) {
            passwordChangeError.value = "The new passwords don't match.";
            return;
        }

        const context = passwordChangeContext.value;
        const studentNumber = context === 'chat'
            ? chatLogin.value.student_number
            : context === 'appointment'
                ? apptForm.value.student_number
                : gradesForm.value.student_number;
        const currentPassword = context === 'chat'
            ? chatLogin.value.password
            : context === 'appointment'
                ? apptForm.value.password
                : gradesForm.value.password;

        passwordChangeLoading.value = true;
        try {
            await axios.post('/portal/grades/change-password', {
                student_number: studentNumber,
                current_password: currentPassword,
                new_password: newPasswordForm.value.new_password,
                new_password_confirmation: newPasswordForm.value.confirm_password,
            });

            if (context === 'grades') {
                gradesForm.value.password = newPasswordForm.value.new_password;
                gradesMustChangePassword.value = false;
                resetPasswordChangeForm();
                await submitGradesLogin();
            } else if (context === 'chat') {
                chatLogin.value.password = newPasswordForm.value.new_password;
                chatMustChangePassword.value = false;
                resetPasswordChangeForm();
                await signInChat();
            } else if (context === 'appointment') {
                apptForm.value.password = newPasswordForm.value.new_password;
                apptMustChangePassword.value = false;
                resetPasswordChangeForm();
                await submitAppointmentLogin();
            }
        } catch (err) {
            passwordChangeError.value = err.response?.data?.message ?? 'Something went wrong, please try again.';
        } finally {
            passwordChangeLoading.value = false;
        }
    };
    // ---- End force change password state ----

    // ---- Announcements table state ----
    const expandedAnnouncementId = ref(null);
    // ---- End announcements table state ----

    // ---- FAQ state ----
    const openFaqIndex = ref(null);

    const faqs = [
        {
            q: 'How do I view my grades?',
            a: 'Click the "View my grades" button, then enter your student number and password. Both need to be correct before your grades show up.',
        },
        {
            q: 'What if my grade is wrong?',
            a: 'After viewing your grades, there\'s a "Something\'s wrong, recheck" button — click it, then enter the specific reason (e.g. which item, what the score should be).',
        },
        {
            q: 'How do I set an appointment with sir?',
            a: 'Click the "Set an appointment" card, sign in with your student number and password, then pick one of sir\'s available time slots and add your reason for the appointment.',
        },
        {
            q: 'How does the chat / Ask Sir Francisco feature work?',
            a: 'Click the chat bubble at the bottom right. Sign in with your student number and password, then you can start asking questions — the AI assistant or sir himself will respond.',
        },
        {
            q: 'Why can\'t I see my Top 10 ranking?',
            a: 'You\'ll only appear in the Top 10 if there are recorded grades for your section. If it\'s empty, that means no grades have been entered yet.',
        },
    ];
    // ---- End FAQ state ----

    let autoTimer;
    let clockTimer;

    onMounted(() => {
        autoTimer = setInterval(() => {
            if (paused.value || filteredStudents.value.length === 0) return;
            slideDirection.value = 'slide-next';
            currentIndex.value = (currentIndex.value + 1) % filteredStudents.value.length;
        }, 2200);

        clockTimer = setInterval(() => {
            liveClock.value = new Date().toLocaleString('en-PH', {
                weekday: 'short', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
            });
        }, 1000);
    });
    onUnmounted(() => {
        clearInterval(autoTimer);
        clearInterval(clockTimer);
        clearInterval(chatPollTimer);
    });

    const liveClock = ref(new Date().toLocaleString('en-PH', {
        weekday: 'short', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    }));

    const initials = (name) =>
        name.split(',')[0].trim().charAt(0) + (name.split(' ').pop()?.charAt(0) ?? '');

    // ---- Sync / last updated state ----
    // No auto-populated "last updated" from the backend — this only reflects
    // the moment the user actually pressed the sync/refresh icon.
    const isSyncing = ref(false);
    const lastSyncedAt = ref(null);

    const formattedUpdate = computed(() => {
        if (!lastSyncedAt.value) return 'Not synced yet';
        return lastSyncedAt.value.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
    });

    const syncNow = () => {
        if (isSyncing.value) return;
        isSyncing.value = true;
        // Re-fetches this page's props (sections, announcements, topics,
        // top10, etc.) from the server in the background — no full
        // browser reload — then updates the "Last updated" timestamp.
        router.reload({
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                lastSyncedAt.value = new Date();
                isSyncing.value = false;
            },
        });
    };
    // ---- End sync / last updated state ----

    const formatEventDate = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
    };

    const todayFormatted = computed(() =>
        new Date().toLocaleString('en-PH', {
            month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit',
        })
    );

    const formatPostedDate = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleString('en-PH', { month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    };

    return {
        // dark mode
        isDarkMode, toggleDarkMode,

        // sections / snapshot
        activeSectionId, activeSectionLabel,
        filteredAnnouncements, filteredTopics, filteredStudents,
        latestAnnouncement, formattedUpdate, todayFormatted,
        isSyncing, syncNow,

        // class ledger carousel
        showFullList, paused, currentIndex, slideDirection, currentStudent, goTo,

        // chat widget
        chatOpen, chatStudent, chatLogin, chatLoginError, chatLoginLoading,
        showChatLoginPassword, chatMustChangePassword, chatMessages, chatInput,
        chatSending, chatScrollEl, signInChat, sendChatMessage,

        // grades modal
        gradesModalOpen, gradesForm, gradesError, gradesLoading,
        showGradesLoginPassword, gradesResult, gradesPeriod, gradesMustChangePassword,
        periods, switchGradesPeriod, openGradesModal, closeGradesModal, submitGradesLogin,

        // grade correction / recheck
        showRecheckForm, recheckNotes, correctionLoading, correctionError, correctionSuccessMessage,
        editedItems, editingItemKey, editDraftScore, startEditItem, confirmEditItem, cancelEditItem, removeEditedItem,
        correctionAttachment, correctionAttachmentError, onAttachmentChange, hasExistingAttachment,
        correctionUiState, cancelEditingRecheckForm, startEditExistingCorrection, cancelCorrection, submitCorrection,

        // set an appointment
        appointmentModalOpen, apptForm, apptLoginError, apptLoginLoading, showApptLoginPassword,
        apptMustChangePassword, apptStudent, apptSlots, apptSlotsLoading, selectedSlotId, apptReason,
        apptExisting, apptShowNewForm, apptError, apptActionLoading,
        openAppointmentModal, closeAppointmentModal, submitAppointmentLogin, submitAppointment, cancelAppointment,

        // standalone change password
        cpModalOpen, cpForm, cpError, cpSuccess, cpLoading,
        showCpCurrentPassword, showCpNewPassword, showCpConfirmPassword,
        openChangePasswordModal, closeChangePasswordModal, submitChangePassword,

        // forced password change
        passwordChangeContext, newPasswordForm, passwordChangeError, passwordChangeLoading,
        showNewPassword, showConfirmPassword, cancelPasswordChange, submitPasswordChange,

        // announcements
        expandedAnnouncementId,

        // FAQ
        openFaqIndex, faqs,

        // misc
        liveClock, initials, formatEventDate, formatPostedDate,
    };
}