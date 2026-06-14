<template>
  <div class="admin-dashboard">
    <div class="admin-dashboard-header">
      <h1>Админка</h1>
      <p class="admin-dashboard-subtitle">Разделы управления</p>
    </div>

    <div class="admin-cards">
      <router-link to="/admin/qr-orders" class="admin-card">
        <i class="pi pi-qrcode admin-card-icon"></i>
        <div class="admin-card-title">QR-заказы</div>
        <div class="admin-card-desc">Заказы от витрины qr.spaceofjoy.ru (просмотр)</div>
      </router-link>
    </div>
  </div>
</template>

<script setup>
// Лендинг админки. Пока один раздел — qr-заказы; следующие плитки добавятся по мере ревампа.
</script>

<style scoped>
.admin-dashboard {
  padding: 1.5rem;
  max-width: 1280px;
  margin: 0 auto;
}

.admin-dashboard-header h1 {
  margin: 0;
  font-size: 1.6rem;
}

.admin-dashboard-subtitle {
  margin: 0.25rem 0 1.5rem;
  color: var(--p-text-muted-color, #6b7280);
}

.admin-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
}

.admin-card {
  display: block;
  padding: 1.5rem;
  border: 1px solid var(--p-content-border-color, #e5e7eb);
  border-radius: 12px;
  text-decoration: none;
  color: inherit;
  transition: box-shadow 0.15s ease, transform 0.15s ease;
}

.admin-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.admin-card-icon {
  font-size: 1.75rem;
  color: var(--p-primary-color, #6366f1);
}

.admin-card-title {
  margin-top: 0.75rem;
  font-weight: 600;
  font-size: 1.1rem;
}

.admin-card-desc {
  margin-top: 0.25rem;
  font-size: 0.85rem;
  color: var(--p-text-muted-color, #6b7280);
}
</style>
