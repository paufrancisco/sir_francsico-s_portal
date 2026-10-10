export const activityTypes = {
    grades_confirmed: { label: 'Grades confirmed', cls: 'bg-emerald-100 text-emerald-700' },
    grades_recheck: { label: 'Complaint (recheck)', cls: 'bg-red-100 text-red-600' },
    grades_viewed: { label: 'Viewed grades', cls: 'bg-slate-100 text-slate-600' },
    chat_message: { label: 'AI chat', cls: 'bg-violet-100 text-violet-700' },
    appointment_booked: { label: 'Appointment', cls: 'bg-amber-100 text-amber-700' },
    classmate_message: { label: 'Classmate post', cls: 'bg-sky-100 text-sky-700' },
    wall_post: { label: 'Freedom Wall post', cls: 'bg-pink-100 text-pink-700' },
};

export const timeAgo = (iso) => {
    if (!iso) return '';
    const s = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
    if (s < 60) return 'just now';
    if (s < 3600) return `${Math.floor(s / 60)}m ago`;
    if (s < 86400) return `${Math.floor(s / 3600)}h ago`;
    return `${Math.floor(s / 86400)}d ago`;
};
