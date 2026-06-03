</main>
<footer class="site-footer">
  <div class="container">
    <div class="site-footer__top">
      <span class="site-footer__brand"><?= e($siteNameGlobal) ?></span>
      <nav class="site-footer__nav">
        <a href="/">Home</a>
        <a href="/contact.php">Contact Us</a>
      </nav>
      <div class="site-footer__user-links">
        <?php if ($user): ?>
          <a href="/admin/index.php">Administration</a>
          <a href="/logout.php">Logout</a>
        <?php else: ?>
          <a href="/login.php">Writers Access</a>
        <?php endif; ?>
      </div>
    </div>
    <p class="site-footer__copyright">&copy; <?= date('Y') ?> <?= e($siteNameGlobal) ?>. All rights reserved.</p>
  </div>
</footer>
</body>
</html>
