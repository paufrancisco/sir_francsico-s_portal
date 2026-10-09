import { ref, watchEffect } from 'vue';

// TODO: shared state, kaya pareho ang theme sa lahat ng admin pages
const KEY = 'admin_theme';
const isDark = ref(false);
try { isDark.value = localStorage.getItem(KEY) === 'dark'; } catch (e) {}

export function useAdminTheme() {
    watchEffect(() => {
        document.documentElement.classList.toggle('admin-dark', isDark.value);
    });

    const toggleDark = () => {
        isDark.value = !isDark.value;
        try { localStorage.setItem(KEY, isDark.value ? 'dark' : 'light'); } catch (e) {}
    };

    return { isDark, toggleDark };
}
