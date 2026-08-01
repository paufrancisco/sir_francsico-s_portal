import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * @param {number|string} sectionId
 * @param {import('vue').Ref<string>} currentPeriod
 */
export function useGradeCorrections(sectionId, currentPeriod) {
    const correctionModalOpen = ref(false);
    const correctionLoadingGrades = ref(false);
    const correctionId = ref(null);
    const correctionStudentId = ref(null);
    const correctionStudentName = ref('');
    const correctionNotes = ref('');
    const correctionAttachmentUrl = ref(null);
    const correctionEditedItems = ref([]);
    const correctionGrades = ref([]);
    const correctionErrorMsg = ref('');
    const correctionResolving = ref(false);
    const correctionStatus = ref('pending');
    const correctionDecision = ref(null);

    const correctionBadgeLabel = (c) => {
        if (c.status === 'pending') return 'Pending request';
        if (c.decision === 'approved') return 'Approved';
        if (c.decision === 'rejected') return 'Rejected';
        return 'Resolved';
    };

    const correctionBadgeClass = (c) => {
        if (c.status === 'pending') return 'bg-[#E6F1FB] text-[#003399]';
        if (c.decision === 'approved') return 'bg-[#EAF3DE] text-[#3B6D11]';
        if (c.decision === 'rejected') return 'bg-red-50 text-red-600';
        return 'bg-slate-100 text-slate-500';
    };

    const openCorrectionReview = async (row) => {
        if (!row.pending_correction) return;

        correctionModalOpen.value = true;
        correctionLoadingGrades.value = true;
        correctionErrorMsg.value = '';
        correctionId.value = row.pending_correction.id;
        correctionStudentId.value = row.id;
        correctionStudentName.value = row.name;
        correctionNotes.value = row.pending_correction.notes;
        correctionAttachmentUrl.value = row.pending_correction.attachment_url;
        correctionEditedItems.value = row.pending_correction.edited_items ?? [];
        correctionStatus.value = row.pending_correction.status;
        correctionDecision.value = row.pending_correction.decision;
        correctionGrades.value = [];

        try {
            const res = await axios.get(`/paulo/sections/${sectionId}/students/${row.id}/grades`, {
                params: { period: currentPeriod.value },
            });
            correctionGrades.value = res.data.grades.map((g) => {
                const proposal = correctionEditedItems.value.find(
                    (p) => p.category === g.category && p.title === g.title
                );
                return {
                    ...g,
                    editValue: proposal ? proposal.claimed_score : g.score,
                    hasProposal: !!proposal,
                };
            });
        } catch (e) {
            correctionErrorMsg.value = "Failed to load the student's grades.";
        } finally {
            correctionLoadingGrades.value = false;
        }
    };

    const closeCorrectionModal = () => {
        correctionModalOpen.value = false;
    };

    const approveCorrectionInline = async () => {
        correctionResolving.value = true;
        correctionErrorMsg.value = '';

        try {
            const changed = correctionGrades.value.filter((g) => Number(g.editValue) !== Number(g.score));
            for (const g of changed) {
                await axios.patch(`/paulo/grades/${g.id}`, { score: g.editValue });
            }

            await axios.patch(`/paulo/grade-corrections/${correctionId.value}/resolve`, {
                decision: 'approved',
            });

            correctionStatus.value = 'resolved';
            correctionDecision.value = 'approved';

            closeCorrectionModal();
            router.reload({ only: ['gradesBreakdown'] });
        } catch (e) {
            correctionErrorMsg.value = e.response?.data?.message
                || Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
                || 'Failed to approve, please try again.';
        } finally {
            correctionResolving.value = false;
        }
    };

    const rejectCorrectionInline = async () => {
        correctionResolving.value = true;
        correctionErrorMsg.value = '';

        try {
            await axios.patch(`/paulo/grade-corrections/${correctionId.value}/resolve`, {
                decision: 'rejected',
            });

            correctionStatus.value = 'resolved';
            correctionDecision.value = 'rejected';

            closeCorrectionModal();
            router.reload({ only: ['gradesBreakdown'] });
        } catch (e) {
            correctionErrorMsg.value = e.response?.data?.message ?? 'Failed to reject, please try again.';
        } finally {
            correctionResolving.value = false;
        }
    };

    return {
        correctionModalOpen,
        correctionLoadingGrades,
        correctionStudentName,
        correctionNotes,
        correctionAttachmentUrl,
        correctionGrades,
        correctionErrorMsg,
        correctionResolving,
        correctionStatus,
        correctionDecision,
        correctionBadgeLabel,
        correctionBadgeClass,
        openCorrectionReview,
        closeCorrectionModal,
        approveCorrectionInline,
        rejectCorrectionInline,
    };
}