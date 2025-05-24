<!-- ───── NAVBAR CREDICASHFÁCIL ───── -->
<header class="navbar">
  <!-- Marca -->
  <a href="dashboard.php" class="brand">
   <!-- <span class="brand__icon">💰</span>Credi<span class="brand__accent">Cash</span>Fácil -->
   <img src="assets/logo.png" style="width:10%;">
  </a>

  <!-- Botón hamburguesa -->
  <button class="menu-toggle" aria-label="Menú">
    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
  </button>

  <!-- Enlaces -->
  <nav class="nav-links">
    <a href="dashboard.php">Inicio</a>
    <a href="loans.php">Préstamos</a>
    <a href="loan_new.php">Nuevo Préstamo</a>

    <?php if($_SESSION['role']==='admin'): ?>
      <a href="../reports/morosos.php" target="_blank">PDF Morosos</a>
      <a href="../reports/loans_csv.php">Exportar CSV</a>
      <a href="users.php">Usuarios</a>
    <?php endif; ?>

    <a href="../auth/logout.php" class="logout">
      Salir (<?= htmlspecialchars($_SESSION['user']) ?>)
    </a>
  </nav>
</header>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const btn  = document.querySelector('.menu-toggle');
  const nav  = document.querySelector('.nav-links');

  btn.addEventListener('click', ()=>{
    nav.classList.toggle('open');
    btn.classList.toggle('active');
  });

  /* Opcional: cierra al hacer clic en un enlace */
  nav.querySelectorAll('a').forEach(link=>{
    link.addEventListener('click',()=>{
      nav.classList.remove('open');
      btn.classList.remove('active');
    });
  });
});
</script>
