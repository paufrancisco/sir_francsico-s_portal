<!-- resources/js/Pages/Admin/Dashboard.vue -->
<template>
    <AdminLayout>
        <main class="max-w-5xl mx-auto px-6 lg:px-10 py-8 space-y-6">

            <div>
                <div class="text-lg font-semibold text-slate-800">Overview</div>
                <div class="text-xs text-slate-400 mt-0.5">Buod ng sections mo ngayon</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <div class="text-xs text-slate-400">Total sections</div>
                    <div class="text-2xl font-semibold text-[#003399] mt-1">{{ sections.length }}</div>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <div class="text-xs text-slate-400">Total students</div>
                    <div class="text-2xl font-semibold text-[#003399] mt-1">{{ totalStudents }}</div>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <div class="text-xs text-slate-400">Grades computed</div>
                    <div class="text-2xl font-semibold text-[#003399] mt-1">{{ computedCount }} / {{ sections.length }}</div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-700 mb-3">Sections</div>
                <p v-if="sections.length === 0" class="text-xs text-slate-400">Wala pang section.</p>
                <div v-for="s in sections" :key="s.id" class="flex items-center justify-between py-3 border-b border-slate-50 last:border-0">
                    <div>
                        <div class="text-sm font-medium text-slate-800">{{ s.name }}</div>
                        <div class="text-xs text-slate-400">{{ s.schedule || 'Walang schedule' }} · {{ s.students_count }} students</div>
                    </div>
                    <span
                        class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                        :class="s.grades_computed ? 'bg-[#EAF3DE] text-[#3B6D11]' : 'bg-slate-100 text-slate-500'"
                    >
                        {{ s.grades_computed ? 'Top 10 computed' : 'Not yet computed' }}
                    </span>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-700 mb-3">Latest announcement</div>
                <p v-if="!latestAnnouncement" class="text-xs text-slate-400">Wala pang announcement.</p>
                <div v-else>
                    <div class="text-sm text-slate-700">{{ latestAnnouncement.title }}</div>
                    <div class="text-xs text-slate-400 mt-1">
                        {{ latestAnnouncement.section_name }} · {{ formatDate(latestAnnouncement.created_at) }}
                    </div>
                </div>
            </div>
        </main>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    latestAnnouncement: { type: Object, default: null },
});

const totalStudents = computed(() =>
    props.sections.reduce((sum, s) => sum + (s.students_count ?? 0), 0)
);

const computedCount = computed(() =>
    props.sections.filter((s) => s.grades_computed).length
);

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
};
</script>