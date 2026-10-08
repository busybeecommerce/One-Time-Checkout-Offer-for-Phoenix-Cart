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
require 'includes/template_top.php';
?>
<div class="checkout-offer-admin">
  <h1 class="h3 mb-3"><?= HEADING_TITLE ?></h1>
  <p class="text-body-secondary"><?= CHECKOUT_OFFER_ADMIN_HELP ?></p>
  <?php if (!$ready) { ?>
    <?= checkout_offer_admin_form('install') ?>
      <button class="btn btn-primary"><?= CHECKOUT_OFFER_ADMIN_INSTALL ?></button>
    </form>
  <?php } else { ?>
    <div class="row g-4">
      <div class="col-lg-4">
        <section class="card mb-4"><div class="card-body">
          <h2 class="h5"><?= CHECKOUT_OFFER_ADMIN_SETUP ?></h2>
          <?= checkout_offer_admin_form('settings') ?>
            <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="enabled" <?= checkout_offer_enabled() ? 'checked' : '' ?>> <?= CHECKOUT_OFFER_ADMIN_ENABLED ?></label>
            <label class="form-label" for="accent"><?= CHECKOUT_OFFER_ADMIN_ACCENT ?></label>
            <input class="form-control form-control-color mb-3" type="color" id="accent" name="accent" value="<?= checkout_offer_escape(defined('CHECKOUT_OFFER_ACCENT') ? CHECKOUT_OFFER_ACCENT : '#6f42c1') ?>">
            <button class="btn btn-primary btn-sm"><?= CHECKOUT_OFFER_ADMIN_SAVE ?></button>
          </form>
        </div></section>
        <section class="card"><div class="card-body">
          <h2 class="h5"><?= CHECKOUT_OFFER_ADMIN_TIERS ?></h2>
          <a class="btn btn-outline-primary btn-sm mb-3" href="<?= $Admin->link('checkout_offer.php') ?>"><?= CHECKOUT_OFFER_ADMIN_NEW ?></a>
          <ul class="list-group list-group-flush">
            <?php $allTiers = $db->query('SELECT * FROM checkout_offer_tiers ORDER BY priority DESC, id'); while ($row = $allTiers->fetch_assoc()) { ?>
              <li class="list-group-item px-0"><a href="<?= $Admin->link('checkout_offer.php', ['tier_id' => $row['id']]) ?>"><?= checkout_offer_escape($row['title']) ?></a>
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
            <?php foreach (['title' => CHECKOUT_OFFER_ADMIN_TITLE, 'minimum' => CHECKOUT_OFFER_ADMIN_MINIMUM, 'maximum' => CHECKOUT_OFFER_ADMIN_MAXIMUM, 'priority' => CHECKOUT_OFFER_ADMIN_PRIORITY] as $key => $label) { ?>
              <label class="form-label small mt-2" for="<?= $key ?>"><?= $label ?></label>
              <input class="form-control form-control-sm" id="<?= $key ?>" name="<?= $key ?>" value="<?= checkout_offer_escape((string)$editTier[$key]) ?>" <?= 'title' === $key ? 'maxlength="120" required' : 'inputmode="decimal"' ?>>
            <?php } ?>
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
              <div class="border rounded p-3 mb-3">
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
        <?php } ?>
      </div>
    </div>
    <details class="mt-4 border rounded p-3"><summary><?= CHECKOUT_OFFER_ADMIN_UNINSTALL ?></summary>
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
