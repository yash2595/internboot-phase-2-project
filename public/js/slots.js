/**
 * InternBoot - Batches & Slots Candidate Flow (M5 Integration)
 * Connects public/batches-slots.html to backend APIs:
 * - GET api/dashboard.php
 * - GET api/slots/available.php
 * - POST api/slots/book.php
 */
let csrfToken = null;

async function getCsrfToken() {
    if (csrfToken) return csrfToken;
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag && metaTag.content) {
        csrfToken = metaTag.content;
        return csrfToken;
    }
    try {
        const res = await fetch("api/auth/csrf.php", {
            credentials: "same-origin",
            headers: { Accept: "application/json" }
        });
        const payload = await res.json();
        csrfToken = payload.data?.token || null;
    } catch {
        const res = await fetch("/api/admin/evaluate.php?action=csrf", {
            credentials: "same-origin",
            headers: { Accept: "application/json" }
        });
        const payload = await res.json();
        csrfToken = payload.data?.token || null;
    }
    if (!csrfToken) throw new Error("Security token could not be loaded.");
    return csrfToken;
}

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
            const assessmentId = data.assessment ? data.assessment.id : 1;
            await loadPreferences(assessmentId);
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
        const token = await getCsrfToken();
        const response = await fetch("api/slots/book.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-Token": token
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
 
 a s y n c   f u n c t i o n   l o a d P r e f e r e n c e s ( a s s e s s m e n t I d )   {  
         c o n s t   s l o t s L i s t E l   =   d o c u m e n t . g e t E l e m e n t B y I d ( " s l o t s - l i s t " ) ;  
         i f   ( ! s l o t s L i s t E l )   r e t u r n ;  
         c o n s t   n o t i c e C o n t a i n e r   =   d o c u m e n t . g e t E l e m e n t B y I d ( " n o t i c e - c o n t a i n e r " ) ;  
  
         t r y   {  
                 c o n s t   r e s p o n s e   =   a w a i t   f e t c h ( ` a p i / s l o t s / p r e f e r e n c e . p h p ? a s s e s s m e n t _ i d = $ { e n c o d e U R I C o m p o n e n t ( a s s e s s m e n t I d ) } ` ,   {  
                         m e t h o d :   " G E T " ,  
                         h e a d e r s :   {   " A c c e p t " :   " a p p l i c a t i o n / j s o n "   }  
                 } ) ;  
  
                 c o n s t   p a y l o a d   =   a w a i t   r e s p o n s e . j s o n ( ) ;  
                 i f   ( ! r e s p o n s e . o k   | |   p a y l o a d . s t a t u s   ! = =   " s u c c e s s " )   {  
                         t h r o w   n e w   E r r o r ( p a y l o a d . m e s s a g e   | |   " F a i l e d   t o   f e t c h   p r e f e r e n c e   o p t i o n s . " ) ;  
                 }  
  
                 c o n s t   d a t a   =   p a y l o a d . d a t a ;  
                 c o n s t   c u r r e n t P r e f   =   d a t a . c u r r e n t _ p r e f e r e n c e   | |   { } ;  
                 c o n s t   o p t i o n s   =   d a t a . o p t i o n s   | |   [ ] ;  
  
                 i f   ( o p t i o n s . l e n g t h   = = =   0 )   {  
                         s l o t s L i s t E l . i n n e r H T M L   =   ` < p   s t y l e = " c o l o r : # 6 0 7 2 8 b ; " > N o   a v a i l a b l e   d a t e s   f o u n d   f o r   s e l e c t i o n   a t   t h i s   t i m e . < / p > ` ;  
                         r e t u r n ;  
                 }  
  
                 l e t   h t m l   =   `  
                         < d i v   s t y l e = " m a r g i n - b o t t o m : 2 0 p x ;   b a c k g r o u n d : # f 0 f 9 f f ;   b o r d e r : 1 p x   s o l i d   # b a e 6 f d ;   p a d d i n g : 1 5 p x ;   b o r d e r - r a d i u s : 8 p x ;   c o l o r : # 0 3 6 9 a 1 ; " >  
                                 < s t r o n g > B a t c h   S e l e c t i o n   M o d e : < / s t r o n g >   P l e a s e   s e l e c t   y o u r   p r e f e r r e d   e x a m   d a t e .   O n c e   1 0 0   c a n d i d a t e s   c h o o s e   t h e   s a m e   d a t e ,   y o u r   b a t c h   w i l l   b e   f o r m e d   a u t o m a t i c a l l y !  
                         < / d i v >  
                 ` ;  
                  
                 i f   ( c u r r e n t P r e f . p r e f e r r e d _ d a t e   & &   c u r r e n t P r e f . p r e f e r r e d _ t i m e _ s l o t )   {  
                         c o n s t   f o r m a t t e d T i m e   =   f o r m a t H H M M ( c u r r e n t P r e f . p r e f e r r e d _ t i m e _ s l o t ) ;  
                         h t m l   + =   `  
                                 < d i v   s t y l e = " m a r g i n - b o t t o m : 2 4 p x ;   p a d d i n g : 1 6 p x ;   b o r d e r : 1 p x   s o l i d   # 1 0 b 9 8 1 ;   b o r d e r - r a d i u s : 8 p x ;   b a c k g r o u n d : # e c f d f 5 ; " >  
                                         < h 3   s t y l e = " m a r g i n : 0   0   8 p x ;   c o l o r : # 0 4 7 8 5 7 ;   f o n t - s i z e : 1 6 p x ; " > Y o u r   C u r r e n t   P r e f e r e n c e < / h 3 >  
                                         < p   s t y l e = " m a r g i n : 0 ;   c o l o r : # 0 6 5 f 4 6 ; " > < s t r o n g > D a t e : < / s t r o n g >   $ { e s c a p e H t m l ( c u r r e n t P r e f . p r e f e r r e d _ d a t e ) }   < b r > < s t r o n g > T i m e : < / s t r o n g >   $ { e s c a p e H t m l ( f o r m a t t e d T i m e ) } < / p >  
                                         < p   s t y l e = " m a r g i n : 8 p x   0   0 ;   f o n t - s i z e : 1 3 p x ;   c o l o r : # 0 4 7 8 5 7 ; " > W a i t i n g   f o r   o t h e r   c a n d i d a t e s   t o   s e l e c t   t h i s   s l o t . . . < / p >  
                                 < / d i v >  
                                 < h 3   s t y l e = " f o n t - s i z e : 1 6 p x ;   m a r g i n - b o t t o m : 1 2 p x ; " > C h a n g e   P r e f e r e n c e < / h 3 >  
                         ` ;  
                 }  
  
                 h t m l   + =   ` < d i v   s t y l e = " d i s p l a y : g r i d ;   g r i d - t e m p l a t e - c o l u m n s :   r e p e a t ( a u t o - f i l l ,   m i n m a x ( 2 8 0 p x ,   1 f r ) ) ;   g a p : 1 6 p x ; " > ` ;  
                  
                 o p t i o n s . f o r E a c h ( o p t   = >   {  
                         c o n s t   d a y L a b e l   =   g e t W e e k d a y L a b e l ( o p t . d a t e ) ;  
                         c o n s t   d a t e H e a d e r   =   d a y L a b e l   ?   ` $ { d a y L a b e l }   ( $ { o p t . d a t e } ) `   :   o p t . d a t e ;  
                         c o n s t   t i m e P a r t s   =   o p t . t i m e _ s l o t . s p l i t ( ' - ' ) ;  
                         c o n s t   s t a r t T i m e F o r m a t t e d   =   f o r m a t H H M M ( t i m e P a r t s [ 0 ] ) ;  
                         c o n s t   e n d T i m e F o r m a t t e d   =   f o r m a t H H M M ( t i m e P a r t s [ 1 ] ) ;  
                          
                         c o n s t   i s C u r r e n t   =   c u r r e n t P r e f . p r e f e r r e d _ d a t e   = = =   o p t . d a t e   & &   c u r r e n t P r e f . p r e f e r r e d _ t i m e _ s l o t   = = =   o p t . t i m e _ s l o t ;  
                          
                         h t m l   + =   `  
                         < d i v   s t y l e = " b o r d e r : 1 p x   s o l i d   $ { i s C u r r e n t   ?   ' # 1 0 b 9 8 1 '   :   ' # e 5 e b f 2 ' } ;   b o r d e r - r a d i u s : 1 0 p x ;   p a d d i n g : 1 8 p x ;   b a c k g r o u n d : $ { i s C u r r e n t   ?   ' # e c f d f 5 '   :   ' # f f f f f f ' } ;   b o x - s h a d o w : 0   2 p x   8 p x   r g b a ( 0 , 0 , 0 , 0 . 0 3 ) ; " >  
                                 < d i v   s t y l e = " f o n t - w e i g h t : 7 0 0 ;   f o n t - s i z e : 1 6 p x ;   c o l o r : # 1 7 2 4 3 a ;   m a r g i n - b o t t o m : 6 p x ; " >  
                                         $ { e s c a p e H t m l ( d a t e H e a d e r ) }  
                                 < / d i v >  
                                 < d i v   s t y l e = " f o n t - s i z e : 1 4 p x ;   c o l o r : # 4 b 5 5 6 3 ;   m a r g i n - b o t t o m : 1 0 p x ; " >  
                                         � � �   $ { e s c a p e H t m l ( s t a r t T i m e F o r m a t t e d ) }   � �    $ { e s c a p e H t m l ( e n d T i m e F o r m a t t e d ) }  
                                 < / d i v >  
                                 < d i v   s t y l e = " d i s p l a y : f l e x ;   j u s t i f y - c o n t e n t : f l e x - e n d ;   m a r g i n - t o p : 1 2 p x ; " >  
                                         $ { i s C u r r e n t   ?    
                                                 ` < s p a n   s t y l e = " c o l o r : # 1 0 b 9 8 1 ;   f o n t - w e i g h t : 7 0 0 ;   d i s p l a y : f l e x ;   a l i g n - i t e m s : c e n t e r ; " > � S&   S e l e c t e d < / s p a n > `   :    
                                                 ` < b u t t o n    
                                                         c l a s s = " b t n - s e t - p r e f e r e n c e "    
                                                         d a t a - d a t e = " $ { o p t . d a t e } "    
                                                         d a t a - t i m e = " $ { o p t . t i m e _ s l o t } "  
                                                         d a t a - a s s e s s m e n t - i d = " $ { a s s e s s m e n t I d } "  
                                                         s t y l e = " b a c k g r o u n d : # 2 5 6 3 e b ;   c o l o r : # f f f ;   b o r d e r : n o n e ;   p a d d i n g : 8 p x   1 6 p x ;   b o r d e r - r a d i u s : 6 p x ;   f o n t - w e i g h t : 7 0 0 ;   c u r s o r : p o i n t e r ; "  
                                                 >  
                                                         C h o o s e   T h i s   S l o t  
                                                 < / b u t t o n > `  
                                         }  
                                 < / d i v >  
                         < / d i v > ` ;  
                 } ) ;  
                  
                 h t m l   + =   ` < / d i v > ` ;  
                 s l o t s L i s t E l . i n n e r H T M L   =   h t m l ;  
  
                 s l o t s L i s t E l . q u e r y S e l e c t o r A l l ( " . b t n - s e t - p r e f e r e n c e " ) . f o r E a c h ( b t n   = >   {  
                         b t n . a d d E v e n t L i s t e n e r ( " c l i c k " ,   ( )   = >   h a n d l e S e t P r e f e r e n c e C l i c k ( b t n ) ) ;  
                 } ) ;  
  
         }   c a t c h   ( e r r )   {  
                 s l o t s L i s t E l . i n n e r H T M L   =   ` < p   s t y l e = " c o l o r : # d c 2 6 2 6 ; " > E r r o r :   $ { e s c a p e H t m l ( e r r . m e s s a g e ) } < / p > ` ;  
         }  
 }  
  
 a s y n c   f u n c t i o n   h a n d l e S e t P r e f e r e n c e C l i c k ( b t n )   {  
         c o n s t   a s s e s s m e n t I d   =   b t n . d a t a s e t . a s s e s s m e n t I d ;  
         c o n s t   d a t e   =   b t n . d a t a s e t . d a t e ;  
         c o n s t   t i m e   =   b t n . d a t a s e t . t i m e ;  
         c o n s t   n o t i c e C o n t a i n e r   =   d o c u m e n t . g e t E l e m e n t B y I d ( " n o t i c e - c o n t a i n e r " ) ;  
  
         b t n . d i s a b l e d   =   t r u e ;  
         b t n . t e x t C o n t e n t   =   " S a v i n g . . . " ;  
  
         t r y   {  
                 c o n s t   t o k e n   =   a w a i t   g e t C s r f T o k e n ( ) ;  
                 c o n s t   r e s p o n s e   =   a w a i t   f e t c h ( " a p i / s l o t s / p r e f e r e n c e . p h p " ,   {  
                         m e t h o d :   " P O S T " ,  
                         h e a d e r s :   {  
                                 " C o n t e n t - T y p e " :   " a p p l i c a t i o n / j s o n " ,  
                                 " A c c e p t " :   " a p p l i c a t i o n / j s o n " ,  
                                 " X - C S R F - T o k e n " :   t o k e n  
                         } ,  
                         b o d y :   J S O N . s t r i n g i f y ( {  
                                 a s s e s s m e n t _ i d :   N u m b e r ( a s s e s s m e n t I d ) ,  
                                 p r e f e r r e d _ d a t e :   d a t e ,  
                                 p r e f e r r e d _ t i m e _ s l o t :   t i m e  
                         } )  
                 } ) ;  
  
                 c o n s t   p a y l o a d   =   a w a i t   r e s p o n s e . j s o n ( ) ;  
  
                 i f   ( ! r e s p o n s e . o k   | |   p a y l o a d . s t a t u s   ! = =   " s u c c e s s " )   {  
                         t h r o w   n e w   E r r o r ( p a y l o a d . m e s s a g e   | |   " F a i l e d   t o   s a v e   p r e f e r e n c e . " ) ;  
                 }  
  
                 i f   ( n o t i c e C o n t a i n e r )   {  
                         n o t i c e C o n t a i n e r . i n n e r H T M L   =   `  
                                 < d i v   c l a s s = " n o t i c e   n o t i c e - s u c c e s s "   s t y l e = " b a c k g r o u n d : # f 0 f d f 4 ;   b o r d e r : 1 p x   s o l i d   # b b f 7 d 0 ;   c o l o r : # 1 6 6 5 3 4 ;   p a d d i n g : 1 4 p x   1 8 p x ;   b o r d e r - r a d i u s : 8 p x ;   m a r g i n - b o t t o m : 1 8 p x ; " >  
                                         � S&   P r e f e r e n c e   s a v e d !   Y o u   w i l l   b e   a l l o c a t e d   t o   a   b a t c h   o n c e   1 0 0   c a n d i d a t e s   c h o o s e   t h i s   d a t e .  
                                 < / d i v > ` ;  
                 }  
  
                 a w a i t   l o a d P r e f e r e n c e s ( a s s e s s m e n t I d ) ;  
  
         }   c a t c h   ( e r r )   {  
                 b t n . d i s a b l e d   =   f a l s e ;  
                 b t n . t e x t C o n t e n t   =   " C h o o s e   T h i s   S l o t " ;  
                 i f   ( n o t i c e C o n t a i n e r )   {  
                         n o t i c e C o n t a i n e r . i n n e r H T M L   =   `  
                                 < d i v   c l a s s = " n o t i c e   n o t i c e - e r r o r "   s t y l e = " b a c k g r o u n d : # f d f 2 f 2 ;   b o r d e r : 1 p x   s o l i d   # f 8 c d c d ;   c o l o r : # b 9 1 c 1 c ;   p a d d i n g : 1 4 p x   1 8 p x ;   b o r d e r - r a d i u s : 8 p x ;   m a r g i n - b o t t o m : 1 8 p x ; " >  
                                         � � R  $ { e s c a p e H t m l ( e r r . m e s s a g e ) }  
                                 < / d i v > ` ;  
                 }  
         }  
 }  
 