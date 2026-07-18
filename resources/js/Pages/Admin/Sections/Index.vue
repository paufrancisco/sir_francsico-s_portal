<template>
    <AdminLayout>
        <main class="max-w-3xl mx-auto px-6 lg:px-10 py-8 space-y-6">

            <div v-if="$page.props.flash?.success" class="bg-[#EAF3DE] text-[#3B6D11] text-sm rounded-lg px-4 py-2">
                {{ $page.props.flash.success }}
            </div>

            <div class="flex items-center justify-between">
                <div class="text-lg font-semibold text-slate-800">Sections</div>
                <button
                    @click="openAddModal"
                    class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition"
                >
                    + Add section
                </button>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-700 mb-3">All sections</div>
                <p v-if="sections.length === 0" class="text-xs text-slate-400">Wala pang section. Mag-add gamit ang button sa itaas.</p>
                <div v-for="s in sections" :key="s.id" class="flex items-center justify-between py-3 border-b border-slate-50 last:border-0">
                    <Link :href="`/paulo/sections/${s.id}`" class="flex-1 hover:opacity-70 transition">
                        <div class="text-sm font-medium text-slate-800">{{ s.name }}</div>
                        <div class="text-xs text-slate-400">{{ s.subject || 'Walang subject' }} · {{ s.schedule || 'Walang schedule' }} · {{ s.students_count }} students</div>
                    </Link>
                    <div class="flex items-center gap-3">
                        <button @click="openEditModal(s)" class="text-slate-400 hover:text-[#003399] transition" title="Edit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                            </svg>
                        </button>
                        <button @click="destroy(s)" class="text-slate-400 hover:text-red-500 transition" title="Delete">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18" />
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                <path d="M10 11v6" />
                                <path d="M14 11v6" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
            @click.self="closeModal"
        >
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-semibold text-slate-800">
                        {{ editingId ? 'Edit section' : 'Add new section' }}
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-3">
                    <div>
                        <input v-model="form.name" type="text" placeholder="Section name (e.g. Section 1)" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <input v-model="form.subject" type="text" placeholder="Subject (e.g. Databases)" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                    </div>
                    <div>
                        <input v-model="form.schedule" type="text" placeholder="Schedule (optional)" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="closeModal" class="text-sm text-slate-500 px-4 py-2">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                            {{ editingId ? 'Save changes' : 'Add section' }}
                        </button>
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

defineProps({ sections: Array });

const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ name: '', subject: '', schedule: '' });

const openAddModal = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (section) => {
    editingId.value = section.id;
    form.name = section.name;
    form.subject = section.subject;
    form.schedule = section.schedule;
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
        form.put(`/paulo/sections/${editingId.value}`, { onSuccess: () => closeModal() });
    } else {
        form.post('/paulo/sections', { onSuccess: () => closeModal() });
    }
};

const destroy = (section) => {
    if (!confirm(`Sigurado ka bang gusto mong burahin ang "${section.name}"?`)) return;
    router.delete(`/paulo/sections/${section.id}`);
};
</script>