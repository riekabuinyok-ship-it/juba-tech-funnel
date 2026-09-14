<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'How It Works';
include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hiw-hero">
  <span class="hero-badge">Simple 4-Step Process</span>
  <h1>How It Works</h1>
  <p>Download your study guides in minutes. Pay with MoMo. Get instant access after confirmation.</p>
</section>

<!-- STEPS -->
<section class="hiw-steps">

  <div class="hiw-step">
    <div class="hiw-step-number">1</div>
    <div class="hiw-step-icon"><?= svg_icon('edit') ?></div>
    <h3>Fill Your Details</h3>
    <p>Enter your name, email, location, class, and level of study. It takes less than a minute.</p>
    <ul class="hiw-step-list">
      <li>Create your account password</li>
      <li>We use this to save your purchases</li>
      <li>No spam, no hidden fees</li>
    </ul>
  </div>

  <div class="hiw-step-arrow">→</div>

  <div class="hiw-step">
    <div class="hiw-step-number">2</div>
    <div class="hiw-step-icon"><?= svg_icon('open-book') ?></div>
    <h3>Choose Your Guides</h3>
    <p>Browse subjects for Primary (P1–P8) and Secondary (S1–S4). Pick a single book or save with a full bundle.</p>
    <ul class="hiw-step-list">
      <li>7,000 SSP per single guide</li>
      <li>21,000 SSP per full bundle</li>
      <li>Save 7,000 SSP on every bundle</li>
    </ul>
  </div>

  <div class="hiw-step-arrow">→</div>

  <div class="hiw-step">
    <div class="hiw-step-number">3</div>
    <div class="hiw-step-icon"><?= svg_icon('phone') ?></div>
    <h3>Pay with MoMo</h3>
    <p>Send the exact amount to our MoMo number, then enter the Transaction ID from your SMS confirmation.</p>
    <ul class="hiw-step-list">
      <li>Send to <strong><?= clean(setting('momo_account_number')) ?></strong></li>
      <li>Name: <strong><?= clean(setting('momo_account_name')) ?></strong></li>
      <li>Each Transaction ID works once</li>
    </ul>
  </div>

  <div class="hiw-step-arrow">→</div>

  <div class="hiw-step">
    <div class="hiw-step-number">4</div>
    <div class="hiw-step-icon"><?= svg_icon('download') ?></div>
    <h3>Download Instantly</h3>
    <p>Once our admin confirms your payment, your download links unlock automatically. Log in anytime to re-download.</p>
    <ul class="hiw-step-list">
      <li>Confirmation usually under 1 hour</li>
      <li>Files stay in your account forever</li>
      <li>Works on phone, tablet, or PC</li>
    </ul>
  </div>

</section>

<!-- WHAT YOU NEED -->
<section class="hiw-requirements">
  <h2>What You Need</h2>
  <div class="hiw-req-grid">
    <div class="hiw-req-card">
      <div class="hiw-req-icon"><?= svg_icon('phone') ?></div>
      <h4>A Phone with MoMo</h4>
      <p>Any phone with MTN or Zain MoMo can pay.</p>
    </div>
    <div class="hiw-req-card">
      <div class="hiw-req-icon"><?= svg_icon('envelope') ?></div>
      <h4>A Valid Email</h4>
      <p>We use your email to save your purchases and send updates.</p>
    </div>
    <div class="hiw-req-card">
      <div class="hiw-req-icon"><?= svg_icon('discount') ?></div>
      <h4>7,000 SSP Minimum</h4>
      <p>Enough to cover one single guide. Bundles start at 21,000 SSP.</p>
    </div>
    <div class="hiw-req-card">
      <div class="hiw-req-icon"><?= svg_icon('wifi') ?></div>
      <h4>Internet Connection</h4>
      <p>Only needed to browse and download. Files work offline.</p>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="hiw-faq">
  <h2>Frequently Asked Questions</h2>

  <details class="faq-item">
    <summary>How long does payment confirmation take?</summary>
    <p>Usually under 1 hour during business hours. If you paid at night, confirmation happens the next morning. If it takes longer, contact us at <?= clean(setting('contact_email')) ?>.</p>
  </details>

  <details class="faq-item">
    <summary>What if I send the wrong amount?</summary>
    <p>Contact us immediately with your Transaction ID. We can adjust the order or refund the difference. Sending the exact amount speeds things up.</p>
  </details>

  <details class="faq-item">
    <summary>Can I use the same MoMo Transaction ID twice?</summary>
    <p>No. Each Transaction ID can only be used once. The system detects duplicates automatically and will reject the second attempt.</p>
  </details>

  <details class="faq-item">
    <summary>What format are the study guides in?</summary>
    <p>Most guides are PDF files. They work on any phone, tablet, or computer and can be printed if you prefer paper.</p>
  </details>

  <details class="faq-item">
    <summary>Can I share my download link with a friend?</summary>
    <p>Download links are protected by your account. Your friend would need to log in as you, which is not secure. Recommend us to them instead — they can buy their own guide.</p>
  </details>

  <details class="faq-item">
    <summary>What if I lose my password?</summary>
    <p>Contact us at <?= clean(setting('contact_email')) ?> with your email and we will reset it for you.</p>
  </details>

  <details class="faq-item">
    <summary>Are the guides aligned to the South Sudan curriculum?</summary>
    <p>Yes. Every guide is written to match the national syllabus for its class level (P1–P8 or S1–S4).</p>
  </details>

  <details class="faq-item">
    <summary>Do you offer refunds?</summary>
    <p>Because the guides are digital and downloaded instantly, refunds are not available once the file has been accessed. If you have a problem, contact us and we will help.</p>
  </details>
</section>

<!-- CTA -->
<section class="hiw-cta">
  <h2>Ready to Start?</h2>
  <p>Pick your level and get your study guides today.</p>
  <div class="hiw-cta-actions">
    <a href="lead-capture.php?level=secondary" class="btn btn-large">Secondary Guides</a>
    <a href="lead-capture.php?level=primary" class="btn btn-large btn-outline">Primary Guides</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>