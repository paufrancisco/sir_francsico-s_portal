import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export const periods = [
    { value: 'prelim', label: 'Prelim' },
    { value: 'midterm', label: 'Midterm' },
    { value: 'prefinal', label: 'Pre-Final' },
    { value: 'finals', label: 'Finals' },
];

/**
 * Prelim / Midterm / Pre-Final / Finals period switcher.
 * Shared between the Grades tab and the Topics tab (they reload together).
 *
 * @param {number|string} sectionId
 * @param {import('vue').Ref<string>} currentPeriod - reactive ref/computed of the current period prop
 */
export function usePeriodSwitcher(sectionId, currentPeriod) {
    const periodLoading = ref(false);

    const switchPeriod = (period) => {
        if (period === currentPeriod.value || periodLoading.value) return;

        periodLoading.value = true;
        router.get(`/paulo/sections/${sectionId}`, { period }, {
            preserveScroll: true,
            preserveState: true,
            only: ['gradeItems', 'gradesBreakdown', 'currentPeriod', 'topics', 'archivedTopics'],
            onFinish: () => { periodLoading.value = false; },
        });
    };

    return { periods, periodLoading, switchPeriod };
}