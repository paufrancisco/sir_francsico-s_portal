<template>
    <AdminLayout>
        <main class="w-full px-6 lg:px-10 py-8 space-y-4">

            <div v-if="$page.props.flash?.success" class="bg-[#EAF3DE] text-[#3B6D11] text-sm rounded-lg px-4 py-2">
                {{ $page.props.flash.success }}
            </div>

            <div>
                <Link
                    href="/paulo/sections"
                    class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-[#003399] transition mb-2"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    Back to sections
                </Link>
                <div class="flex items-center gap-2">
                    <div class="text-lg font-semibold text-slate-800">{{ section.name }}</div>
                    <span v-if="section.subject" class="text-sm font-medium text-slate-400">— {{ section.subject }}</span>
                </div>
                <div class="text-xs text-slate-400">{{ students.length }} students</div>
            </div>

            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit">
                <button
                    @click="activeTab = 'masterlist'"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="activeTab === 'masterlist' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Masterlist
                </button>
                <button
                    @click="activeTab = 'grades'"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="activeTab === 'grades' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Grades
                </button>
                <button
                    @click="activeTab = 'topics'"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="activeTab === 'topics' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Topics
                </button>
            </div>

            <MasterlistTab
                v-if="activeTab === 'masterlist'"
                :section="section"
                :students="students"
                :revealed="revealed"
                :current-period="currentPeriod"
            />

            <GradesTab
                v-else-if="activeTab === 'grades'"
                :section="section"
                :grade-items="gradeItems"
                :grades-breakdown="gradesBreakdown"
                :current-period="currentPeriod"
                :periods="periods"
                :period-loading="periodLoading"
                :switch-period="switchPeriod"
            />

            <TopicsTab
                v-else-if="activeTab === 'topics'"
                :section="section"
                :topics="topics"
                :archived-topics="archivedTopics"
                :current-period="currentPeriod"
                :periods="periods"
                :period-loading="periodLoading"
                :switch-period="switchPeriod"
            />

        </main>
    </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePeriodSwitcher } from '@/composables/usePeriodSwitcher';
import MasterlistTab from './components/MasterlistTab.vue';
import GradesTab from './components/GradesTab.vue';
import TopicsTab from './components/TopicsTab.vue';

const props = defineProps({
    section: Object,
    students: Array,
    revealed: Boolean,
    gradeItems: { type: Array, default: () => [] },
    gradesBreakdown: { type: Array, default: () => [] },
    currentPeriod: { type: String, default: 'prelim' },
    topics: { type: Array, default: () => [] },
    archivedTopics: { type: Array, default: () => [] },
});

const activeTab = ref('masterlist');

// Period tabs (Prelim / Midterm / Pre-Final / Finals) are shared by
// the Grades tab and the Topics tab, so the switcher lives here and
// gets passed down as props.
const currentPeriodRef = computed(() => props.currentPeriod);
const { periods, periodLoading, switchPeriod } = usePeriodSwitcher(props.section.id, currentPeriodRef);
</script>