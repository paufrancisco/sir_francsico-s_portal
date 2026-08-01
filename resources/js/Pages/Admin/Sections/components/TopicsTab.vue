<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit">
                <button
                    @click="topicView = 'active'"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="topicView === 'active' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Active
                </button>
                <button
                    @click="topicView = 'archived'"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="topicView === 'archived' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Archived
                </button>
            </div>
            <button
                v-if="topicView === 'active'"
                @click="openAddTopic"
                class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg"
            >
                Add topic
            </button>
        </div>

        <!-- Period tabs -->
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit">
                <button
                    v-for="p in periods"
                    :key="p.value"
                    @click="switchPeriod(p.value)"
                    :disabled="periodLoading"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition disabled:cursor-not-allowed"
                    :class="currentPeriod === p.value ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    {{ p.label }}
                </button>
            </div>
            <svg
                v-if="periodLoading"
                width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin text-[#003399]"
            >
                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
            </svg>
        </div>

        <p class="text-[11px] text-slate-400">
            Showing topics for <span class="font-semibold text-[#003399]">{{ periods.find(p => p.value === currentPeriod)?.label }}</span> only.
        </p>

        <!-- Search -->
        <div class="relative">
            <svg
                width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"
            >
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.3-4.3"/>
            </svg>
            <input
                v-model="topicSearch"
                type="text"
                placeholder="Search by topic title..."
                class="w-full text-sm border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:border-[#003399]"
            />
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <p v-if="currentTopicList.length === 0" class="text-xs text-slate-400 px-4 py-6">
                {{ topicView === 'active' ? 'No topics yet.' : 'No archived topics.' }}
            </p>
            <p v-else-if="filteredTopicList.length === 0" class="text-xs text-slate-400 px-4 py-6">
                No matching topics.
            </p>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-xs text-slate-500">
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Date covered</th>
                        <th class="px-4 py-2">Quiz</th>
                        <th class="px-4 py-2">TP</th>
                        <th class="px-4 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in filteredTopicList" :key="t.id" class="border-t border-slate-100">
                        <td class="px-4 py-2 text-slate-700">{{ t.title }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ formatDate(t.date_covered) }}</td>
                        <td class="px-4 py-2 text-slate-500">
                            <span v-if="t.has_quiz">{{ t.quiz_items }} items</span>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="px-4 py-2 text-slate-500">
                            <span v-if="t.has_tp">Yes</span>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    v-if="topicView === 'active'"
                                    @click="openEditTopic(t)"
                                    title="Edit"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button
                                    v-if="topicView === 'active'"
                                    @click="archiveTopic(t)"
                                    title="Archive"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="4" rx="1"/>
                                        <path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/>
                                        <path d="M10 12h4"/>
                                    </svg>
                                </button>
                                <button
                                    v-if="topicView === 'archived'"
                                    @click="restoreTopic(t)"
                                    title="Restore"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                                        <path d="M3 3v5h5"/>
                                    </svg>
                                </button>
                                <button
                                    v-if="topicView === 'archived'"
                                    @click="deleteTopicPermanently(t)"
                                    title="Delete"
                                    class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Topic modal -->
        <div v-if="topicModalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeTopicModal">
            <div class="bg-white rounded-xl p-5 w-full max-w-xs shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold text-slate-700">{{ editingTopicId ? 'Edit topic' : 'Add topic' }}</div>
                    <button @click="closeTopicModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form @submit.prevent="submitTopicForm" class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500">Title</label>
                        <input v-model="topicForm.title" type="text" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mt-1" />
                        <p v-if="topicForm.errors.title" class="text-xs text-red-500 mt-1">{{ topicForm.errors.title }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Date covered</label>
                        <input v-model="topicForm.date_covered" type="date" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mt-1" />
                        <p v-if="topicForm.errors.date_covered" class="text-xs text-red-500 mt-1">{{ topicForm.errors.date_covered }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="topicForm.has_quiz" type="checkbox" id="has_quiz" class="rounded border-slate-300" />
                        <label for="has_quiz" class="text-xs text-slate-600">Has quiz</label>
                    </div>
                    <div v-if="topicForm.has_quiz">
                        <label class="text-xs text-slate-500">Number of quiz items</label>
                        <input v-model.number="topicForm.quiz_items" type="number" min="1" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mt-1" />
                        <p v-if="topicForm.errors.quiz_items" class="text-xs text-red-500 mt-1">{{ topicForm.errors.quiz_items }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="topicForm.has_tp" type="checkbox" id="has_tp" class="rounded border-slate-300" />
                        <label for="has_tp" class="text-xs text-slate-600">Has TP</label>
                    </div>
                    <button type="submit" :disabled="topicForm.processing" class="w-full bg-[#003399] text-white text-sm font-medium py-2 rounded-lg disabled:opacity-50">
                        {{ topicForm.processing ? 'Saving...' : 'Save' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTopicsTab } from '@/composables/useTopicsTab';

const props = defineProps({
    section: Object,
    topics: { type: Array, default: () => [] },
    archivedTopics: { type: Array, default: () => [] },
    currentPeriod: String,
    periods: { type: Array, required: true },
    periodLoading: { type: Boolean, default: false },
    switchPeriod: { type: Function, required: true },
});

const sectionId = props.section.id;
const topicsRef = computed(() => props.topics);
const archivedTopicsRef = computed(() => props.archivedTopics);
const currentPeriodRef = computed(() => props.currentPeriod);

const {
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
} = useTopicsTab(sectionId, topicsRef, archivedTopicsRef, currentPeriodRef);
</script>