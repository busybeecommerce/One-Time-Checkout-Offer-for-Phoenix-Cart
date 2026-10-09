<?php
declare(strict_types=1);

require 'includes/application_top.php';
require_once DIR_FS_CATALOG . 'includes/functions/checkout_offer.php';
require_once __DIR__ . '/includes/functions/checkout_offer_admin.php';
if (!defined('CHECKOUT_OFFER_ADMIN_HELP')) {
    require 'includes/languages/english/checkout_offer.php';
}

if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '')) {
    try {
        $action = $_POST['action'] ?? '';
        if (!is_string($action) || !is_string($_POST['formid'] ?? null) || !Form::validate_action_is($action)) {
            throw new InvalidArgumentException('The request could not be verified.');
        }
        checkout_offer_admin_action($action, $_POST);
        $messageStack->add_session(CHECKOUT_OFFER_ADMIN_SAVED, 'success');
    } catch (InvalidArgumentException $exception) {
        $messageStack->add_session($exception->getMessage(), 'error');
    } catch (Throwable $exception) {
        error_log('Checkout Offer admin: ' . $exception->getMessage());
        $messageStack->add_session(CHECKOUT_OFFER_ADMIN_ERROR, 'error');
    }
    Href::redirect($Admin->link('checkout_offer.php', ['tier_id' => (int)($_POST['tier_id'] ?? 0)]));
}

$ready = checkout_offer_schema_ready();
$tierId = (int)($_GET['tier_id'] ?? 0);
$tier = $ready && $tierId > 0 ? checkout_offer_admin_tier($tierId) : null;
$editTier = $tier ?? ['id' => 0, 'title' => '', 'minimum' => '0', 'maximum' => '', 'priority' => '0', 'enabled' => 1];
$appearance = checkout_offer_appearance();
require 'includes/template_top.php';
?>
<link rel="stylesheet" href="<?= checkout_offer_escape((string)$Admin->catalog('ext/checkout_offer/checkout_offer_admin.css?v=1.5.0')) ?>">
<div class="checkout-offer-admin">
  <header class="co-admin-header">
    <div class="co-admin-heading">
      <p class="co-admin-eyebrow">BusyBee Commerce</p>
      <h1><?= HEADING_TITLE ?></h1>
      <span class="co-admin-status"><?= !$ready ? CHECKOUT_OFFER_ADMIN_SETUP_REQUIRED : (checkout_offer_enabled() ? CHECKOUT_OFFER_ADMIN_ACTIVE : CHECKOUT_OFFER_ADMIN_INACTIVE) ?></span>
    </div>
    <a class="co-admin-logo" href="https://busybeecommerce.co.uk" target="_blank" rel="noopener noreferrer"><img src="<?= checkout_offer_escape((string)$Admin->catalog('images/checkout_offer/busybee-logo.png')) ?>" alt="BusyBee Commerce" width="1500" height="600"></a>
  </header>
  <p class="co-admin-intro"><?= CHECKOUT_OFFER_ADMIN_HELP ?></p>
  <?php if (!$ready) { ?>
    <section class="card"><div class="card-body">
    <?= checkout_offer_admin_form('install') ?>
      <button class="btn btn-primary"><?= CHECKOUT_OFFER_ADMIN_INSTALL ?></button>
    </form>
    </div></section>
  <?php } else { ?>
    <div class="row g-4">
      <div class="col-lg-4">
        <section class="card mb-4"><div class="card-body">
          <h2 class="h5"><?= CHECKOUT_OFFER_ADMIN_SETUP ?></h2>
          <?= checkout_offer_admin_form('settings') ?>
            <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="enabled" <?= checkout_offer_enabled() ? 'checked' : '' ?>> <?= CHECKOUT_OFFER_ADMIN_ENABLED ?></label>
            <label class="form-label" for="display-mode"><?= CHECKOUT_OFFER_ADMIN_DISPLAY_MODE ?></label>
            <select class="form-select form-select-sm mb-3" id="display-mode" name="display_mode">
              <option value="inline" <?= 'inline' === checkout_offer_display_mode() ? 'selected' : '' ?>><?= CHECKOUT_OFFER_ADMIN_INLINE ?></option>
              <option value="modal" <?= 'modal' === checkout_offer_display_mode() ? 'selected' : '' ?>><?= CHECKOUT_OFFER_ADMIN_MODAL ?></option>
            </select>
            <p class="small text-body-secondary"><?= CHECKOUT_OFFER_ADMIN_MODAL_HELP ?></p>
            <details class="co-admin-appearance mb-3"><summary class="fw-semibold"><?= CHECKOUT_OFFER_ADMIN_APPEARANCE ?></summary>
              <fieldset class="co-template-picker">
                <legend><?= CHECKOUT_OFFER_ADMIN_STYLE_TEMPLATE ?></legend>
                <p class="small text-body-secondary"><?= CHECKOUT_OFFER_ADMIN_TEMPLATE_HELP ?></p>
                <div class="co-template-choices">
                  <?php foreach (checkout_offer_templates() as $name => $palette) { ?>
                    <label class="co-template-choice">
                      <input type="radio" name="appearance[template]" value="<?= $name ?>" <?= $appearance['template'] === $name ? 'checked' : '' ?>>
                      <span class="co-template-name"><?= constant('CHECKOUT_OFFER_ADMIN_CHOICE_' . strtoupper($name)) ?></span>
                      <span class="co-template-preview" aria-hidden="true" style="--co-preview-colour:<?= $palette['banner_background'] ?? '#e9edf1' ?>;--co-preview-button:<?= $palette['button_background'] ?? '#0d6efd' ?>">
                        <span class="co-template-preview-header"></span><span class="co-template-preview-product"></span><span class="co-template-preview-button"></span>
                      </span>
                    </label>
                  <?php } ?>
                </div>
              </fieldset>
              <div class="co-appearance-fields mt-1">
                <?php foreach (checkout_offer_appearance_fields() as $key => $field) { ?>
                  <?php if ('template' === $key) { continue; } ?>
                  <div class="co-appearance-field">
                    <label class="form-label small" for="style-<?= $key ?>"><?= constant('CHECKOUT_OFFER_ADMIN_STYLE_' . strtoupper($key)) ?></label>
                    <?php if ('select' === $field['type']) { ?>
                      <select class="form-select form-select-sm" id="style-<?= $key ?>" name="appearance[<?= $key ?>]">
                        <?php foreach ($field['choices'] as $choice) { ?>
                          <option value="<?= $choice ?>" <?= $appearance[$key] === $choice ? 'selected' : '' ?>><?= constant('CHECKOUT_OFFER_ADMIN_CHOICE_' . strtoupper($choice)) ?></option>
                        <?php } ?>
                      </select>
                    <?php } else { ?>
                      <input class="form-control form-control-sm<?= 'color' === $field['type'] ? ' form-control-color' : '' ?>" id="style-<?= $key ?>" type="<?= $field['type'] ?>" name="appearance[<?= $key ?>]" value="<?= checkout_offer_escape((string)$appearance[$key]) ?>" <?= 'number' === $field['type'] ? 'min="' . $field['min'] . '" max="' . $field['max'] . '" step="1"' : '' ?>>
                    <?php } ?>
                  </div>
                <?php } ?>
              </div>
            </details>
            <button class="btn btn-primary btn-sm"><?= CHECKOUT_OFFER_ADMIN_SAVE ?></button>
          </form>
        </div></section>
        <section class="card"><div class="card-body">
          <h2 class="h5"><?= CHECKOUT_OFFER_ADMIN_TIERS ?></h2>
          <a class="btn btn-outline-primary btn-sm mb-3" href="<?= $Admin->link('checkout_offer.php') ?>"><?= CHECKOUT_OFFER_ADMIN_NEW ?></a>
          <ul class="list-group list-group-flush">
            <?php $allTiers = $db->query('SELECT * FROM checkout_offer_tiers ORDER BY priority DESC, id'); while ($row = $allTiers->fetch_assoc()) { ?>
              <li class="list-group-item co-admin-tier<?= (int)$row['id'] === $tierId ? ' co-admin-tier-selected' : '' ?>"><a href="<?= $Admin->link('checkout_offer.php', ['tier_id' => $row['id']]) ?>" <?= (int)$row['id'] === $tierId ? 'aria-current="page"' : '' ?>><?= checkout_offer_escape($row['title']) ?></a>
                <div class="small text-body-secondary"><?= checkout_offer_escape((string)$row['minimum']) ?> – <?= null === $row['maximum'] ? '∞' : checkout_offer_escape((string)$row['maximum']) ?> · <?= (int)$row['priority'] ?> <?= empty($row['enabled']) ? '(disabled)' : '' ?></div>
              </li>
            <?php } ?>
          </ul>
        </div></section>
      </div>
      <div class="col-lg-8">
        <section class="card mb-4"><div class="card-body">
          <h2 class="h5"><?= $tier ? CHECKOUT_OFFER_ADMIN_EDIT : CHECKOUT_OFFER_ADMIN_NEW ?></h2>
          <?= checkout_offer_admin_form('save_tier', ['tier_id' => $editTier['id']]) ?>
            <div class="row g-3">
            <?php foreach (['title' => CHECKOUT_OFFER_ADMIN_TITLE, 'minimum' => CHECKOUT_OFFER_ADMIN_MINIMUM, 'maximum' => CHECKOUT_OFFER_ADMIN_MAXIMUM, 'priority' => CHECKOUT_OFFER_ADMIN_PRIORITY] as $key => $label) { ?>
              <div class="<?= 'title' === $key ? 'col-12' : 'col-md-6' ?>">
              <label class="form-label small" for="<?= $key ?>"><?= $label ?></label>
              <input class="form-control form-control-sm" id="<?= $key ?>" name="<?= $key ?>" value="<?= checkout_offer_escape((string)$editTier[$key]) ?>" <?= 'title' === $key ? 'maxlength="120" required' : 'inputmode="decimal"' ?>>
              </div>
            <?php } ?>
            </div>
            <label class="form-check my-3"><input class="form-check-input" type="checkbox" name="enabled" <?= $editTier['enabled'] ? 'checked' : '' ?>> <?= CHECKOUT_OFFER_ADMIN_ENABLED ?></label>
            <button class="btn btn-primary btn-sm"><?= CHECKOUT_OFFER_ADMIN_SAVE ?></button>
          </form>
          <?php if ($tier) { ?>
            <?= checkout_offer_admin_form('delete_tier', ['tier_id' => $tierId]) ?>
              <button class="btn btn-outline-danger btn-sm mt-2"><?= CHECKOUT_OFFER_ADMIN_DELETE ?></button>
            </form>
          <?php } ?>
        </div></section>
        <?php if ($tier) { ?>
          <section class="card mb-4"><div class="card-body">
            <h2 class="h5"><?= CHECKOUT_OFFER_ADMIN_PRODUCTS ?></h2>
            <?php $rules = checkout_offer_items($tierId); $rules[] = ['id' => 0, 'products_id' => '', 'mode' => 'percent', 'value' => '10']; foreach ($rules as $rule) { ?>
              <div class="co-admin-product-rule">
                <h3><?= $rule['id'] ? CHECKOUT_OFFER_ADMIN_PRODUCT_RULE : CHECKOUT_OFFER_ADMIN_ADD_PRODUCT ?></h3>
                <?= checkout_offer_admin_form('save_product', ['tier_id' => $tierId, 'item_id' => $rule['id']]) ?>
                  <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small"><?= CHECKOUT_OFFER_ADMIN_PRODUCT_ID ?><input class="form-control form-control-sm" type="number" min="1" required name="products_id" value="<?= checkout_offer_escape((string)$rule['products_id']) ?>"></label></div>
                    <div class="col-md-5"><label class="form-label small"><?= CHECKOUT_OFFER_ADMIN_MODE ?><select class="form-select form-select-sm" name="mode"><option value="fixed" <?= 'fixed' === $rule['mode'] ? 'selected' : '' ?>><?= CHECKOUT_OFFER_ADMIN_FIXED ?></option><option value="percent" <?= 'percent' === $rule['mode'] ? 'selected' : '' ?>><?= CHECKOUT_OFFER_ADMIN_PERCENT ?></option></select></label></div>
                    <div class="col-md-4"><label class="form-label small"><?= CHECKOUT_OFFER_ADMIN_VALUE ?><input class="form-control form-control-sm" type="number" min="0" step="0.0001" required name="value" value="<?= checkout_offer_escape((string)$rule['value']) ?>"></label></div>
                  </div>
                  <button class="btn btn-primary btn-sm"><?= CHECKOUT_OFFER_ADMIN_SAVE ?></button>
                </form>
                <?php if ($rule['id']) { ?>
                  <?= checkout_offer_admin_form('delete_product', ['tier_id' => $tierId, 'item_id' => $rule['id']]) ?>
                    <button class="btn btn-outline-danger btn-sm mt-2"><?= CHECKOUT_OFFER_ADMIN_DELETE ?></button>
                  </form>
                <?php } ?>
              </div>
            <?php } ?>
          </div></section>
        <?php } else { ?>
          <section class="card co-admin-empty"><div class="card-body">
            <h2 class="h5"><?= CHECKOUT_OFFER_ADMIN_PRODUCTS ?></h2>
            <p><?= CHECKOUT_OFFER_ADMIN_PRODUCTS_HELP ?></p>
          </div></section>
        <?php } ?>
      </div>
    </div>
    <details class="co-admin-uninstall mt-4"><summary><?= CHECKOUT_OFFER_ADMIN_UNINSTALL ?></summary>
      <p class="small mt-3"><?= CHECKOUT_OFFER_ADMIN_UNINSTALL_HELP ?></p>
      <?= checkout_offer_admin_form('uninstall') ?>
        <label class="form-check my-3"><input class="form-check-input" type="checkbox" name="confirm_remove" value="yes" required> <?= CHECKOUT_OFFER_ADMIN_CONFIRM_REMOVE ?></label>
        <button class="btn btn-danger btn-sm"><?= CHECKOUT_OFFER_ADMIN_UNINSTALL ?></button>
      </form>
    </details>
  <?php } ?>
</div>
<?php
require 'includes/template_bottom.php';
require 'includes/application_bottom.php';
