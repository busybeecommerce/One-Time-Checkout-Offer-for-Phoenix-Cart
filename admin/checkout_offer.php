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
    $returnTab = $_POST['admin_tab'] ?? ('settings' === $action ? 'setup' : ('uninstall' === $action ? 'maintenance' : 'offers'));
    if (!is_string($returnTab) || !in_array($returnTab, ['setup', 'templates', 'appearance', 'text', 'offers', 'maintenance', 'manual'], true)) {
        $returnTab = 'setup';
    }
    Href::redirect($Admin->link('checkout_offer.php', ['tier_id' => (int)($_POST['tier_id'] ?? 0), 'tab' => $returnTab]));
}

$ready = checkout_offer_schema_ready();
$tierId = (int)($_GET['tier_id'] ?? 0);
$tier = $ready && $tierId > 0 ? checkout_offer_admin_tier($tierId) : null;
$editTier = $tier ?? ['id' => 0, 'title' => '', 'minimum' => '0', 'maximum' => '', 'priority' => '0', 'enabled' => 1];
$appearance = checkout_offer_appearance();
$activeTab = $_GET['tab'] ?? ($tierId > 0 ? 'offers' : 'setup');
if (!is_string($activeTab) || !in_array($activeTab, ['setup', 'templates', 'appearance', 'text', 'offers', 'maintenance', 'manual'], true)) {
    $activeTab = 'setup';
}
require 'includes/template_top.php';
?>
<link rel="stylesheet" href="<?= checkout_offer_escape((string)$Admin->catalog('ext/checkout_offer/checkout_offer_admin.css?v=1.7.26')) ?>">
<script src="<?= checkout_offer_escape((string)$Admin->catalog('ext/checkout_offer/checkout_offer_admin.js?v=1.7.26')) ?>" defer></script>
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
    <div class="co-admin-workspace" data-initial-tab="<?= $activeTab ?>">
      <nav class="co-admin-tabs" role="tablist" aria-label="<?= CHECKOUT_OFFER_ADMIN_NAVIGATION ?>" hidden>
        <?php foreach (['setup' => CHECKOUT_OFFER_ADMIN_SETUP, 'templates' => CHECKOUT_OFFER_ADMIN_STYLE_TEMPLATE, 'appearance' => CHECKOUT_OFFER_ADMIN_APPEARANCE, 'text' => CHECKOUT_OFFER_ADMIN_CUSTOM_TEXT, 'offers' => CHECKOUT_OFFER_ADMIN_OFFERS, 'maintenance' => CHECKOUT_OFFER_ADMIN_MAINTENANCE, 'manual' => CHECKOUT_OFFER_ADMIN_MANUAL] as $key => $label) { ?>
          <button type="button" id="co-tab-<?= $key ?>" role="tab" aria-controls="co-panel-<?= $key ?>" data-co-tab="<?= $key ?>"><?= $label ?></button>
        <?php } ?>
      </nav>
      <?= checkout_offer_admin_form('settings') ?>
        <section id="co-panel-setup" class="co-admin-panel" data-co-panel="setup"><h2><?= CHECKOUT_OFFER_ADMIN_SETUP ?></h2>
          <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="enabled" <?= checkout_offer_enabled() ? 'checked' : '' ?>> <?= CHECKOUT_OFFER_ADMIN_ENABLED ?></label>
          <label class="form-label" for="display-mode"><?= CHECKOUT_OFFER_ADMIN_DISPLAY_MODE ?></label>
          <select class="form-select form-select-sm mb-3" id="display-mode" name="display_mode">
            <option value="inline" <?= 'inline' === checkout_offer_display_mode() ? 'selected' : '' ?>><?= CHECKOUT_OFFER_ADMIN_INLINE ?></option>
            <option value="modal" <?= 'modal' === checkout_offer_display_mode() ? 'selected' : '' ?>><?= CHECKOUT_OFFER_ADMIN_MODAL ?></option>
          </select>
          <p class="small text-body-secondary" id="co-display-help" aria-live="polite" data-inline-help="<?= checkout_offer_escape(CHECKOUT_OFFER_ADMIN_INLINE_HELP) ?>" data-modal-help="<?= checkout_offer_escape(CHECKOUT_OFFER_ADMIN_MODAL_HELP) ?>"><?= 'modal' === checkout_offer_display_mode() ? CHECKOUT_OFFER_ADMIN_MODAL_HELP : CHECKOUT_OFFER_ADMIN_INLINE_HELP ?></p>
        </section>
        <section id="co-panel-templates" class="co-admin-panel" data-co-panel="templates"><h2><?= CHECKOUT_OFFER_ADMIN_STYLE_TEMPLATE ?></h2>
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
        </section>
        <section id="co-panel-appearance" class="co-admin-panel" data-co-panel="appearance"><h2><?= CHECKOUT_OFFER_ADMIN_APPEARANCE ?></h2>
          <p class="small text-body-secondary"><?= CHECKOUT_OFFER_ADMIN_TEXT_STYLE_HELP ?></p>
          <div class="co-appearance-groups">
            <?php foreach ([
                CHECKOUT_OFFER_ADMIN_GROUP_COLOURS => ['background', 'text', 'card_background', 'border', 'button_background', 'button_text', 'heading_background', 'image_background'],
                CHECKOUT_OFFER_ADMIN_GROUP_LAYOUT => ['product_layout', 'columns', 'tablet_columns', 'mobile_columns', 'modal_width', 'image_fit', 'content_alignment', 'products_alignment', 'price_alignment', 'button_alignment'],
                CHECKOUT_OFFER_ADMIN_GROUP_SPACING => ['radius', 'padding', 'gap', 'border_width', 'image_height', 'font_size'],
                CHECKOUT_OFFER_ADMIN_GROUP_BUTTONS => ['button_style', 'shadow', 'button_size', 'button_width', 'card_shadow', 'button_shadow', 'button_hover_background', 'button_hover_text', 'button_radius'],
                CHECKOUT_OFFER_ADMIN_GROUP_TYPOGRAPHY => ['heading_size', 'description_size', 'heading_weight', 'description_weight', 'heading_colour', 'description_colour', 'heading_style', 'description_style'],
            ] as $group => $keys) { ?>
            <fieldset class="co-admin-field-group"><legend><?= $group ?></legend><div class="co-appearance-fields">
            <?php foreach ($keys as $key) { $field = checkout_offer_appearance_fields()[$key]; ?>
              <div class="co-appearance-field">
                <label class="form-label small" for="style-<?= $key ?>"><?= constant('CHECKOUT_OFFER_ADMIN_STYLE_' . strtoupper($key)) ?></label>
                <?php if ('select' === $field['type']) { ?>
                  <select class="form-select form-select-sm" id="style-<?= $key ?>" name="appearance[<?= $key ?>]">
                    <?php foreach ($field['choices'] as $choice) { ?>
                      <option value="<?= $choice ?>" <?= $appearance[$key] === $choice ? 'selected' : '' ?>><?= constant('CHECKOUT_OFFER_ADMIN_CHOICE_' . strtoupper($choice)) ?></option>
                    <?php } ?>
                  </select>
                <?php } elseif (in_array($field['type'], ['color', 'optional_color'], true)) { $colour = (string)$appearance[$key]; ?>
                  <div class="co-colour-control" data-co-colour>
                    <input class="form-control form-control-color" type="color" value="<?= checkout_offer_escape($colour ?: '#ffffff') ?>" aria-label="<?= checkout_offer_escape(constant('CHECKOUT_OFFER_ADMIN_STYLE_' . strtoupper($key))) ?>: colour picker" data-co-colour-picker>
                    <div class="co-colour-codes">
                      <label class="co-colour-code-label" for="style-<?= $key ?>">HEX</label>
                      <input class="form-control form-control-sm" id="style-<?= $key ?>" type="text" name="appearance[<?= $key ?>]" value="<?= checkout_offer_escape($colour) ?>" placeholder="#rrggbb" maxlength="7" pattern="#[0-9a-fA-F]{6}" <?= 'color' === $field['type'] ? 'required' : '' ?> data-co-colour-hex>
                      <label class="co-colour-code-label" for="rgb-<?= $key ?>">RGB</label>
                      <input class="form-control form-control-sm" id="rgb-<?= $key ?>" type="text" readonly data-co-colour-rgb>
                    </div>
                  </div>
                <?php } else { ?>
                  <input class="form-control form-control-sm<?= 'color' === $field['type'] ? ' form-control-color' : '' ?>" id="style-<?= $key ?>" type="<?= 'optional_color' === $field['type'] ? 'text' : $field['type'] ?>" name="appearance[<?= $key ?>]" value="<?= checkout_offer_escape((string)$appearance[$key]) ?>" <?= 'optional_color' === $field['type'] ? 'placeholder="#rrggbb" maxlength="7"' : '' ?> <?= 'number' === $field['type'] ? 'min="' . $field['min'] . '" max="' . $field['max'] . '" step="1"' : '' ?>>
                <?php } ?>
              </div>
            <?php } ?>
            </div></fieldset>
            <?php } ?>
          </div>
        </section>

        <section id="co-panel-text" class="co-admin-panel" data-co-panel="text"><h2><?= CHECKOUT_OFFER_ADMIN_CUSTOM_TEXT ?></h2>
          <p class="small text-body-secondary"><?= CHECKOUT_OFFER_ADMIN_TEXT_HELP ?></p>
          <div class="co-admin-text-fields">
          <?php foreach (['heading_text', 'badge_text', 'add_text', 'dismiss_text', 'saving_text', 'description_text', 'note_text'] as $key) { $field = checkout_offer_appearance_fields()[$key]; ?>
            <div class="co-admin-text-field<?= $field['max'] > 200 ? ' co-admin-text-field-wide' : '' ?>">
            <label class="form-label" for="style-<?= $key ?>"><?= constant('CHECKOUT_OFFER_ADMIN_STYLE_' . strtoupper($key)) ?></label>
            <textarea class="form-control form-control-sm mb-3" id="style-<?= $key ?>" name="appearance[<?= $key ?>]" rows="<?= $field['max'] > 200 ? 3 : 1 ?>" maxlength="<?= $field['max'] ?>"><?= checkout_offer_escape($appearance[$key]) ?></textarea>
            </div>
          <?php } ?>
          </div>
        </section>
        <button class="btn btn-primary btn-sm"><?= CHECKOUT_OFFER_ADMIN_SAVE ?></button>
      </form>
      <section id="co-panel-offers" class="co-admin-panel" data-co-panel="offers"><div class="row g-4"><div class="col-lg-4">
        <section class="card"><div class="card-body">
          <h2 class="h5"><?= CHECKOUT_OFFER_ADMIN_TIERS ?></h2>
          <a class="btn btn-outline-primary btn-sm mb-3" href="<?= $Admin->link('checkout_offer.php', ['tab' => 'offers']) ?>"><?= CHECKOUT_OFFER_ADMIN_NEW ?></a>
          <ul class="list-group co-admin-tier-list">
            <?php $tierCurrencies = new currencies(); $baseCurrency = $tierCurrencies->currencies[DEFAULT_CURRENCY]; $currencySymbol = trim($baseCurrency['symbol_left'] ?: $baseCurrency['symbol_right']) ?: DEFAULT_CURRENCY; $allTiers = $db->query('SELECT * FROM checkout_offer_tiers ORDER BY priority DESC, id'); while ($row = $allTiers->fetch_assoc()) { ?>
              <li class="list-group-item co-admin-tier<?= (int)$row['id'] === $tierId ? ' co-admin-tier-selected' : '' ?>">
                <div class="co-admin-tier-description"><a href="<?= $Admin->link('checkout_offer.php', ['tier_id' => $row['id']]) ?>" <?= (int)$row['id'] === $tierId ? 'aria-current="page"' : '' ?>><?= checkout_offer_escape($row['title']) ?></a>
                <div class="small text-body-secondary"><?= checkout_offer_escape($tierCurrencies->format((float)$row['minimum'], false, DEFAULT_CURRENCY)) ?> – <?= null === $row['maximum'] ? '∞' : checkout_offer_escape($tierCurrencies->format((float)$row['maximum'], false, DEFAULT_CURRENCY)) ?> · <?= (int)$row['priority'] ?> <?= empty($row['enabled']) ? '(disabled)' : '' ?></div>
                </div>
                <div class="co-admin-tier-icons">
                  <a class="co-admin-tier-icon" href="<?= $Admin->link('checkout_offer.php', ['tier_id' => $row['id']]) ?>" aria-label="<?= checkout_offer_escape(CHECKOUT_OFFER_ADMIN_EDIT . ': ' . $row['title']) ?>" title="<?= CHECKOUT_OFFER_ADMIN_EDIT ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z M14 5l5 5"/></svg></a>
                  <?= checkout_offer_admin_form('delete_tier', ['tier_id' => $row['id']]) ?>
                    <button class="co-admin-tier-icon co-admin-tier-delete" aria-label="<?= checkout_offer_escape(CHECKOUT_OFFER_ADMIN_DELETE . ': ' . $row['title']) ?>" title="<?= CHECKOUT_OFFER_ADMIN_DELETE ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18 M9 6V3h6v3 M5 6l1 15h12l1-15 M10 10v7 M14 10v7"/></svg></button>
                  </form>
                </div>
              </li>
            <?php } ?>
          </ul>
        </div></section>
      </div>
      <div class="col-lg-8">
        <section class="card mb-4"><div class="card-body">
          <h2 class="h5"><?= $tier ? CHECKOUT_OFFER_ADMIN_EDIT : CHECKOUT_OFFER_ADMIN_NEW ?></h2>
          <?= checkout_offer_admin_form('save_tier', ['tier_id' => $editTier['id']], ['id' => 'co-save-tier']) ?>
            <div class="co-admin-tier-fields">
            <?php foreach (['title' => CHECKOUT_OFFER_ADMIN_TITLE, 'minimum' => CHECKOUT_OFFER_ADMIN_MINIMUM, 'maximum' => CHECKOUT_OFFER_ADMIN_MAXIMUM, 'priority' => CHECKOUT_OFFER_ADMIN_PRIORITY] as $key => $label) { ?>
              <div class="<?= 'title' === $key ? 'co-admin-tier-title' : '' ?>">
              <label class="form-label small" for="<?= $key ?>"><?= $label ?><?= in_array($key, ['minimum', 'maximum'], true) ? ' (' . checkout_offer_escape(DEFAULT_CURRENCY) . ')' : '' ?></label>
              <?php if (in_array($key, ['minimum', 'maximum'], true)) { ?>
                <div class="input-group input-group-sm co-admin-money">
                  <span class="input-group-text" aria-hidden="true"><?= checkout_offer_escape($currencySymbol) ?></span>
                  <input class="form-control form-control-sm" type="number" min="0" step="0.01" data-co-money id="<?= $key ?>" name="<?= $key ?>" value="<?= null === $editTier[$key] || '' === $editTier[$key] ? '' : number_format((float)$editTier[$key], 2, '.', '') ?>" <?= 'minimum' === $key ? 'required' : '' ?>>
                </div>
              <?php } else { ?>
                <input class="form-control form-control-sm" id="<?= $key ?>" name="<?= $key ?>" value="<?= checkout_offer_escape((string)$editTier[$key]) ?>" <?= 'title' === $key ? 'maxlength="120" required' : 'inputmode="numeric"' ?>>
              <?php } ?>
              </div>
            <?php } ?>
            </div>
            <p class="small text-body-secondary mt-2"><?= CHECKOUT_OFFER_ADMIN_PRIORITY_HELP ?></p>
            <label class="form-check my-3"><input class="form-check-input" type="checkbox" name="enabled" <?= $editTier['enabled'] ? 'checked' : '' ?>> <?= CHECKOUT_OFFER_ADMIN_ENABLED ?></label>
          </form>
          <div class="co-admin-actions">
            <button class="btn btn-primary btn-sm" form="co-save-tier"><?= CHECKOUT_OFFER_ADMIN_SAVE ?></button>
            <?php if ($tier) { ?>
              <?= checkout_offer_admin_form('delete_tier', ['tier_id' => $tierId]) ?>
                <button class="btn btn-outline-danger btn-sm"><?= CHECKOUT_OFFER_ADMIN_DELETE ?></button>
              </form>
            <?php } ?>
          </div>
        </div></section>
        <?php if ($tier) { ?>
          <section class="card mb-4"><div class="card-body">
            <h2 class="h5"><?= CHECKOUT_OFFER_ADMIN_PRODUCTS ?></h2>
            <?php $catalogue = checkout_offer_admin_catalogue((int)$_SESSION['languages_id']); $rules = checkout_offer_items($tierId); $rules[] = ['id' => 0, 'products_id' => '', 'mode' => 'percent', 'value' => '10']; foreach ($rules as $rule) { ?>
              <div class="co-admin-product-rule">
                <h3><?= $rule['id'] ? CHECKOUT_OFFER_ADMIN_PRODUCT_RULE : CHECKOUT_OFFER_ADMIN_ADD_PRODUCT ?></h3>
                <?= checkout_offer_admin_form('save_product', ['tier_id' => $tierId, 'item_id' => $rule['id']], ['id' => 'co-save-product-' . (int)$rule['id']]) ?>
                  <div class="row g-2">
                    <div class="col-12 co-admin-product-picker">
                      <label class="form-label" for="co-category-<?= (int)$rule['id'] ?>"><?= CHECKOUT_OFFER_ADMIN_CATEGORY ?></label>
                      <select class="form-select form-select-sm" id="co-category-<?= (int)$rule['id'] ?>" data-co-category disabled>
                        <option value=""><?= CHECKOUT_OFFER_ADMIN_SELECT_CATEGORY ?></option>
                        <?php if ($catalogue['has_uncategorised']) { ?><option value="0"><?= CHECKOUT_OFFER_ADMIN_UNCATEGORISED ?></option><?php } ?>
                        <?php foreach ($catalogue['categories'] as $category) { ?>
                          <option value="<?= (int)$category['categories_id'] ?>"><?= checkout_offer_escape($category['categories_name']) ?> (#<?= (int)$category['categories_id'] ?>)</option>
                        <?php } ?>
                      </select>
                      <label class="form-label" for="co-product-<?= (int)$rule['id'] ?>"><?= CHECKOUT_OFFER_ADMIN_PRODUCT ?></label>
                      <select class="form-select form-select-sm" id="co-product-<?= (int)$rule['id'] ?>" name="products_id" required data-co-product>
                        <option value=""><?= CHECKOUT_OFFER_ADMIN_SELECT_PRODUCT ?></option>
                        <?php if ($rule['products_id'] && !isset($catalogue['products'][(int)$rule['products_id']])) { ?>
                          <option value="<?= (int)$rule['products_id'] ?>" selected disabled><?= CHECKOUT_OFFER_ADMIN_UNAVAILABLE_PRODUCT ?> (#<?= (int)$rule['products_id'] ?>)</option>
                        <?php } ?>
                        <?php if ($rule['products_id'] && isset($catalogue['products'][(int)$rule['products_id']])) { ?>
                          <option value="<?= (int)$rule['products_id'] ?>" selected><?= checkout_offer_escape($catalogue['products'][(int)$rule['products_id']]['name']) ?> (#<?= (int)$rule['products_id'] ?>)</option>
                        <?php } ?>
                      </select>
                      <template data-co-products>
                        <?php foreach ($catalogue['products'] as $productId => $product) { ?>
                          <option value="<?= $productId ?>" data-categories="<?= checkout_offer_escape(json_encode($product['categories'] ?: [0], JSON_THROW_ON_ERROR)) ?>"><?= checkout_offer_escape($product['name']) ?> (#<?= $productId ?>)</option>
                        <?php } ?>
                      </template>
                      <noscript><p class="small"><?= CHECKOUT_OFFER_ADMIN_CATEGORY_SCRIPT_HELP ?></p></noscript>
                    </div>
                    <div class="col-md-6"><label class="form-label small"><?= CHECKOUT_OFFER_ADMIN_MODE ?><select class="form-select form-select-sm" name="mode"><option value="fixed" <?= 'fixed' === $rule['mode'] ? 'selected' : '' ?>><?= CHECKOUT_OFFER_ADMIN_FIXED ?></option><option value="percent" <?= 'percent' === $rule['mode'] ? 'selected' : '' ?>><?= CHECKOUT_OFFER_ADMIN_PERCENT ?></option></select></label></div>
                    <div class="col-md-6"><label class="form-label small"><?= CHECKOUT_OFFER_ADMIN_VALUE ?><span class="input-group input-group-sm co-admin-money" data-co-price-field data-currency-symbol="<?= checkout_offer_escape($currencySymbol) ?>"><span class="input-group-text" data-co-price-symbol aria-hidden="true"><?= 'fixed' === $rule['mode'] ? checkout_offer_escape($currencySymbol) : '%' ?></span><input class="form-control form-control-sm" type="number" min="0" step="0.01" required name="value" value="<?= number_format((float)$rule['value'], 2, '.', '') ?>"></span></label></div>
                  </div>
                </form>
                <div class="co-admin-actions mt-2">
                  <button class="btn btn-primary btn-sm" form="co-save-product-<?= (int)$rule['id'] ?>"><?= CHECKOUT_OFFER_ADMIN_SAVE ?></button>
                  <?php if ($rule['id']) { ?>
                    <?= checkout_offer_admin_form('delete_product', ['tier_id' => $tierId, 'item_id' => $rule['id']]) ?>
                      <button class="btn btn-outline-danger btn-sm"><?= CHECKOUT_OFFER_ADMIN_DELETE ?></button>
                    </form>
                  <?php } ?>
                </div>
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
    </section>
    <section id="co-panel-maintenance" class="co-admin-panel" data-co-panel="maintenance"><h2><?= CHECKOUT_OFFER_ADMIN_DANGER_ZONE ?></h2>
    <details class="co-admin-uninstall" open><summary><span class="co-admin-danger-icon" aria-hidden="true">&#9888;</span><?= CHECKOUT_OFFER_ADMIN_REMOVE_ADDON ?></summary>
      <div class="co-admin-danger-body">
      <p class="co-admin-danger-warning"><?= CHECKOUT_OFFER_ADMIN_PERMANENT_REMOVAL ?></p>
      <p class="small"><?= CHECKOUT_OFFER_ADMIN_UNINSTALL_HELP ?></p>
      <?= checkout_offer_admin_form('uninstall') ?>
        <label class="form-check my-3"><input class="form-check-input" type="checkbox" name="confirm_remove" value="yes" required> <?= CHECKOUT_OFFER_ADMIN_CONFIRM_REMOVE ?></label>
        <button class="btn btn-danger btn-sm"><?= CHECKOUT_OFFER_ADMIN_UNINSTALL ?></button>
      </form>
      </div>
    </details>
    </section>
    <section id="co-panel-manual" class="co-admin-panel co-admin-manual" data-co-panel="manual">
      <?php require __DIR__ . '/includes/manuals/checkout_offer.html'; ?>
    </section>
    </div>
  <?php } ?>
</div>
<?php
require 'includes/template_bottom.php';
require 'includes/application_bottom.php';
