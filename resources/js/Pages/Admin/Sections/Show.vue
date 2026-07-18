<template>
    <AdminLayout>
        <main class="max-w-5xl mx-auto px-6 lg:px-10 py-8 space-y-4">

            <div v-if="$page.props.flash?.success" class="bg-[#EAF3DE] text-[#3B6D11] text-sm rounded-lg px-4 py-2">
                {{ $page.props.flash.success }}
            </div>

            <div>
                <div class="text-lg font-semibold text-slate-800">{{ section.name }}</div>
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

            <!-- MASTERLIST TAB -->
            <div v-if="activeTab === 'masterlist'" class="space-y-3">
                <div class="flex justify-end gap-2">
                    <Link :href="`/paulo/sections/${section.id}/students/import`" class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg">
                        Import students
                    </Link>
                    <button v-if="!revealed" @click="showModal = true" class="border border-slate-200 text-xs font-medium px-4 py-2 rounded-lg text-slate-600">
                        Show passwords
                    </button>
                    <Link v-else :href="`/paulo/sections/${section.id}`" class="text-xs text-slate-400 hover:underline">Hide passwords</Link>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <p v-if="students.length === 0" class="text-xs text-slate-400 px-4 py-6">
                        Wala pang estudyante dito. I-click yung "Import students" para magdagdag.
                    </p>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs text-slate-500">
                                <th class="px-4 py-2">Student number</th>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Password</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in students" :key="s.id" class="border-t border-slate-100">
                                <td class="px-4 py-2 text-slate-700">{{ s.student_number }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ s.full_name }}</td>
                                <td class="px-4 py-2 font-mono text-[#003399]">{{ revealed ? s.password : '••••••' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- GRADES TAB -->
            <div v-else class="space-y-3">
                <div class="flex justify-end">
                    <label class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg cursor-pointer">
                        {{ uploading ? 'Uploading...' : 'Import grades (Excel)' }}
                        <input type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="uploadGrades" :disabled="uploading" />
                    </label>
                </div>
                <p class="text-[11px] text-slate-400">
                    Header format: <code>Long Quiz: Quiz 1 (50)</code>, <code>TP: Project 1 (100)</code>, <code>Exam: Midterm (100)</code> — Column A = Student Number.
                </p>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-x-auto">
                    <p v-if="gradesBreakdown.length === 0" class="text-xs text-slate-400 px-4 py-6">
                        Wala pang na-import na grades.
                    </p>
                    <table v-else class="w-full text-sm whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs text-slate-500">
                                <th class="px-3 py-2">Rank</th>
                                <th class="px-3 py-2">Name</th>
                                <th v-for="item in gradeItems" :key="item.category + item.title" class="px-3 py-2">
                                    {{ item.title }}
                                </th>
                                <th class="px-3 py-2">Total %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in gradesBreakdown" :key="row.id" class="border-t border-slate-100">
                                <td class="px-3 py-2 text-slate-500">{{ row.rank }}</td>
                                <td class="px-3 py-2 text-slate-700 font-medium">{{ row.name }}</td>
                                <td v-for="item in gradeItems" :key="item.category + item.title" class="px-3 py-2 text-slate-600">
                                    <template v-if="row.scores[item.category + '|' + item.title]">
                                        {{ row.scores[item.category + '|' + item.title].score }}/{{ row.scores[item.category + '|' + item.title].max_score }}
                                    </template>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-3 py-2 font-semibold text-[#003399]">{{ row.total_percentage }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <div v-if="showModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-5 w-80 shadow-xl">
                <div class="text-sm font-semibold text-slate-700 mb-3">Confirm admin password</div>
                <form @submit.prevent="submitReveal" class="space-y-3">
                    <input v-model="form.password" type="password" placeholder="Your login password" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                    <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg flex-1">Confirm</button>
                        <button type="button" @click="showModal = false" class="text-sm text-slate-500 px-3 py-2">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    section: Object,
    students: Array,
    revealed: Boolean,
    gradeItems: { type: Array, default: () => [] },
    gradesBreakdown: { type: Array, default: () => [] },
});

const activeTab = ref('masterlist');
const showModal = ref(false);
const uploading = ref(false);
const form = useForm({ password: '' });

const submitReveal = () => {
    form.post(`/paulo/sections/${props.section.id}/students/reveal`, {
        onSuccess: () => { showModal.value = false; form.reset(); },
    });
};

const uploadGrades = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    uploading.value = true;
    router.post(`/paulo/sections/${props.section.id}/grades/import`, { file }, {
        forceFormData: true,
        onFinish: () => { uploading.value = false; e.target.value = ''; },
    });
};
</script>