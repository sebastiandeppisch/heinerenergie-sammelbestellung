<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
</script>
<template>
  <div class="content-block">
    <footer class="footer">
      <a href="https://github.com/sebastiandeppisch/heinerenergie-sammelbestellung" ><img src="/img/github.svg" alt="Github" style="height:1em;"></a>
      &nbsp;&nbsp;
      <Link href="/impress">Impressum</Link>
       &nbsp;&nbsp;
      <Link href="/datapolicy">Datenschutzerklärung</Link>
    </footer>
  </div>
</template>

<style lang="scss">
@import "../themes/generated/variables.base.scss";

.footer {
  display: block;
  color: rgba($base-text-color, alpha($base-text-color) * 0.7);
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  padding-top: 20px;
  padding-bottom: 24px;
  display: flex;
  justify-content: center;
}
</style>
