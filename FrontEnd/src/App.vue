<template>
    <header id="header" class="header fixed-top d-flex align-items-center">
            <a href="/" class="logo d-flex">Система регистрации оргвзносов на систо</a>
    </header>

    <aside id="sidebar" class="sidebar">
        <MenuView/>
    </aside>
    <main id="main" class="main">
        <router-view/>
    </main>
</template>

<script>
    import MenuView from "@/views/MenuView";

    export default {
        components: {MenuView}
    }
</script>
