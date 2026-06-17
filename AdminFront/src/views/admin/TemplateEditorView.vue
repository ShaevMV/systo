<script setup>
import { ref, computed, nextTick, watch, onMounted } from 'vue';
import { useStore } from 'vuex';
import { useRoute, useRouter } from 'vue-router';

import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

const store = useStore();
const route = useRoute();
const router = useRouter();

const id = computed(() => route.params.id);
const isNew = computed(() => id.value === 'new');

// Форма
const title = ref('');
const kind = ref('email');
const engine = ref('html');
const slug = ref('');
const active = ref(false);
const editing = ref(''); // рабочий исходник (черновик)

const kindOptions = [
    { label: 'Письмо', value: 'email' },
    { label: 'PDF-билет', value: 'pdf' }
];
// MJML пока не реализован (нет компиляции) → только HTML, чтобы не сохранить битый движок.
const engineOptions = [{ label: 'HTML', value: 'html' }];

const variables = computed(() => store.getters['appTemplate/getVariables']);
const versions = computed(() => store.getters['appTemplate/getVersions']);
const history = computed(() => store.getters['appTemplate/getHistory']);
const isLoading = computed(() => store.getters['appTemplate/getIsLoading']);

// Превью
const codeRef = ref(null);
const previewMode = ref('none'); // none | email | pdf
const previewHtml = ref('');
const previewPdfUrl = ref('');
const previewError = ref('');
const statusMsg = ref('');
const showVersions = ref(false);
const showHistory = ref(false);
const codePlaceholder = 'HTML с плейсхолдерами в стиле {{ name }} — вставляйте переменные кликом из палитры справа';

function flash(msg) {
    statusMsg.value = msg;
    setTimeout(() => (statusMsg.value = ''), 2500);
}

/** Вставить плейсхолдер/сниппет в позицию курсора (важно — не печатать руками). */
function insertAtCursor(text) {
    const el = codeRef.value;
    if (!el) {
        editing.value += text;
        return;
    }
    const start = el.selectionStart ?? editing.value.length;
    const end = el.selectionEnd ?? editing.value.length;
    editing.value = editing.value.slice(0, start) + text + editing.value.slice(end);
    nextTick(() => {
        el.focus();
        const pos = start + text.length;
        el.selectionStart = el.selectionEnd = pos;
    });
}

// Загрузка картинки (фон PDF-билета / иллюстрации): файл → public storage → URL вставляется
// в позицию курсора (админ ставит его в <img src="...">).
const fileRef = ref(null);
const uploading = ref(false);

function triggerUpload() {
    fileRef.value?.click();
}

async function onUploadImage(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    uploading.value = true;
    try {
        const r = await store.dispatch('appTemplate/uploadImage', { file });
        if (r?.url) {
            insertAtCursor(r.url);
            flash('Картинка загружена — URL вставлен в позицию курсора');
        } else {
            flash('Не удалось загрузить картинку');
        }
    } catch (err) {
        flash(err.response?.data?.message || 'Ошибка загрузки картинки');
    } finally {
        uploading.value = false;
        e.target.value = ''; // сброс — чтобы можно было выбрать тот же файл повторно
    }
}

async function loadVariables() {
    await store.dispatch('appTemplate/loadVariables', { kind: kind.value, slug: slug.value });
}

async function load() {
    if (isNew.value) {
        title.value = '';
        kind.value = 'email';
        engine.value = 'html';
        slug.value = '';
        active.value = false;
        editing.value = '';
        await loadVariables();
        return;
    }

    const item = await store.dispatch('appTemplate/loadItem', { id: id.value });
    title.value = item.title ?? '';
    kind.value = item.kind ?? 'email';
    engine.value = item.engine ?? 'html';
    slug.value = item.slug ?? '';
    active.value = !!item.active;
    editing.value = item.draft_body || item.body || '';
    await loadVariables();
}

async function doPreview() {
    previewError.value = '';
    try {
        const res = await store.dispatch('appTemplate/preview', {
            kind: kind.value,
            slug: slug.value || kind.value,
            body: editing.value
        });
        if (res.type === 'pdf') {
            previewPdfUrl.value = res.url;
            previewMode.value = 'pdf';
        } else {
            previewHtml.value = res.html;
            previewMode.value = 'email';
        }
    } catch (e) {
        let msg = 'Ошибка рендера шаблона';
        const data = e.response?.data;
        if (data instanceof Blob) {
            try {
                msg = JSON.parse(await data.text()).message || msg;
            } catch (_) {
                /* пусто */
            }
        } else if (data?.message) {
            msg = data.message;
        }
        previewError.value = msg;
        previewMode.value = 'none';
    }
}

async function onSaveDraft() {
    await store.dispatch('appTemplate/saveDraft', { id: id.value, draftBody: editing.value });
    flash('Черновик сохранён');
}

async function onPublish() {
    const r = await store.dispatch('appTemplate/publish', { id: id.value, body: editing.value });
    if (r.item) {
        active.value = !!r.item.active;
        editing.value = r.item.body || '';
    }
    flash('Опубликовано');
}

async function onActivate() {
    const r = await store.dispatch('appTemplate/activate', { id: id.value, active: !active.value });
    if (r.item) active.value = !!r.item.active;
    flash(active.value ? 'Шаблон активирован' : 'Шаблон деактивирован');
}

async function onCreate() {
    const r = await store.dispatch('appTemplate/create', {
        data: { slug: slug.value, kind: kind.value, engine: engine.value, title: title.value || slug.value, body: editing.value, active: false }
    });
    if (r.item?.id) {
        flash('Шаблон создан');
        router.replace('/admin/templates/' + r.item.id);
    }
}

async function toggleVersions() {
    showVersions.value = !showVersions.value;
    if (showVersions.value && !isNew.value) {
        await store.dispatch('appTemplate/loadVersions', { id: id.value });
    }
}

// Журнал изменений (кто/что/когда — из domain_history, aggregate_type=template).
const HISTORY_LABELS = {
    template_created: 'Создан',
    template_edited: 'Изменён',
    template_activated: 'Переключена активность',
    template_published: 'Опубликован',
    template_rolled_back: 'Откат к версии'
};
function eventLabel(name) {
    return HISTORY_LABELS[name] || name;
}
function historyDetail(row) {
    const p = row.payload || {};
    if (row.event_name === 'template_edited' && Array.isArray(p.changed)) return 'поля: ' + p.changed.join(', ');
    if (row.event_name === 'template_activated') return p.active ? 'включён' : 'выключен';
    if (row.event_name === 'template_published' && p.comment) return p.comment;
    return '';
}
function actorLabel(row) {
    return row.actor_name || row.actor_email || (row.actor_type === 'artisan' ? 'система (CLI)' : '—');
}
async function toggleHistory() {
    showHistory.value = !showHistory.value;
    if (showHistory.value && !isNew.value) {
        await store.dispatch('appTemplate/loadHistory', { id: id.value });
    }
}

async function onRollback(versionId) {
    if (!window.confirm('Откатить шаблон к этой версии?')) return;
    const r = await store.dispatch('appTemplate/rollback', { id: id.value, versionId });
    if (r.item) editing.value = r.item.body || '';
    await store.dispatch('appTemplate/loadVersions', { id: id.value });
    flash('Откат выполнен');
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// Смена типа в режиме создания → обновить палитру и движок.
watch(kind, () => {
    if (kind.value === 'pdf') engine.value = 'html';
    loadVariables();
});

onMounted(load);
</script>

<template>
    <div class="ed-view">
        <div class="ed-header">
            <div class="ed-titleblock">
                <Button icon="pi pi-arrow-left" text rounded aria-label="Назад" @click="router.push('/admin/templates')" />
                <h1>{{ isNew ? 'Новый шаблон' : title || slug }}</h1>
                <Tag v-if="!isNew" :value="active ? 'активен' : 'черновик'" :severity="active ? 'success' : 'secondary'" />
            </div>
            <span v-if="statusMsg" class="ed-status">{{ statusMsg }}</span>
        </div>

        <!-- Метаданные -->
        <Card class="ed-meta-card">
            <template #content>
                <div class="ed-meta">
                    <div class="ed-field">
                        <label>Название</label>
                        <InputText v-model="title" placeholder="Напр. Письмо об оплате" />
                    </div>
                    <div class="ed-field">
                        <label>Тип</label>
                        <Select v-model="kind" :options="kindOptions" option-label="label" option-value="value" :disabled="!isNew" />
                    </div>
                    <div class="ed-field">
                        <label>Движок</label>
                        <Select v-model="engine" :options="engineOptions" option-label="label" option-value="value" />
                    </div>
                    <div class="ed-field">
                        <label>Slug (привязка к событию)</label>
                        <InputText v-model="slug" :disabled="!isNew" placeholder="напр. orderToPaid / pdf" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Редактор: исходник | палитра+превью -->
        <div class="ed-grid">
            <Card class="ed-source-card">
                <template #title>Исходник ({{ kind === 'pdf' ? 'HTML' : engine.toUpperCase() }} + Mustache)</template>
                <template #content>
                    <textarea ref="codeRef" v-model="editing" class="ed-code" spellcheck="false" :placeholder="codePlaceholder"></textarea>
                    <div class="ed-actions">
                        <Button label="Загрузить картинку" icon="pi pi-image" severity="secondary" outlined :loading="uploading" @click="triggerUpload" />
                        <input ref="fileRef" type="file" accept="image/*" style="display: none" @change="onUploadImage" />
                        <template v-if="isNew">
                            <Button label="Создать" icon="pi pi-check" :disabled="!slug || !editing" @click="onCreate" />
                        </template>
                        <template v-else>
                            <Button label="Сохранить черновик" icon="pi pi-save" severity="secondary" outlined @click="onSaveDraft" />
                            <Button label="Опубликовать" icon="pi pi-upload" @click="onPublish" />
                            <Button :label="active ? 'Деактивировать' : 'Активировать'" :icon="active ? 'pi pi-pause' : 'pi pi-play'" :severity="active ? 'warn' : 'success'" outlined @click="onActivate" />
                            <Button label="Версии" icon="pi pi-history" text @click="toggleVersions" />
                            <Button label="Журнал" icon="pi pi-list" text @click="toggleHistory" />
                        </template>
                    </div>

                    <!-- Версии -->
                    <div v-if="showVersions && !isNew" class="ed-versions">
                        <h4>Версии</h4>
                        <div v-if="!versions.length" class="ed-muted">Пока нет опубликованных версий</div>
                        <div v-for="v in versions" :key="v.id" class="ed-version-row">
                            <div>
                                <span class="ed-version-date">{{ formatDate(v.created_at) }}</span>
                                <span v-if="v.comment" class="ed-muted"> · {{ v.comment }}</span>
                            </div>
                            <Button label="Откатить" icon="pi pi-replay" size="small" text @click="onRollback(v.id)" />
                        </div>
                    </div>

                    <!-- Журнал изменений (кто/что/когда — аудит действий админа) -->
                    <div v-if="showHistory && !isNew" class="ed-versions">
                        <h4>Журнал изменений</h4>
                        <div v-if="!history.length" class="ed-muted">Пока нет записей</div>
                        <div v-for="(h, i) in history" :key="i" class="ed-version-row">
                            <div>
                                <span class="ed-version-date">{{ formatDate(h.occurred_at) }}</span>
                                <span> · {{ eventLabel(h.event_name) }}</span>
                                <span v-if="historyDetail(h)" class="ed-muted"> · {{ historyDetail(h) }}</span>
                            </div>
                            <span class="ed-muted">{{ actorLabel(h) }}</span>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="ed-side-card">
                <template #content>
                    <div class="ed-side">
                        <!-- Палитра -->
                        <div class="ed-palette">
                            <h4>Палитра (вставка кликом)</h4>
                            <div v-for="group in variables" :key="group.group" class="ed-pal-group">
                                <div class="ed-pal-label">{{ group.group }}</div>
                                <div class="ed-pal-items">
                                    <button v-for="(it, i) in group.items" :key="i" class="ed-chip" type="button" :title="it.insert" @click="insertAtCursor(it.insert)">
                                        {{ it.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Превью -->
                        <div class="ed-preview-block">
                            <div class="ed-preview-head">
                                <h4>Предпросмотр</h4>
                                <Button label="Обновить превью" icon="pi pi-eye" size="small" :loading="isLoading" @click="doPreview" />
                            </div>
                            <div v-if="previewError" class="ed-preview-error">{{ previewError }}</div>
                            <div class="ed-preview-box">
                                <iframe v-if="previewMode === 'email'" :srcdoc="previewHtml" class="ed-iframe" sandbox title="preview-email"></iframe>
                                <iframe v-else-if="previewMode === 'pdf'" :src="previewPdfUrl" class="ed-iframe" title="preview-pdf"></iframe>
                                <div v-else class="ed-muted ed-preview-empty">Нажмите «Обновить превью» — рендер на тестовых данных</div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.ed-view {
    padding: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
    min-width: 0;
}

.ed-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.ed-titleblock {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.ed-titleblock h1 {
    margin: 0;
    font-size: 1.45rem;
}

.ed-status {
    color: var(--p-primary-color, #10b981);
    font-weight: 600;
}

.ed-meta-card {
    margin-bottom: 1rem;
}

.ed-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.ed-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.ed-field label {
    font-size: 0.8rem;
    font-weight: 600;
}

.ed-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 1rem;
    align-items: start;
}

.ed-code {
    width: 100%;
    min-height: 360px;
    font-family: 'JetBrains Mono', 'Fira Code', Consolas, monospace;
    font-size: 0.85rem;
    line-height: 1.5;
    padding: 0.75rem;
    border: 1px solid var(--p-content-border-color, #e5e7eb);
    border-radius: 8px;
    resize: vertical;
    background: var(--p-content-background, #fff);
    color: var(--p-text-color, #111827);
}

.ed-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.ed-versions {
    margin-top: 1rem;
    border-top: 1px solid var(--p-content-border-color, #e5e7eb);
    padding-top: 0.75rem;
}

.ed-versions h4 {
    margin: 0 0 0.5rem;
}

.ed-version-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.3rem 0;
}

.ed-version-date {
    font-size: 0.85rem;
}

.ed-side {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.ed-palette h4,
.ed-preview-head h4 {
    margin: 0 0 0.5rem;
}

.ed-pal-group {
    margin-bottom: 0.6rem;
}

.ed-pal-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--p-text-muted-color, #6b7280);
    margin-bottom: 0.3rem;
}

.ed-pal-items {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.ed-chip {
    border: 1px solid var(--p-content-border-color, #e5e7eb);
    background: var(--p-content-hover-background, #f8fafc);
    border-radius: 999px;
    padding: 0.2rem 0.7rem;
    font-size: 0.8rem;
    cursor: pointer;
    transition: background 0.15s;
}

.ed-chip:hover {
    background: var(--p-primary-100, #d1fae5);
}

.ed-preview-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.ed-preview-box {
    border: 1px solid var(--p-content-border-color, #e5e7eb);
    border-radius: 8px;
    overflow: hidden;
    height: 420px;
    background: #fff;
}

.ed-iframe {
    width: 100%;
    height: 100%;
    border: 0;
}

.ed-preview-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    padding: 1rem;
}

.ed-preview-error {
    color: var(--p-red-500, #ef4444);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.ed-muted {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.85rem;
}

@media (max-width: 991px) {
    .ed-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767px) {
    .ed-view {
        padding: 0.75rem 0;
    }
}
</style>
