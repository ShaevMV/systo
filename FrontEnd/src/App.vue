<template>
  <nav id="navbar-example2" class="navbar navbar-light bg-light">
    <ul class="nav nav-pills">
      <li class="nav-item">
        <router-link
            class="nav-link"
            active-class="active"
            to="/">О фестевале
        </router-link>
      </li>
      <li class="nav-item">
        <router-link
            class="nav-link"
            active-class="active"
            to="/order">Регистрация оргвзноса
        </router-link>
      </li>
    </ul>
  </nav>
  <router-view/>
</template>
