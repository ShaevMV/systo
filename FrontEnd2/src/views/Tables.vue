<template>
  <div class="py-4 container-fluid">
    <div class="row">
      <div class="col-12">
        <projects-table />
      </div>
    </div>
  </div>
</template>

<script>
import ProjectsTable from "./components/ProjectsTable";

export default {
  name: "tables",
  components: {
    ProjectsTable,
  },
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/getOrderListForUser');
    next();
  },
};
</script>
