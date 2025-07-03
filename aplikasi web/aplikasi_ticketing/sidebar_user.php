<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion position-relative" id="accordionSidebar">

  <!-- Brand -->
  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index_user.php">
    <div class="sidebar-brand-icon rotate-n-15"></div>
    <div class="sidebar-brand-text mx-3">
      <br><br><br>
      <img src="img/logo2.png" alt="Logo" width="200">
    </div>
  </a>

  <hr class="sidebar-divider my-0">

  <!-- Dashboard -->
  <li class="nav-item active">
    <a class="nav-link" href="index_User.php">
      <br><br><br>
      <i class="fas fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <!-- Tiket -->
  <div class="sidebar-heading">Tiket</div>
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#menuTiket" aria-expanded="true" aria-controls="menuTiket">
      <i class="fas fa-ticket-alt"></i>
      <span>Order Tiket</span>
    </a>
    <div id="menuTiket" class="collapse">
      <div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item" href="order_tiket.php"><i class="fas fa-paper-plane mr-2 text-primary"></i>Order Sekarang</a>
        <a class="collapse-item" href="akses_khanza.php"><i class="fas fa-key mr-2 text-success"></i>Akses Khanza</a>
        <a class="collapse-item" href="spo_it_user.php"><i class="fas fa-file-alt mr-2 text-danger"></i>SPO IT</a>
      </div>
    </div>
  </li>

  <hr class="sidebar-divider">

  <!-- Pengaturan -->
  <div class="sidebar-heading">Pengaturan</div>
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#menuPengaturan" aria-expanded="true" aria-controls="menuPengaturan">
      <i class="fas fa-cogs"></i>
      <span>Setting</span>
    </a>
    <div id="menuPengaturan" class="collapse">
      <div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item" href="profil_user.php"><i class="fas fa-user text-info mr-2"></i>Profil</a>
        <a class="collapse-item" href="ubah_password.php"><i class="fas fa-key text-warning mr-2"></i>Ubah Password</a>
      </div>
    </div>
  </li>

  <hr class="sidebar-divider d-none d-md-block">

  <!-- Sidebar Toggler -->
  <div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
  </div>

  <!-- Jam Digital (pinned to bottom) -->
  <div class="sidebar-clock text-white text-center py-3">
    <div><i class="fas fa-clock mr-1"></i><span id="digitalClock" style="font-weight:bold; font-size:15px;">--:--:--</span></div>
    <div><i class="fas fa-calendar-day mr-1"></i><span id="calendarDate" style="font-weight:bold; font-size:14px;">-- --- ----</span></div>
  </div>

</ul>

<!-- CSS untuk posisi tetap jam -->
<style>
.sidebar-clock {
  position: absolute;
  bottom: 0;
  width: 100%;
  background-color: rgba(0,0,0,0.1); /* Tambahan opsional */
}
</style>

<!-- Script Jam Digital -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  function updateClock() {
    const now = new Date();
    const waktu = now.toLocaleTimeString('id-ID', { hour12: false });
    const tanggal = now.toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });

    const clock = document.getElementById('digitalClock');
    const date = document.getElementById('calendarDate');

    if (clock && date) {
      clock.textContent = waktu;
      date.textContent = tanggal;
    }
  }

  updateClock();
  setInterval(updateClock, 1000);
});
</script>
