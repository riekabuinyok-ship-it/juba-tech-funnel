</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <h4><?= clean(setting('site_name', SITE_NAME)) ?></h4>
        <p style="color:#bbb;font-size:0.9rem;line-height:1.7;">
          Study guides for South Sudan students. Primary (P1–P8) and Secondary (S1–S4).
        </p>
      </div>
      <div>
        <h4>Quick Links</h4>
        <a href="<?= SITE_URL ?>/all-books.php">All Books</a>
        <a href="<?= SITE_URL ?>/lead-capture.php?level=secondary">Secondary Guides</a>
        <a href="<?= SITE_URL ?>/lead-capture.php?level=primary">Primary Guides</a>
        <a href="<?= SITE_URL ?>/how-it-works.php">How It Works</a>
      </div>
      <div>
        <h4>Contact</h4>
        <a href="tel:<?= clean(setting('momo_account_number')) ?>"><?= svg_icon('phone', 'svg-icon icon-sm') ?> <?= clean(setting('momo_account_number')) ?></a>
        <a href="mailto:<?= clean(setting('contact_email')) ?>"><?= svg_icon('envelope', 'svg-icon icon-sm') ?> <?= clean(setting('contact_email')) ?></a>
      </div>
      <div>
        <h4>Follow Us</h4>
        <a href="#">Facebook</a>
        <a href="#">Twitter</a>
        <a href="#">YouTube</a>
      </div>
    </div>
    <div class="copyright">
      © <?= date('Y') ?> <?= clean(setting('site_name', SITE_NAME)) ?>. All rights reserved.
    </div>
  </div>
</footer>

<?php include __DIR__ . '/exit-modal.php'; ?>
<?php include __DIR__ . '/review-prompt.php'; ?>

<?= render_flashes() ?>

<script>
  // Fire any server-side flash messages as toasts
  document.addEventListener('DOMContentLoaded', function() {
    if (window.__flashMessages && Array.isArray(window.__flashMessages)) {
      window.__flashMessages.forEach(function(f, i) {
        setTimeout(function() {
          if (window.showToast) {
            window.showToast(f.message, {
              type: f.type || 'info',
              title: f.title || '',
              duration: 4500
            });
          }
        }, i * 250);
      });
    }
  });
</script>

<?php include __DIR__ . '/exit-intent.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js?v=14"></script>
</body>
</html>
