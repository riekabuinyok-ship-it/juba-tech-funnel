<?php
if (setting('whatsapp_enabled', '0') !== '1') return;
$number = setting('whatsapp_number', '');
$message = setting('whatsapp_message', 'Hi, I need help with my order on SSD Study Guides.');
if ($number === '') return;
$waLink = 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . urlencode($message);
?>
<div class="whatsapp-widget" id="whatsappWidget">
  <div class="wa-panel" id="waPanel" aria-hidden="true">
    <div class="wa-panel-head">
      <div class="wa-agent"><div class="wa-agent-avatar">💬</div><div><strong>Need help?</strong><small><?= clean(setting('whatsapp_hours', 'Mon-Sat, 8am - 8pm')) ?></small></div></div>
      <button type="button" class="wa-panel-close" id="waClose" aria-label="Close">✕</button>
    </div>
    <div class="wa-panel-body">
      <div class="wa-intro"><?= clean($message) ?></div>
      <a href="<?= $waLink ?>" target="_blank" rel="noopener" class="wa-action wa-action-whatsapp"><span class="wa-action-icon">💬</span><span class="wa-action-body"><strong>Chat on WhatsApp</strong><small><?= clean($number) ?></small></span><span class="wa-action-arrow">→</span></a>
      <a href="tel:<?= clean(setting('support_phone', $number)) ?>" class="wa-action"><span class="wa-action-icon"><?= svg_icon('phone') ?></span><span class="wa-action-body"><strong>Call us</strong><small><?= clean(setting('support_phone', $number)) ?></small></span><span class="wa-action-arrow">→</span></a>
      <a href="mailto:<?= clean(setting('support_email', 'info@ssdstudy.com')) ?>" class="wa-action"><span class="wa-action-icon"><?= svg_icon('envelope') ?></span><span class="wa-action-body"><strong>Email us</strong><small><?= clean(setting('support_email', 'info@ssdstudy.com')) ?></small></span><span class="wa-action-arrow">→</span></a>
    </div>
  </div>
  <button type="button" class="wa-trigger" id="waTrigger" aria-label="Open WhatsApp chat" aria-expanded="false"><span class="wa-trigger-icon">💬</span><span class="wa-trigger-badge" id="waBadge" hidden>1</span></button>
</div>
