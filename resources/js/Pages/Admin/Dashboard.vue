<!-- resources/js/Pages/Admin/Dashboard.vue -->
<template>
    <AdminLayout>
        <main class="max-w-5xl mx-auto px-6 lg:px-10 py-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-slate-800">Overview</div>
                    <div class="text-xs text-slate-400 mt-0.5">Buod ng sections mo ngayon</div>
                </div>
                <button
                    @click="openAddModal"
                    class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition"
                >
                    + Add Announcement
                </button>
            </div>

            <div v-if="$page.props.flash?.success" class="bg-[#EAF3DE] text-[#3B6D11] text-sm rounded-lg px-4 py-2">
                {{ $page.props.flash.success }}
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

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="text-sm font-semibold text-slate-700 px-5 pt-5 mb-3">Recent announcements</div>
                <p v-if="announcements.length === 0" class="text-xs text-slate-400 px-5 pb-5">Wala pang announcement.</p>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs text-slate-500">
                            <th class="px-5 py-2">Target</th>
                            <th class="px-5 py-2">Title</th>
                            <th class="px-5 py-2">Body</th>
                            <th class="px-5 py-2">Date</th>
                            <th class="px-5 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in announcements" :key="a.id" class="border-t border-slate-100 align-top">
                            <td class="px-5 py-3">
                                <span class="inline-block text-[11px] font-medium px-2 py-0.5 rounded-full bg-[#E6F1FB] text-[#003399] whitespace-nowrap">
                                    {{ a.target }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-800 font-medium">{{ a.title }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ a.body }}</td>
                            <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap">{{ formatDate(a.created_at) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        @click="openEditModal(a)"
                                        title="Edit"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                    >
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click="destroy(a)"
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
        </main>

        <!-- Add/Edit Announcement Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-semibold text-slate-800">
                        {{ editingId ? 'Edit Announcement' : 'Add Announcement' }}
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-3">
                    <div>
                        <input v-model="form.title" type="text" placeholder="Title" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <textarea v-model="form.body" placeholder="Announcement details" rows="3" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2"></textarea>
                        <p v-if="form.errors.body" class="text-xs text-red-500 mt-1">{{ form.errors.body }}</p>
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="radio" :value="true" v-model="form.is_global" />
                            Lahat ng section
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="radio" :value="false" v-model="form.is_global" />
                            Specific na section
                        </label>

                        <div v-if="!form.is_global" class="pl-6 space-y-1 max-h-32 overflow-y-auto">
                            <label v-for="s in allSections" :key="s.id" class="flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" :value="s.id" v-model="form.section_ids" />
                                {{ s.subject ? `${s.subject} - ${s.name}` : s.name }}
                            </label>
                        </div>
                        <p v-if="form.errors.section_ids" class="text-xs text-red-500">{{ form.errors.section_ids }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="closeModal" class="text-sm text-slate-500 px-4 py-2">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                            {{ editingId ? 'Save changes' : 'Post' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    announcements: { type: Array, default: () => [] },
    allSections: { type: Array, default: () => [] },
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

const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ title: '', body: '', is_global: true, section_ids: [] });

const openAddModal = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (a) => {
    editingId.value = a.id;
    form.title = a.title;
    form.body = a.body;
    form.is_global = a.is_global;
    form.section_ids = a.section_ids;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingId.value) {
        form.put(`/paulo/announcements/${editingId.value}`, { onSuccess: () => closeModal() });
    } else {
        form.post('/paulo/announcements', { onSuccess: () => closeModal() });
    }
};

const destroy = (a) => {
    if (!confirm(`Sigurado ka bang tatanggalin ang announcement na "${a.title}"?`)) return;
    router.delete(`/paulo/announcements/${a.id}`);
};
</script>