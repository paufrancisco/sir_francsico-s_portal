<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    student_number: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/student/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #fff;">
        <div style="width: 100%; max-width: 400px; border: 6px solid #003399; border-radius: 8px; padding: 32px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h1 style="color: #003399; font-size: 22px; font-weight: 600;">Sir Francisco's Portal</h1>
                <p style="color: #666; font-size: 13px;">Mag-login gamit ang student number mo</p>
            </div>

            <form @submit.prevent="submit">
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; color: #412402; font-weight: 500;">Student number</label>
                    <input
                        type="text"
                        v-model="form.student_number"
                        style="width: 100%; padding: 8px 12px; border: 1px solid #FFCC00; border-radius: 6px; margin-top: 4px;"
                        autofocus
                    />
                    <div v-if="form.errors.student_number" style="color: #E24B4A; font-size: 12px; margin-top: 4px;">
                        {{ form.errors.student_number }}
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; color: #412402; font-weight: 500;">Password</label>
                    <input
                        type="password"
                        v-model="form.password"
                        style="width: 100%; padding: 8px 12px; border: 1px solid #FFCC00; border-radius: 6px; margin-top: 4px;"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    style="width: 100%; background: #003399; color: #fff; padding: 10px; border-radius: 6px; font-size: 14px; border: none; cursor: pointer;"
                >
                    Sign in
                </button>
            </form>
        </div>
    </div>
</template>