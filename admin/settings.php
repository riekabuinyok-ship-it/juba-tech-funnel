<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    redirect('settings.php');
}

$settings = $pdo->query("SELECT * FROM settings ORDER BY setting_key")->fetchAll();
$pageTitle = 'Settings';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-actions-bar">
  <p class="muted">Site-wide configuration. These values are used across the storefront.</p>
</div>

<section class="admin-section">
  <div class="section-head">
    <h2>Store Settings</h2>
    <span class="count-pill"><?= count($settings) ?> settings</span>
  </div>
  <form method="post">
    <?php foreach ($settings as $s): ?>
      <div class="form-group">
        <label><?php
          $labelMap = [
              'site_name'               => 'Site Name',
              'momo_account_name'       => 'MoMo Account Name',
              'momo_account_number'     => 'MoMo Account Number',
              'contact_email'           => 'Contact Email',
              'single_price'            => 'Single Book Price (SSP)',
              'bundle_price'            => 'Bundle Price (SSP)',
              'trust_students_count'    => 'Trusted Students Count (0 to hide)',
              'trust_show_curriculum'   => 'Show "Aligned with National Curriculum" (0 or 1)',
              'trust_show_moneyback'    => 'Show Money-Back Guarantee (0 or 1)',
              'trust_show_ministry'     => 'Show Ministry Verification (0 or 1 — only if true)',
              'trust_show_downloads'    => 'Show Real Download Count (0 or 1)',
              'trust_moneyback_days'    => 'Money-Back Days',
              'trust_moneyback_text'    => 'Money-Back Guarantee Text',
               'welcome_bar_enabled' => 'Show Welcome Bar (0 or 1)',
               'welcome_bar_text'    => 'Welcome Bar Message',
               'welcome_bar_code'    => 'Discount Code (optional)',
               'welcome_bar_link'    => 'Welcome Bar Button Link',
               'welcome_bar_button'  => 'Welcome Bar Button Text',
               'exit_intent_coupon'         => 'Exit-Intent Coupon Code',
               'exit_intent_discount_label' => 'Exit-Intent Badge (e.g. 10% OFF)',
               'exit_intent_heading'        => 'Exit-Intent Heading',
               'exit_intent_message'        => 'Exit-Intent Message',
          ];
          $label = $labelMap[$s['setting_key']] ?? ucwords(str_replace('_', ' ', $s['setting_key']));
        ?><?= clean($label) ?>
        </label>
        <?php if (in_array($s['setting_key'], ['trust_show_curriculum','trust_show_moneyback','trust_show_ministry','trust_show_downloads','whatsapp_enabled','welcome_bar_enabled'])): ?>
          <select name="settings[<?= clean($s['setting_key']) ?>]">
            <option value="1" <?= $s['setting_value'] === '1' ? 'selected' : '' ?>>Yes</option>
            <option value="0" <?= $s['setting_value'] === '0' ? 'selected' : '' ?>>No</option>
          </select>
        <?php else: ?>
          <input name="settings[<?= clean($s['setting_key']) ?>]" value="<?= clean($s['setting_value']) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button class="btn">Save Settings</button>
  </form>
</section>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>