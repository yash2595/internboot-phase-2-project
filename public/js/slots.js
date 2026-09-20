/**
 * InternBoot - Batches & Slots Candidate Flow (M5 Integration)
 * Connects public/batches-slots.html to backend APIs:
 * - GET api/dashboard.php
 * - GET api/slots/available.php
 * - POST api/slots/book.php
 */
document.addEventListener("DOMContentLoaded", async () => {
    await initSlotsModule();
});

async function initSlotsModule() {
    const noticeContainer = document.getElementById("notice-container");
    const slotsListEl = document.getElementById("slots-list");

    try {
        const response = await fetch("api/dashboard.php", {
            method: "GET",
            headers: { "Accept": "application/json" }
        });

        const payload = await response.json();
        if (!response.ok || payload.status !== "success" || !payload.data) {
            throw new Error(payload.message || "Failed to load candidate details.");
        }

        const data = payload.data;

        // Render Batch Details
        if (data.batch) {
            const batchNameEl = document.getElementById("batch-name");
            const batchStatusEl = document.getElementById("batch-status-badge");
            const batchCandEl = document.getElementById("batch-candidates");

            if (batchNameEl) batchNameEl.textContent = data.batch.name || "Awaiting Formation";
            if (batchStatusEl) {
                batchStatusEl.textContent = data.batch.status || "Pending";
                batchStatusEl.className = `badge ${data.batch.status === "Assigned" ? "green" : "gray"}`;
            }
            if (batchCandEl) batchCandEl.textContent = data.batch.candidates || "—";
        }

        // Render Booked Slot status if already booked
        updateBookedSlotSection(data);

        // Check enrollment eligibility
        const isEligible = data.enrollment && (data.enrollment.eligibility_status === "eligible" || data.enrollment.status === "Enrolled");
        if (!isEligible) {
            if (noticeContainer) {
                noticeContainer.innerHTML = `
                    <div class="notice notice-error" style="background:#fdf2f2; border:1px solid #f8cdcd; color:#b91c1c; padding:14px 18px; border-radius:8px; margin-bottom:18px;">
                        <strong>Action Required:</strong> Your registration fee payment or enrollment eligibility is pending. 
                        Please complete your payment to unlock exam slot booking.
                        <a href="payment.html" class="btn btn-ib-primary btn-sm" style="margin-left:12px; background:#2563eb; color:#fff; padding:6px 12px; border-radius:6px; text-decoration:none; display:inline-block;">Go to Payment →</a>
                    </div>`;
            }
            if (slotsListEl) {
                slotsListEl.innerHTML = `<p style="color:#60728b;">Slot booking unlocks automatically once your payment is completed.</p>`;
            }
            return;
        }

        // Check if candidate is awaiting batch formation
        if (!data.batch || !data.batch.name || data.batch.name === "—") {
            if (slotsListEl) {
                slotsListEl.innerHTML = `<p style="color:#60728b;">You are in the queue for batch formation. Exam slots will be available as soon as your batch is formed (minimum threshold reached).</p>`;
            }
            return;
        }

        // Fetch available slots
        const assessmentId = data.assessment ? data.assessment.id : 1;
        await loadAvailableSlots(assessmentId);

    } catch (err) {
        console.error("Slots module error:", err);
        if (noticeContainer) {
            noticeContainer.innerHTML = `
                <div class="notice notice-error" style="background:#fdf2f2; border:1px solid #f8cdcd; color:#b91c1c; padding:14px 18px; border-radius:8px; margin-bottom:18px;">
                    ${escapeHtml(err.message || "Unable to load slot details.")}
                </div>`;
        }
    }
}

function updateBookedSlotSection(dashData) {
    const bookedDateEl = document.getElementById("booked-exam-date");
    const bookedTimeEl = document.getElementById("booked-slot-time");
    const bookedStatusEl = document.getElementById("booked-slot-status");

    const attemptId = localStorage.getItem("ib_attempt_id");

    if (dashData.exam && dashData.exam.status && dashData.exam.status !== "—" && dashData.exam.status !== "Not Started") {
        if (bookedDateEl) bookedDateEl.textContent = dashData.exam.exam_date || "Scheduled";
        if (bookedTimeEl) bookedTimeEl.textContent = dashData.exam.slot_time || "Assigned Slot";
        if (bookedStatusEl) {
            bookedStatusEl.textContent = dashData.exam.status;
            bookedStatusEl.className = "badge green";
        }
    } else if (attemptId) {
        if (bookedStatusEl) {
            bookedStatusEl.textContent = "Booked";
            bookedStatusEl.className = "badge green";
        }
    }
}

function getWeekdayLabel(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    if (isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-US', { weekday: 'long' });
}

function formatHHMM(timeStr) {
    if (!timeStr) return '';
    const parts = timeStr.split(':');
    if (parts.length >= 2) {
        return `${parts[0]}:${parts[1]}`;
    }
    return timeStr;
}

async function loadAvailableSlots(assessmentId) {
    const slotsListEl = document.getElementById("slots-list");
    if (!slotsListEl) return;

    try {
        const response = await fetch(`api/slots/available.php?assessment_id=${encodeURIComponent(assessmentId)}`, {
            method: "GET",
            headers: { "Accept": "application/json" }
        });

        const payload = await response.json();
        if (!response.ok || payload.status !== "success" || !Array.isArray(payload.data)) {
            throw new Error(payload.message || "Failed to fetch available slots.");
        }

        const slots = payload.data;
        if (slots.length === 0) {
            slotsListEl.innerHTML = `<p style="color:#60728b;">No available slots found for your batch at this time.</p>`;
            return;
        }

        slotsListEl.innerHTML = `
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">
                ${slots.map(s => {
                    const dayLabel = getWeekdayLabel(s.exam_date);
                    const dateHeader = dayLabel ? `${dayLabel} (${s.exam_date || ''})` : (s.exam_date || '');
                    const startTimeFormatted = formatHHMM(s.start_time);
                    const endTimeFormatted = formatHHMM(s.end_time);
                    return `
                    <div style="border:1px solid #e5ebf2; border-radius:10px; padding:18px; background:#ffffff; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                        <div style="font-weight:700; font-size:16px; color:#17243a; margin-bottom:6px;">
                            ${escapeHtml(dateHeader)}
                        </div>
                        <div style="font-size:14px; color:#4b5563; margin-bottom:10px;">
                            ⏰ ${escapeHtml(startTimeFormatted)} – ${escapeHtml(endTimeFormatted)}
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
                            <span class="badge ${s.seats_remaining > 0 ? 'blue' : 'gray'}">
                                ${s.seats_remaining} seats remaining
                            </span>
                            <button 
                                class="btn-book-slot" 
                                data-slot-id="${s.exam_slot_id}" 
                                data-assessment-id="${assessmentId}"
                                ${s.seats_remaining <= 0 ? 'disabled' : ''}
                                style="background:#2563eb; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:700; cursor:pointer;"
                            >
                                Book This Slot
                            </button>
                        </div>
                    </div>`;
                }).join('')}
            </div>`;

        slotsListEl.querySelectorAll(".btn-book-slot").forEach(btn => {
            btn.addEventListener("click", () => handleBookSlotClick(btn));
        });

    } catch (err) {
        slotsListEl.innerHTML = `<p style="color:#dc2626;">Error: ${escapeHtml(err.message)}</p>`;
    }
}

async function handleBookSlotClick(btn) {
    const slotId = btn.dataset.slotId;
    const assessmentId = btn.dataset.assessmentId;
    const noticeContainer = document.getElementById("notice-container");

    const parsedSlotId = Number(slotId);
    if (!slotId || isNaN(parsedSlotId) || !Number.isInteger(parsedSlotId) || parsedSlotId <= 0) {
        if (noticeContainer) {
            noticeContainer.innerHTML = `
                <div class="notice notice-error" style="background:#fdf2f2; border:1px solid #f8cdcd; color:#b91c1c; padding:14px 18px; border-radius:8px; margin-bottom:18px;">
                    ❌ ${escapeHtml("Invalid slot selected")}
                </div>`;
        }
        return;
    }

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = "Booking...";

    try {
        const response = await fetch("api/slots/book.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                assessment_id: Number(assessmentId),
                exam_slot_id: Number(slotId)
            })
        });

        const payload = await response.json();

        if (!response.ok || payload.status !== "success" || !payload.data) {
            throw new Error(payload.message || "Failed to book slot.");
        }

        const bookingData = payload.data;
        const attemptId = bookingData.attempt_id;

        if (attemptId) {
            localStorage.setItem("ib_attempt_id", attemptId);
        }

        if (noticeContainer) {
            noticeContainer.innerHTML = `
                <div class="notice notice-success" style="background:#e9f8f0; border:1px solid #c3edd7; color:#127249; padding:14px 18px; border-radius:8px; margin-bottom:18px;">
                    🎉 <strong>Slot Booked Successfully!</strong> Your exam attempt ID is #${escapeHtml(attemptId)}.
                    <a href="exam.html" class="btn btn-ib-primary btn-sm" style="margin-left:12px; background:#18a56a; color:#fff; padding:6px 12px; border-radius:6px; text-decoration:none; display:inline-block;">Go to Exam Page →</a>
                </div>`;
        }

        await loadAvailableSlots(assessmentId);

        document.querySelectorAll(".btn-book-slot").forEach(b => {
            b.disabled = true;
            b.textContent = "Already Booked";
            b.style.opacity = "0.6";
            b.style.cursor = "not-allowed";
        });

    } catch (err) {
        btn.disabled = false;
        btn.textContent = originalText;

        if (noticeContainer) {
            noticeContainer.innerHTML = `
                <div class="notice notice-error" style="background:#fdf2f2; border:1px solid #f8cdcd; color:#b91c1c; padding:14px 18px; border-radius:8px; margin-bottom:18px;">
                    ❌ ${escapeHtml(err.message || "Booking failed.")}
                </div>`;
        }
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
