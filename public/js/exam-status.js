/**
 * InternBoot - Exam Status Handler (M6 Integration)
 * Connects public/exam.html to backend API:
 * - GET api/exam/exam_status.php
 */
document.addEventListener("DOMContentLoaded", async () => {
    await initExamStatusModule();
});

async function initExamStatusModule() {
    const container = document.getElementById("exam-details-body");
    if (!container) return;

    // Attempt ID is now automatically resolved by the backend for the logged in user
    try {
        const response = await fetch(`api/exam/exam_status.php`, {
            method: "GET",
            headers: { "Accept": "application/json" }
        });

        const payload = await response.json();

        if (!response.ok || payload.status !== "success" || !payload.data) {
            if (payload.message === "No attempt found for this candidate") {
                container.innerHTML = `
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                        <h2 class="text-base font-semibold text-slate-700 mb-1">No Slot Booked Yet</h2>
                        <p class="text-[13px] text-slate-500 mb-3">You haven't booked an exam slot yet. Please select an available slot from your assigned batch.</p>
                        <a href="batches-slots.html" class="inline-block bg-blue-600 text-white text-[13px] font-semibold px-4 py-2 rounded-lg hover:bg-blue-700 transition">Go to Batches &amp; Slots →</a>
                    </div>`;
                return;
            }
            if (payload.message === "Preference saved, awaiting batch formation") {
                container.innerHTML = `
                    <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-center">
                        <h2 class="text-base font-semibold text-slate-700 mb-1">Preference Saved</h2>
                        <p class="text-[13px] text-slate-500 mb-3">Your choice for <strong>${escapeHtml(payload.data?.preferred_date || 'your preferred date')}</strong> has been recorded. Once 100 candidates choose this slot, the batch will be created automatically.</p>
                        <a href="batches-slots.html" class="inline-block bg-emerald-600 text-white text-[13px] font-semibold px-4 py-2 rounded-lg hover:bg-emerald-700 transition">View Batch Details →</a>
                    </div>`;
                return;
            }
            if (payload.message === "Batch assigned, no attempt yet") {
                const dateRaw = payload.data?.exam_date;
                const dateStr = dateRaw ? new Date(dateRaw).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : "your scheduled date";

                const formatTime = (timeRaw) => {
                    if (!timeRaw) return "";
                    const dt = new Date(`1970-01-01T${timeRaw}Z`);
                    return dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true, timeZone: 'UTC' });
                };

                const timeStart = formatTime(payload.data?.start_time);
                const timeEnd = formatTime(payload.data?.end_time);
                const timeStr = timeStart && timeEnd ? ` from <strong>${timeStart} - ${timeEnd}</strong>` : "";

                container.innerHTML = `
                    <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-center">
                        <h2 class="text-base font-semibold text-slate-700 mb-1">Batch Scheduled</h2>
                        <p class="text-[13px] text-slate-500 mb-3">Your batch is scheduled on <strong>${escapeHtml(dateStr)}</strong>${timeStr}. Your exam slot is confirmed and you will be able to start the exam from this page when it begins.</p>
                        <a href="batches-slots.html" class="inline-block bg-emerald-600 text-white text-[13px] font-semibold px-4 py-2 rounded-lg hover:bg-emerald-700 transition">View Batch Details →</a>
                    </div>`;
                return;
            }
            container.innerHTML = `
                <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center">
                    <h3 class="text-base font-semibold text-red-700 mb-1">Attempt Access Error</h3>
                    <p class="text-[13px] text-red-600 mb-3">${escapeHtml(payload.message || "Attempt not found or access denied.")}</p>
                    <a href="batches-slots.html" class="inline-block bg-blue-600 text-white text-[13px] font-semibold px-4 py-2 rounded-lg hover:bg-blue-700 transition">Book a New Slot →</a>
                </div>`;
            return;
        }

        const data = payload.data;
        const status = data.status;
        const remainingSec = Number(data.remaining_seconds) || 0;

        // 1. Exam in progress & active time remaining -> Resume Exam
        if (status === "in_progress" && data.start_time && remainingSec > 0) {
            const minutes = Math.floor(remainingSec / 60);
            const seconds = remainingSec % 60;
            const formattedTimer = `${minutes}m ${seconds}s`;

            container.innerHTML = `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Attempt ID</p>
                        <p class="text-sm font-bold text-slate-700">#${data.attempt_id}</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Status</p>
                        <p class="text-sm font-bold text-blue-600">In Progress</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Time Remaining</p>
                        <p class="text-sm font-bold text-blue-600">${formattedTimer}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Answered</p>
                        <p class="text-sm font-bold text-slate-700">${data.answered_count} / ${data.total_questions}</p>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                    <p class="text-sm font-semibold text-slate-700 mb-1">Exam Session Active</p>
                    <p class="text-[13px] text-slate-500 mb-3">Your exam timer is currently running. Resume your assessment before the timer expires.</p>
                    <a href="take-exam.php?attempt_id=${data.attempt_id}" class="inline-block bg-blue-600 text-white text-[13px] font-semibold px-5 py-2 rounded-lg hover:bg-blue-700 transition">Resume Exam →</a>
                </div>`;
            return;
        }

        // 2. Exam submitted or expired -> Show final status, no start button, link to results
        if (status === "submitted" || status === "expired") {
            const isSubmitted = status === "submitted";
            container.innerHTML = `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Attempt ID</p>
                        <p class="text-sm font-bold text-slate-700">#${data.attempt_id}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Final Status</p>
                        <p class="text-sm font-bold ${isSubmitted ? 'text-emerald-600' : 'text-slate-400'}">${isSubmitted ? 'Submitted' : 'Expired'}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Questions Answered</p>
                        <p class="text-sm font-bold text-slate-700">${data.answered_count} / ${data.total_questions}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Completed At</p>
                        <p class="text-sm font-bold text-slate-700" style="font-size:12px;">${escapeHtml(data.submitted_at || "Completed")}</p>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                    <p class="text-sm font-semibold text-slate-700 mb-1">${isSubmitted ? 'Assessment Completed' : 'Assessment Expired'}</p>
                    <p class="text-[13px] text-slate-500 mb-3">${isSubmitted ? 'Your answers have been submitted for evaluation.' : 'Your exam session ended.'} View your evaluated score and certificate level on the Results page.</p>
                    <a href="results.html" class="inline-block bg-emerald-600 text-white text-[13px] font-semibold px-5 py-2 rounded-lg hover:bg-emerald-700 transition">View Results →</a>
                </div>`;
            return;
        }

        // 3. Attempt exists but not started -> Decide between "Ready to Start" and "Exam Window Locked"
        if (data.can_start) {
            container.innerHTML = `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Attempt ID</p>
                        <p class="text-sm font-bold text-slate-700">#${data.attempt_id}</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Status</p>
                        <p class="text-sm font-bold text-blue-600">Ready to Start</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Questions</p>
                        <p class="text-sm font-bold text-slate-700">${data.total_questions}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Access</p>
                        <p class="text-sm font-bold text-emerald-600">Slot Active</p>
                    </div>
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                    <div class="text-3xl mb-2">📝</div>
                    <p class="text-sm font-semibold text-slate-700 mb-1">Your Exam Slot is Now Open</p>
                    <p class="text-[13px] text-slate-500 mb-3">Click below to launch the assessment engine. Once started, your assessment timer will run continuously.</p>
                    <a href="take-exam.php?attempt_id=${data.attempt_id}" class="inline-block bg-blue-600 text-white text-[13px] font-semibold px-6 py-2.5 rounded-lg hover:bg-blue-700 transition">Start Exam →</a>
                </div>`;
        } else {
            const gateMessage = data.gate_message || "Your exam slot is not open yet.";
            container.innerHTML = `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Attempt ID</p>
                        <p class="text-sm font-bold text-slate-700">#${data.attempt_id}</p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Status</p>
                        <p class="text-sm font-bold text-amber-600">Scheduled</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Questions</p>
                        <p class="text-sm font-bold text-slate-700">${data.total_questions}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Access</p>
                        <p class="text-sm font-bold text-slate-400">Locked 🔒</p>
                    </div>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-center">
                    <div class="text-3xl mb-2">🔒</div>
                    <p class="text-sm font-semibold text-slate-700 mb-1">Exam Window Locked</p>
                    <p class="text-[13px] text-amber-700 mb-3">${escapeHtml(gateMessage)}</p>
                    <button disabled class="bg-slate-200 text-slate-400 text-[13px] font-semibold px-5 py-2 rounded-lg cursor-not-allowed">Exam Not Open Yet</button>
                </div>`;
        }

    } catch (err) {
        console.error("Exam status error:", err);
        container.innerHTML = `
            <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center">
                <p class="text-[13px] text-red-600">${escapeHtml(err.message || "Failed to load exam status.")}</p>
            </div>`;
    }
}

function escapeHtml(str) {
    if (!str) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
