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
                    <div class="relative" ref="sectionMenuRef">
                        <button
                            type="button"
                            @click="sectionMenuOpen = !sectionMenuOpen"
                            title="Switch section"
                            class="flex items-center gap-1.5 text-lg font-semibold text-slate-800 hover:text-[#003399] transition"
                        >
                            {{ section.name }}
                            <svg
                                width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="transition-transform"
                                :class="sectionMenuOpen ? 'rotate-180' : ''"
                            >
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div
                            v-if="sectionMenuOpen"
                            class="absolute left-0 mt-2 min-w-[12rem] max-h-72 overflow-y-auto bg-white border border-slate-200 rounded-lg shadow-lg py-1 z-30"
                        >
                            <Link
                                v-for="s in ($page.props.adminSections ?? [])"
                                :key="s.id"
                                :href="sectionHref(s.id)"
                                class="block px-4 py-2 text-sm hover:bg-slate-50 truncate"
                                :class="s.id === section.id ? 'text-[#003399] font-medium' : 'text-slate-600'"
                            >
                                {{ s.name }}
                            </Link>
                        </div>
                    </div>
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

        </main>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePeriodSwitcher } from '@/composables/usePeriodSwitcher';
import MasterlistTab from './components/MasterlistTab.vue';
import GradesTab from './components/GradesTab.vue';

const props = defineProps({
    section: Object,
    students: Array,
    revealed: Boolean,
    gradeItems: { type: Array, default: () => [] },
    gradesBreakdown: { type: Array, default: () => [] },
    currentPeriod: { type: String, default: 'prelim' },
});

const activeTab = ref(
    new URLSearchParams(window.location.search).get('tab') === 'grades' ? 'grades' : 'masterlist'
);

// Remember the last opened section for the sidebar "Grades" shortcut
try {
    const m = window.location.pathname.match(/\/paulo\/sections\/(\d+)/);
    if (m) localStorage.setItem('lastSectionId', m[1]);
} catch (e) { /* storage unavailable */ }

// ---- Section switcher (dropdown sa section name) ----
const sectionMenuOpen = ref(false);
const sectionMenuRef = ref(null);

// Stay on the tab that is currently open
const sectionHref = (id) =>
    '/paulo/sections/' + id + (activeTab.value === 'grades' ? '?tab=grades' : '');

const closeSectionMenu = (e) => {
    if (sectionMenuRef.value && !sectionMenuRef.value.contains(e.target)) {
        sectionMenuOpen.value = false;
    }
};
onMounted(() => document.addEventListener('click', closeSectionMenu));
onUnmounted(() => document.removeEventListener('click', closeSectionMenu));
// ---- End section switcher ----

// Period tabs (Prelim / Midterm / Pre-Final / Finals) are shared by
// the Grades tab, so the switcher lives here and gets passed down as a prop.
const currentPeriodRef = computed(() => props.currentPeriod);
const { periods, periodLoading, switchPeriod } = usePeriodSwitcher(props.section.id, currentPeriodRef);
</script>