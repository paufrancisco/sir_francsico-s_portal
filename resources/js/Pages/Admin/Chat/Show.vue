<template>
    <AdminLayout>
        <main class="max-w-2xl mx-auto px-6 lg:px-10 py-8 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-slate-800">{{ student.full_name }}</div>
                    <div class="text-xs text-slate-400">{{ student.student_number }}</div>
                </div>
                <Link href="/paulo/chat" class="text-xs text-slate-400 hover:underline">← Back sa lahat</Link>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4 space-y-2 max-h-[60vh] overflow-y-auto">
                <div
                    v-for="m in messages"
                    :key="m.id"
                    :class="m.sender === 'admin' ? 'ml-auto bg-[#003399] text-white' : m.sender === 'student' ? 'mr-auto bg-slate-100 text-slate-700' : 'mr-auto bg-[#FAEEDA] text-[#854F0B]'"
                    class="max-w-[80%] text-sm rounded-lg px-3 py-2 whitespace-pre-wrap"
                >
                    <div class="text-[10px] font-medium mb-0.5 opacity-70">
                        {{ m.sender === 'admin' ? 'Ikaw' : m.sender === 'student' ? student.full_name : 'AI Assistant' }}
                    </div>
                    {{ m.body }}
                </div>
            </div>

            <form @submit.prevent="submitReply" class="flex gap-2">
                <input
                    v-model="form.body"
                    type="text"
                    placeholder="I-type ang sagot mo..."
                    class="flex-1 text-sm border border-slate-200 rounded-lg px-3 py-2"
                />
                <button type="submit" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg">
                    Send
                </button>
            </form>
        </main>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    student: Object,
    messages: Array,
});

const form = useForm({ body: '' });

const submitReply = () => {
    form.post(`/paulo/chat/${props.student.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>