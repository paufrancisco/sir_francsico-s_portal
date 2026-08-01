import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

/**
 * @param {number|string} sectionId
 * @param {import('vue').Ref<Array>} topics
 * @param {import('vue').Ref<Array>} archivedTopics
 * @param {import('vue').Ref<string>} currentPeriod
 */
export function useTopicsTab(sectionId, topics, archivedTopics, currentPeriod) {
    const topicView = ref('active');

    const currentTopicList = computed(() =>
        topicView.value === 'active' ? topics.value : archivedTopics.value
    );

    const topicSearch = ref('');

    const filteredTopicList = computed(() => {
        const q = topicSearch.value.trim().toLowerCase();
        if (!q) return currentTopicList.value;
        return currentTopicList.value.filter((t) => t.title.toLowerCase().includes(q));
    });

    // ---- Add/Edit topic modal ----
    const topicModalOpen = ref(false);
    const editingTopicId = ref(null);
    const topicForm = useForm({
        title: '',
        date_covered: '',
        has_quiz: false,
        quiz_items: '',
        has_tp: false,
    });

    const openAddTopic = () => {
        editingTopicId.value = null;
        topicForm.reset();
        topicForm.clearErrors();
        topicModalOpen.value = true;
    };

    const openEditTopic = (topic) => {
        editingTopicId.value = topic.id;
        topicForm.title = topic.title;
        topicForm.date_covered = topic.date_covered ?? '';
        topicForm.has_quiz = !!topic.has_quiz;
        topicForm.quiz_items = topic.quiz_items ?? '';
        topicForm.has_tp = !!topic.has_tp;
        topicForm.clearErrors();
        topicModalOpen.value = true;
    };

    const closeTopicModal = () => {
        topicModalOpen.value = false;
    };

    const submitTopicForm = () => {
        if (editingTopicId.value) {
            topicForm.transform((data) => ({ ...data, _method: 'patch' }))
                .post(`/paulo/topics/${editingTopicId.value}/details`, {
                    preserveScroll: true,
                    onSuccess: () => { topicModalOpen.value = false; },
                });
        } else {
            topicForm.transform((data) => ({ ...data, period: currentPeriod.value }))
                .post(`/paulo/sections/${sectionId}/topics`, {
                    preserveScroll: true,
                    onSuccess: () => { topicModalOpen.value = false; },
                });
        }
    };

    const archiveTopic = (topic) => {
        router.patch(`/paulo/topics/${topic.id}/archive`, {}, { preserveScroll: true });
    };

    const restoreTopic = (topic) => {
        router.patch(`/paulo/topics/${topic.id}/restore`, {}, { preserveScroll: true });
    };

    const deleteTopicPermanently = (topic) => {
        if (!confirm(`Are you sure you want to permanently delete "${topic.title}"?`)) return;
        router.delete(`/paulo/topics/${topic.id}`, { preserveScroll: true });
    };

    const formatDate = (value) => {
        if (!value) return '—';
        return new Date(value).toLocaleDateString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    return {
        topicView,
        currentTopicList,
        topicSearch,
        filteredTopicList,
        topicModalOpen,
        editingTopicId,
        topicForm,
        openAddTopic,
        openEditTopic,
        closeTopicModal,
        submitTopicForm,
        archiveTopic,
        restoreTopic,
        deleteTopicPermanently,
        formatDate,
    };
}