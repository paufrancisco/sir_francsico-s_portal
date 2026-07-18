<template>
    <AdminLayout>
        <main class="max-w-3xl mx-auto px-6 lg:px-10 py-8 space-y-4">
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

            <div class="space-y-2">
                <div v-for="f in faqs" :key="f.id" class="bg-white border border-slate-200 rounded-xl p-4">
                    <template v-if="editingId === f.id">
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
                    </template>
                    <template v-else>
                        <span class="inline-block text-[11px] font-medium px-2 py-0.5 rounded-full bg-[#E6F1FB] text-[#003399] mb-1.5">
                            {{ f.section ? `${f.section.subject ? f.section.subject + ' - ' : ''}${f.section.name}` : 'Lahat ng section' }}
                        </span>
                        <div class="text-sm font-medium text-slate-800">{{ f.question }}</div>
                        <div class="text-sm text-slate-500 mt-1">{{ f.answer }}</div>
                        <div class="flex gap-3 mt-2">
                            <button @click="startEdit(f)" class="text-xs text-[#003399] hover:underline">Edit</button>
                            <button @click="destroy(f.id)" class="text-xs text-red-500 hover:underline">Delete</button>
                        </div>
                    </template>
                </div>
            </div>
        </main>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({ faqs: Array, sections: Array });

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
</script>