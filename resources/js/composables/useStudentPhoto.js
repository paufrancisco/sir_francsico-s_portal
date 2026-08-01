import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useStudentPhoto(sectionId) {
    // ---- Bulk photo import (ZIP) ----
    const photosImporting = ref(false);

    const uploadPhotosZip = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        photosImporting.value = true;
        router.post(`/paulo/sections/${sectionId}/students/photos/import`, { file }, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => { photosImporting.value = false; e.target.value = ''; },
        });
    };

    // ---- Single student photo modal ----
    const photoModalOpen = ref(false);
    const photoStudent = ref(null);
    const photoFile = ref(null);
    const photoPreview = ref(null);
    const photoUploading = ref(false);
    const photoErrorMsg = ref('');

    const openPhotoUpload = (student) => {
        photoStudent.value = student;
        photoFile.value = null;
        photoPreview.value = null;
        photoErrorMsg.value = '';
        photoModalOpen.value = true;
    };

    const closePhotoUpload = () => {
        photoModalOpen.value = false;
        photoStudent.value = null;
        photoFile.value = null;
        photoPreview.value = null;
    };

    const onPhotoSelected = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        photoErrorMsg.value = '';
        photoFile.value = file;
        photoPreview.value = URL.createObjectURL(file);
    };

    const submitPhotoUpload = () => {
        if (!photoFile.value || !photoStudent.value) return;

        photoUploading.value = true;
        photoErrorMsg.value = '';

        router.post(
            `/paulo/sections/${sectionId}/students/${photoStudent.value.id}/photo`,
            { photo: photoFile.value },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => { closePhotoUpload(); },
                onError: (errors) => {
                    photoErrorMsg.value = errors.photo || 'Failed to upload picture.';
                },
                onFinish: () => { photoUploading.value = false; },
            }
        );
    };

    const removePhoto = () => {
        if (!photoStudent.value) return;
        if (!confirm('Are you sure you want to remove the picture?')) return;

        photoUploading.value = true;
        router.delete(`/paulo/sections/${sectionId}/students/${photoStudent.value.id}/photo`, {
            preserveScroll: true,
            onSuccess: () => { closePhotoUpload(); },
            onFinish: () => { photoUploading.value = false; },
        });
    };

    return {
        photosImporting,
        uploadPhotosZip,
        photoModalOpen,
        photoStudent,
        photoFile,
        photoPreview,
        photoUploading,
        photoErrorMsg,
        openPhotoUpload,
        closePhotoUpload,
        onPhotoSelected,
        submitPhotoUpload,
        removePhoto,
    };
}