<template>
    <AdminLayout>
        <main class="max-w-3xl mx-auto px-6 lg:px-10 py-8 space-y-6">

            <div v-if="$page.props.flash?.success" class="bg-[#EAF3DE] text-[#3B6D11] text-sm rounded-lg px-4 py-2">
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-700 mb-3">
                    {{ editingId ? 'Edit section' : 'Add new section' }}
                </div>
                <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input v-model="form.name" type="text" placeholder="Section name (e.g. Section 1)" class="text-sm border border-slate-200 rounded-lg px-3 py-2 sm:col-span-1" />
                    <input v-model="form.schedule" type="text" placeholder="Schedule (optional)" class="text-sm border border-slate-200 rounded-lg px-3 py-2 sm:col-span-1" />
                    <div class="flex gap-2">
                        <button type="submit" :disabled="form.processing" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                            {{ editingId ? 'Save changes' : 'Add section' }}
                        </button>
                        <button v-if="editingId" type="button" @click="cancelEdit" class="text-sm text-slate-500 px-3 py-2">Cancel</button>
                    </div>
                </form>
                <p v-if="form.errors.name" class="text-xs text-red-500 mt-2">{{ form.errors.name }}</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-700 mb-3">All sections</div>
                <p v-if="sections.length === 0" class="text-xs text-slate-400">Wala pang section. Magdagdag sa itaas.</p>
                <div v-for="s in sections" :key="s.id" class="flex items-center justify-between py-3 border-b border-slate-50 last:border-0">
                    <Link :href="`/paulo/sections/${s.id}`" class="flex-1 hover:opacity-70 transition">
                        <div class="text-sm font-medium text-slate-800">{{ s.name }}</div>
                        <div class="text-xs text-slate-400">{{ s.schedule || 'Walang schedule' }} · {{ s.students_count }} students</div>
                    </Link>
                    <div class="flex gap-3">
                        <button @click="edit(s)" class="text-xs text-[#003399] font-medium hover:underline">Edit</button>
                        <button @click="destroy(s)" class="text-xs text-red-500 font-medium hover:underline">Delete</button>
                    </div>
                </div>
            </div>
        </main>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({ sections: Array });

const editingId = ref(null);
const form = useForm({ name: '', schedule: '' });

const submit = () => {
    if (editingId.value) {
        form.put(`/paulo/sections/${editingId.value}`, { onSuccess: () => cancelEdit() });
    } else {
        form.post('/paulo/sections', { onSuccess: () => form.reset() });
    }
};

const edit = (section) => {
    editingId.value = section.id;
    form.name = section.name;
    form.schedule = section.schedule;
};

const cancelEdit = () => {
    editingId.value = null;
    form.reset();
};

const destroy = (section) => {
    if (!confirm(`Sigurado ka bang gusto mong burahin ang "${section.name}"?`)) return;
    router.delete(`/paulo/sections/${section.id}`);
};
</script>