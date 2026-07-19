<template>
    <AdminLayout>
        <main class="max-w-5xl mx-auto px-6 lg:px-10 py-8 space-y-4">
            <div class="text-lg font-semibold text-slate-800">FAQ — AI Assistant knowledge base</div>
            <p class="text-xs text-slate-400">
                Ito yung gagamitin ng AI assistant bilang basehan ng sagot niya sa mga estudyante. Kung wala rito ang tanong, ipapaalam sa'yo via email.
            </p>

            <div v-if="$page.props.flash?.success" class="bg-[#EAF3DE] text-[#3B6D11] text-sm rounded-lg px-4 py-2">
                {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submitNew" class="bg-white border border-slate-200 rounded-xl p-4 space-y-2">
                <select v-model="newForm.section_id" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2">
                    <option :value="null">Para sa lahat ng section</option>
                    <option v-for="s in sections" :key="s.id" :value="s.id">
                        {{ s.subject ? `${s.subject} - ${s.name}` : s.name }}
                    </option>
                </select>
                <input v-model="newForm.question" placeholder="Tanong (hal. Kailan ang deadline ng project?)" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                <textarea v-model="newForm.answer" placeholder="Sagot" rows="2" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2"></textarea>
                <button type="submit" class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg">Idagdag</button>
            </form>

            <!-- Section tabs -->
            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit flex-wrap">
                <button
                    @click="activeTab = 'all'; currentPage = 1"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="activeTab === 'all' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Lahat ng Section
                </button>
                <button
                    v-for="s in sections"
                    :key="s.id"
                    @click="activeTab = s.id; currentPage = 1"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="activeTab === s.id ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    {{ s.subject ? `${s.subject} - ${s.name}` : s.name }}
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <p v-if="filteredFaqs.length === 0" class="text-xs text-slate-400 px-4 py-6">
                    Wala pang FAQ dito.
                </p>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs text-slate-500">
                            <th class="px-4 py-2 w-2/5">Tanong</th>
                            <th class="px-4 py-2">Sagot</th>
                            <th class="px-4 py-2 text-center w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="f in paginatedFaqs" :key="f.id" class="border-t border-slate-100 align-top">
                            <template v-if="editingId === f.id">
                                <td colspan="3" class="px-4 py-3">
                                    <select v-model="editForm.section_id" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mb-2">
                                        <option :value="null">Para sa lahat ng section</option>
                                        <option v-for="s in sections" :key="s.id" :value="s.id">
                                            {{ s.subject ? `${s.subject} - ${s.name}` : s.name }}
                                        </option>
                                    </select>
                                    <input v-model="editForm.question" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mb-2" />
                                    <textarea v-model="editForm.answer" rows="2" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mb-2"></textarea>
                                    <div class="flex gap-2">
                                        <button @click="saveEdit(f.id)" class="bg-[#003399] text-white text-xs font-medium px-3 py-1.5 rounded-lg">Save</button>
                                        <button @click="editingId = null" class="text-xs text-slate-500 px-3 py-1.5">Cancel</button>
                                    </div>
                                </td>
                            </template>
                            <template v-else>
                                <td class="px-4 py-3 text-slate-800 font-medium">{{ f.question }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ f.answer }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            @click="startEdit(f)"
                                            title="Edit"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                        >
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="destroy(f.id)"
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
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-between text-xs text-slate-500">
                <span>Page {{ currentPage }} ng {{ totalPages }} ({{ filteredFaqs.length }} FAQ)</span>
                <div class="flex gap-1">
                    <button
                        @click="currentPage--"
                        :disabled="currentPage === 1"
                        class="px-3 py-1.5 border border-slate-200 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition"
                    >
                        Previous
                    </button>
                    <button
                        @click="currentPage++"
                        :disabled="currentPage === totalPages"
                        class="px-3 py-1.5 border border-slate-200 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition"
                    >
                        Next
                    </button>
                </div>
            </div>
        </main>
    </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ faqs: Array, sections: Array });

const newForm = useForm({ question: '', answer: '', section_id: null });
const submitNew = () => {
    newForm.post('/paulo/faqs', { onSuccess: () => newForm.reset() });
};

const editingId = ref(null);
const editForm = ref({ question: '', answer: '', section_id: null });

const startEdit = (f) => {
    editingId.value = f.id;
    editForm.value = { question: f.question, answer: f.answer, section_id: f.section_id };
};

const saveEdit = (id) => {
    router.put(`/paulo/faqs/${id}`, editForm.value, {
        onSuccess: () => { editingId.value = null; },
    });
};

const destroy = (id) => {
    if (confirm('Sigurado ka bang i-delete ito?')) {
        router.delete(`/paulo/faqs/${id}`);
    }
};

// ---- Tabs ----
const activeTab = ref('all');

const filteredFaqs = computed(() => {
    if (activeTab.value === 'all') {
        return props.faqs.filter((f) => f.section_id === null);
    }
    return props.faqs.filter((f) => f.section_id === activeTab.value);
});

// ---- Pagination ----
const currentPage = ref(1);
const perPage = 10;

const totalPages = computed(() => Math.max(1, Math.ceil(filteredFaqs.value.length / perPage)));

const paginatedFaqs = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredFaqs.value.slice(start, start + perPage);
});
</script>