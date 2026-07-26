import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';

/**
 * All reactive state + logic for the Student Dashboard portal
 * (dark mode, class ledger carousel, chat widget, grades modal,
 * grade correction/recheck flow, forced password change, FAQ).
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
    const calendarEvents = computed(() => [
        ...(props.globalCalendarEvents ?? []),
        ...(props.calendarEventsBySection[activeSectionId.value] ?? []),
    ]);
    const filteredStudents = computed(() => props.top10BySection[activeSectionId.value] ?? []);

    const currentStudent = computed(() => filteredStudents.value[currentIndex.value] ?? filteredStudents.value[0]);

    // ---- Snapshot stats (derived, no backend change needed) ----
    const nextEvent = computed(() => {
        const now = new Date();
        const upcoming = calendarEvents.value
            .filter((e) => new Date(e.event_date) >= now)
            .sort((a, b) => new Date(a.event_date) - new Date(b.event_date));
        return upcoming[0] ?? null;
    });

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
            chatLoginError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
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
            chatMessages.value.push({ id: Date.now(), sender: 'ai', body: 'May error, subukan ulit mamaya.' });
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
            gradesError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
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
            correctionError.value = 'Hindi valid na score.';
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
            correctionAttachmentError.value = 'Larawan lang ang tinatanggap.';
            e.target.value = '';
            correctionAttachment.value = null;
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            correctionAttachmentError.value = 'Dapat hindi hihigit sa 10MB ang larawan.';
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
            correctionSuccessMessage.value = 'Nakansela na ang recheck request mo.';
            await refetchGradesResult();
        } catch (err) {
            correctionError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
        } finally {
            correctionLoading.value = false;
        }
    };

    const submitCorrection = async (type) => {
        correctionError.value = '';

        if (type === 'recheck') {
            if (Object.keys(editedItems.value).length === 0) {
                correctionError.value = 'Mag-edit muna ng score na mali bago mag-submit.';
                return;
            }
            if (!correctionAttachment.value && !hasExistingAttachment.value) {
                correctionError.value = 'Maglagay ng larawan bilang patunay (attachment).';
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
            correctionError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
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
            gradesError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
        } finally {
            gradesLoading.value = false;
        }
    };
    // ---- End grades modal state ----

    // ---- Force change password (first login) state ----
    // Shared by both the grades sign-in and chat sign-in flows.
    const passwordChangeContext = ref(null); // 'grades' | 'chat' | null
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
        }
        resetPasswordChangeForm();
    };

    const submitPasswordChange = async () => {
        passwordChangeError.value = '';

        if (newPasswordForm.value.new_password.length < 8) {
            passwordChangeError.value = 'Dapat at least 8 characters ang bagong password.';
            return;
        }
        if (newPasswordForm.value.new_password !== newPasswordForm.value.confirm_password) {
            passwordChangeError.value = 'Hindi magkatugma ang bagong password.';
            return;
        }

        const context = passwordChangeContext.value;
        const studentNumber = context === 'chat' ? chatLogin.value.student_number : gradesForm.value.student_number;
        const currentPassword = context === 'chat' ? chatLogin.value.password : gradesForm.value.password;

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
            }
        } catch (err) {
            passwordChangeError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
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
            q: 'Paano ako makakita ng grades ko?',
            a: 'I-click yung "View my grades" button, tapos ilagay yung student number at password mo. Kailangan tama yung dalawa bago lumabas ang grades mo.',
        },
        {
            q: 'Paano kung mali yung grade ko?',
            a: 'Pagkatapos mong tingnan yung grades mo, may button na "May mali, i-recheck" — pindutin mo yun tapos ilagay yung specific na dahilan (hal. anong item, dapat ilan yung score).',
        },
        {
            q: 'Paano ako mag-inform na absent si sir?',
            a: 'I-click yung "Inform sir absent" card sa dashboard. Awtomatikong naka-fill na ang section mo, ikaw na lang mag-submit ng dahilan o detalye.',
        },
        {
            q: 'Paano gumagana ang chat / Ask Sir Francisco?',
            a: 'I-click yung chat bubble sa ibaba kanan. Mag-sign in ka gamit ang student number at password, tapos pwede ka nang magtanong — sasagutin ka ng AI assistant o ni sir mismo.',
        },
        {
            q: 'Bakit hindi ko makita yung Top 10 ranking ko?',
            a: 'Lalabas lang sa Top 10 kung may naka-record nang grades sa section mo. Kung wala pang laman, ibig sabihin wala pa nailalagay na grades para dyan.',
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

    const formattedUpdate = computed(() => {
        if (!props.lastCalendarUpdate) return 'Wala pang update';
        const d = new Date(props.lastCalendarUpdate);
        return d.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
    });

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
        nextEvent, latestAnnouncement, formattedUpdate, todayFormatted,

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