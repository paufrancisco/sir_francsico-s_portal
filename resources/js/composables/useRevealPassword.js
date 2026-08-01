import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useRevealPassword(sectionId, currentPeriod) {
    const showModal = ref(false);
    const form = useForm({ password: '' });

    const submitReveal = () => {
        form.transform((data) => ({
            ...data,
            period: currentPeriod.value,
        })).post(`/paulo/sections/${sectionId}/students/reveal`, {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    };

    return { showModal, form, submitReveal };
}