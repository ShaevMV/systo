// ─────────────────────────────────────────────────────────────────────────────
//  Звук + вибро для уведомлений КПП. Сотрудник смотрит на гостя, а не на экран —
//  звук/вибро «дёргают» взгляд, чтобы ошибку/успех НЕ пропустить (боль владельца).
//  Параллель светофору ScanView (там свой feedback по цвету вердикта).
// ─────────────────────────────────────────────────────────────────────────────
const FREQ = { error: 220, critical: 220, warn: 880, success: 880, info: null };
const VIBRO = { error: [120, 60, 120], critical: [200, 80, 200], warn: 60, success: 60, info: 0 };

/** @param {'success'|'info'|'warn'|'error'|'critical'} severity */
export function playFeedback(severity) {
    try {
        const v = VIBRO[severity];
        if (navigator.vibrate && v) navigator.vibrate(v);
    } catch {
        /* вибро не поддержано */
    }
    try {
        const freq = FREQ[severity];
        if (!freq) return;
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        const ctx = new Ctx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.frequency.value = freq;
        gain.gain.value = 0.05;
        osc.connect(gain).connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + (severity === 'error' || severity === 'critical' ? 0.22 : 0.13));
        osc.onended = () => ctx.close();
    } catch {
        /* звук не поддержан */
    }
}
