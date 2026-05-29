</main>
<footer class="site-footer">
  <div class="container">
    <div class="site-footer__top">
      <span class="site-footer__brand"><?= e($siteNameGlobal) ?></span>
      <nav class="site-footer__nav">
        <a href="/">Accueil</a>
        <a href="/contact.php">Nous contacter</a>
      </nav>
      <div class="site-footer__user-links">
        <?php if ($user): ?>
          <a href="/admin/index.php">Administration</a>
          <a href="/logout.php">Déconnexion</a>
        <?php else: ?>
          <a href="/login.php">Accès rédacteurs</a>
        <?php endif; ?>
      </div>
    </div>
    <p class="site-footer__copyright">&copy; <?= date('Y') ?> <?= e($siteNameGlobal) ?>. Tous droits réservés.</p>
  </div>
</footer>
</body>
</html>

