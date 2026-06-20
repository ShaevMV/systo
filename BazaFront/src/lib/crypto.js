// ─────────────────────────────────────────────────────────────────────────────
//  Криптопримитивы офлайн-кэша (Ф5, PR-6) — нативный WebCrypto, без зависимостей.
//
//  KDF: PBKDF2-SHA256 (нативный SubtleCrypto). Argon2id из спеки идеален, но требует
//  npm-библиотеки — отмечено как hardening-follow-up. Шифр снимка: AES-GCM-256.
// ─────────────────────────────────────────────────────────────────────────────
const encoder = new TextEncoder();
const decoder = new TextDecoder();

const PBKDF2_ITERATIONS = 150000;

function toB64(buf) {
    return btoa(String.fromCharCode(...new Uint8Array(buf)));
}

function fromB64(s) {
    return Uint8Array.from(atob(s), (c) => c.charCodeAt(0));
}

export function randomBytes(n) {
    const a = new Uint8Array(n);
    crypto.getRandomValues(a);
    return a;
}

/** Новая соль (base64) для деривации ключа из PIN. */
export function newSalt() {
    return toB64(randomBytes(16));
}

/** Вывести AES-GCM ключ из PIN + соли (PBKDF2). */
export async function deriveKey(pin, saltB64) {
    const baseKey = await crypto.subtle.importKey('raw', encoder.encode(String(pin)), 'PBKDF2', false, ['deriveKey']);
    return crypto.subtle.deriveKey(
        { name: 'PBKDF2', salt: fromB64(saltB64), iterations: PBKDF2_ITERATIONS, hash: 'SHA-256' },
        baseKey,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt']
    );
}

/** Зашифровать строку → { iv, ct } (base64). */
export async function encryptString(key, plaintext) {
    const iv = randomBytes(12);
    const ct = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, encoder.encode(plaintext ?? ''));
    return { iv: toB64(iv), ct: toB64(ct) };
}

/** Расшифровать { iv, ct } → строка (или null). */
export async function decryptString(key, blob) {
    if (!blob || !blob.iv || !blob.ct) {
        return null;
    }
    const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: fromB64(blob.iv) }, key, fromB64(blob.ct));
    return decoder.decode(pt);
}
