// ─────────────────────────────────────────────────────────────────────────────
//  Офлайн-PIN и ключ шифрования кэша (Ф5, PR-6, реш. C8/C10/C11).
//
//  PIN (4-6 цифр) при онлайн-входе → деривация AES-GCM ключа (PBKDF2), которым
//  шифруется снимок в IndexedDB. Ключ держим ТОЛЬКО в памяти с TTL = смена (10ч).
//  Wipe (стереть кэш + ключ): 5 неверных PIN / закрытие смены / выход.
//  Самообслуживание (C10): PIN задаёт сам сотрудник; отзыв — TTL + revoke-at-reconnect (C11).
// ─────────────────────────────────────────────────────────────────────────────
import { getMeta, setMeta } from '@/db/index';
import { deriveKey, encryptString, decryptString, newSalt } from '@/lib/crypto';
import { clearSnapshot } from '@/db/snapshot';
import { clearBlacklist } from '@/db/blacklist';

const TTL_MS = 10 * 60 * 60 * 1000; // 10 часов — длительность смены (реш. C9)
const MAX_FAILS = 5;
const VERIFIER = 'baza-pin-ok';

// Ключ — только в памяти (никогда не в IndexedDB/localStorage).
let key = null;
let unlockedUntil = 0;

export async function hasPin() {
    return Boolean(await getMeta('pin_salt')) && Boolean(await getMeta('pin_verifier'));
}

/** Задать PIN (самообслуживание при онлайн-входе). Разблокирует сессию. */
export async function setPin(pin) {
    const salt = newSalt();
    const k = await deriveKey(pin, salt);
    const verifier = await encryptString(k, VERIFIER);
    await setMeta('pin_salt', salt);
    await setMeta('pin_verifier', verifier);
    await setMeta('pin_fails', 0);
    key = k;
    unlockedUntil = Date.now() + TTL_MS;
    return true;
}

/**
 * Разблокировать по PIN. 5 неверных подряд → wipe кэша.
 * @returns {Promise<{ok: boolean, reason?: string, left?: number}>}
 */
export async function unlock(pin) {
    const salt = await getMeta('pin_salt');
    const verifier = await getMeta('pin_verifier');
    if (!salt || !verifier) {
        return { ok: false, reason: 'no-pin' };
    }

    let ok = false;
    try {
        const k = await deriveKey(pin, salt);
        ok = (await decryptString(k, verifier)) === VERIFIER;
        if (ok) {
            key = k;
            unlockedUntil = Date.now() + TTL_MS;
            await setMeta('pin_fails', 0);
            return { ok: true };
        }
    } catch {
        ok = false;
    }

    const fails = (await getMeta('pin_fails', 0)) + 1;
    await setMeta('pin_fails', fails);
    if (fails >= MAX_FAILS) {
        await wipe();
        return { ok: false, reason: 'wiped' };
    }
    return { ok: false, reason: 'bad', left: MAX_FAILS - fails };
}

export function isUnlocked() {
    return Boolean(key) && Date.now() < unlockedUntil;
}

/** Ключ шифрования снимка (или null, если заблокировано). */
export function getKey() {
    return isUnlocked() ? key : null;
}

/** Заблокировать (ключ из памяти). Кэш остаётся зашифрованным на диске. */
export function lock() {
    key = null;
    unlockedUntil = 0;
}

/** Полностью стереть кэш и ключ (5 неверных / закрытие смены / выход). */
export async function wipe() {
    lock();
    await setMeta('pin_salt', null);
    await setMeta('pin_verifier', null);
    await setMeta('pin_fails', 0);
    await setMeta('snapshot_since', null);
    await setMeta('blacklist_since', null);
    await clearSnapshot();
    await clearBlacklist();
}
