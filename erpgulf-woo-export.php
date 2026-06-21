<?php

/**
 * Plugin Name: ERPGulf WooCommerce Product Exporter
 * Description: Export WooCommerce products to ERPNext-compatible Data Import CSV format with progress bar UI.
 * Version:     2.0.0
 * Author:      ERPGulf
 *
 * Install:
 *   Upload this folder to /var/www/woocommerce/wp-content/plugins/erpgulf-woo-export/
 *   Activate via WP Admin → Plugins
 *   Access via WP Admin → ERPGulf Export
 */
if (!defined('ABSPATH'))
    exit;

// ── Admin menu ────────────────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_menu_page('ERPGulf Exporter', 'ERPGulf Export', 'manage_woocommerce',
        'erpgulf-export', 'erpgulf_export_page', 'dashicons-download', 56);
});

// ── Column definitions ────────────────────────────────────────────────────────
// Each entry: [ label, fieldname, required ]
function erpgulf_main_columns()
{
    return [
        // Core
        ['ID', 'name', 'Yes'],
        ['Item Name', 'item_name', 'Yes'],
        ['Item Group', 'item_group', 'Yes'],
        ['Default Unit of Measure', 'stock_uom', 'Yes'],
        ['Manufacturer part Number', 'manufacturer_part_no', 'No'],
        ['Brand', 'brand', 'No'],
        ['Product Group', 'product_group', 'No'],
        ['Category', 'category', 'No'],
        ['Group', 'business_group', 'No'],
        ['Purchase Group', 'purchase_group', 'No'],
        ['Business Category', 'business_category', 'No'],
        ['Business Category Code', 'business_category_code', 'No'],
        ['Created On', 'creation', 'No'],
        ['Created By', 'owner', 'No'],
        ['Series', 'naming_series', 'No'],
        ['Tax Code', 'tax_code', 'No'],
        ['Disabled', 'disabled', 'No'],
        ['Maintain Stock', 'is_stock_item', 'No'],
        ['Has Variants', 'has_variants', 'No'],
        ['Opening Stock', 'opening_stock', 'No'],
        ['Valuation Rate', 'valuation_rate', 'No'],
        ['Standard Selling Rate', 'standard_rate', 'No'],
        ['Is Fixed Asset', 'is_fixed_asset', 'No'],
        ['Description', 'description', 'No'],
        ['Is Zero Rated', 'is_zero_rated', 'No'],
        ['Is Exempt', 'is_exempt', 'No'],
        ['Woocommerce ID', 'woocommerce_id', 'No'],
        ['OEN', 'oen', 'No'],
        ['Brand Code', 'brand_code', 'No'],
        ['Category Code', 'category_code', 'No'],
        ['Business Group Code', 'group_code', 'No'],
        ['Sub Category', 'sub_category', 'No'],
        ['Sub Category Code', 'sub_category_code', 'No'],
        ['HS Code', 'custom_hs_code', 'No'],
        ['Shelf Life In Days', 'shelf_life_in_days', 'No'],
        ['End of Life', 'end_of_life', 'No'],
        ['Default Material Request Type', 'default_material_request_type', 'No'],
        ['Valuation Method', 'valuation_method', 'No'],
        ['Warranty Period (in days)', 'warranty_period', 'No'],
        ['Weight Per Unit', 'weight_per_unit', 'No'],
        ['Weight UOM', 'weight_uom', 'No'],
        ['Allow Negative Stock', 'allow_negative_stock', 'No'],
        ['Has Batch No', 'has_batch_no', 'No'],
        ['Has Expiry Date', 'has_expiry_date', 'No'],
        ['Has Serial No', 'has_serial_no', 'No'],
        ['Disable Sync', 'custom_disable_sync', 'No'],
        ['Verified', 'custom_verified', 'No'],
        ['VIN required', 'custom_vin_required', 'No'],
        ['Universal', 'custom_universal', 'No'],
        ['Disable sync if not in stock', 'custom_disable_sync_if_not_in_stock', 'No'],
        ['Woo name - Arabic', 'custom_woo_name__arabic', 'No'],
        ['woo - Short Description', 'custom_woo__short_description', 'No'],
        ['Woo Description', 'custom_woo_description', 'No'],
        ['Woo Image URL', 'custom_woo_image_url', 'No'],
        ['Categories', 'custom_categories', 'No'],
        ['Shipping Class', 'custom_shipping_class', 'No'],
        ['Variant Of', 'variant_of', 'No'],
        ['Allow Purchase', 'is_purchase_item', 'No'],
        ['Lead Time in days', 'lead_time_days', 'No'],
        ['Last Purchase Rate', 'last_purchase_rate', 'No'],
        ['Grant Commission', 'grant_commission', 'No'],
        ['Allow Sales', 'is_sales_item', 'No'],
        ['Max Discount (%)', 'max_discount', 'No'],
        ['Include Item In Manufacturing', 'include_item_in_manufacturing', 'No'],
    ];
}

// Compatibility child table columns
function erpgulf_compat_columns()
{
    return [
        ['ID', 'name', 'No'],
        ['Created On', 'creation', 'No'],
        ['Created By', 'owner', 'No'],
        ['Brand', 'brand', 'No'],
        ['Model', 'model', 'No'],
        ['Years', 'years', 'No'],
        ['Fuel', 'fuel', 'No'],
        ['Engine Size', 'engine_size', 'No'],
        ['Body Type', 'bodytype', 'No'],
    ];
}

// Offer categories child table columns
function erpgulf_offer_columns()
{
    return [
        ['ID', 'name', 'No'],
        ['Created On', 'creation', 'No'],
        ['Created By', 'owner', 'No'],
        ['Offer Name', 'offer_name', 'No'],
    ];
}

// WooCommerce server child table columns
function erpgulf_woo_server_columns()
{
    return [
        ['ID', 'name', 'No'],
        ['WooCommerce Server', 'woocommerce_server', 'No'],
        ['Created On', 'creation', 'No'],
        ['Created By', 'owner', 'No'],
        ['Enable Sync', 'enabled', 'No'],
        ['WooCommerce ID', 'woocommerce_id', 'No'],
        ['Last Sync Hash', 'woocommerce_last_sync_hash', 'No'],
    ];
}

// ── Build the 4 header rows ────────────────────────────────────────────────────
function erpgulf_build_header_rows()
{
    $main = erpgulf_main_columns();
    $compat = erpgulf_compat_columns();
    $offer = erpgulf_offer_columns();
    $woo = erpgulf_woo_server_columns();

    $n_main = count($main);
    $n_compat = count($compat);
    $n_offer = count($offer);
    $n_woo = count($woo);

    // Row 0: section headers
    $row0 = array_fill(0, $n_main, '');
    $row0[0] = 'Item';
    // Compatibility section
    $row0[] = '~';
    $row0[] = 'Compatibility';
    $row0[] = 'custom_compatibility';
    for ($i = 3; $i < $n_compat; $i++)
        $row0[] = '';
    // Offer categories section
    $row0[] = '~';
    $row0[] = 'Offer Categories';
    $row0[] = 'custom_offer_categories';
    for ($i = 3; $i < $n_offer; $i++)
        $row0[] = '';
    // WooCommerce server section
    $row0[] = '~';
    $row0[] = 'Item WooCommerce Server';
    $row0[] = 'woocommerce_servers';
    for ($i = 3; $i < $n_woo; $i++)
        $row0[] = '';

    // Row 1: field labels
    $row1 = array_column($main, 0);
    $row1[] = '';
    foreach ($compat as $c)
        $row1[] = $c[0];
    $row1[] = '';
    foreach ($offer as $c)
        $row1[] = $c[0];
    $row1[] = '';
    foreach ($woo as $c)
        $row1[] = $c[0];

    // Row 2: field API names
    $row2 = array_column($main, 1);
    $row2[] = '~';
    foreach ($compat as $c)
        $row2[] = $c[1];
    $row2[] = '~';
    foreach ($offer as $c)
        $row2[] = $c[1];
    $row2[] = '~';
    foreach ($woo as $c)
        $row2[] = $c[1];

    // Row 3: required flags
    $row3 = array_column($main, 2);
    $row3[] = 'NaN';
    foreach ($compat as $c)
        $row3[] = $c[2];
    $row3[] = 'NaN';
    foreach ($offer as $c)
        $row3[] = $c[2];
    $row3[] = 'NaN';
    foreach ($woo as $c)
        $row3[] = $c[2];

    return [$row0, $row1, $row2, $row3];
}

// ── Total column count ─────────────────────────────────────────────────────────
function erpgulf_total_cols()
{
    return count(erpgulf_main_columns())
        + 1 + count(erpgulf_compat_columns())
        + 1 + count(erpgulf_offer_columns())
        + 1 + count(erpgulf_woo_server_columns());
}

// ── Build one full item block (main row + child rows) ─────────────────────────
function erpgulf_build_item_rows($post_id)
{
    $post = get_post($post_id);
    $product = wc_get_product($post_id);
    if (!$product)
        return [];

    $total_cols = erpgulf_total_cols();
    $main_cols = count(erpgulf_main_columns());
    $c_cols = count(erpgulf_compat_columns());
    $o_cols = count(erpgulf_offer_columns());
    $w_cols = count(erpgulf_woo_server_columns());

    // ── helpers ──
    $blank = array_fill(0, $total_cols, '');

    // ── Meta helpers ──────────────────────────────────────────────────────────
    $m = function ($key) use ($post_id) {
        return (string) get_post_meta($post_id, $key, true);
    };

    // ── Woo server info ───────────────────────────────────────────────────────
    $woo_id = $m('_woocommerce_id') ?: $product->get_id();
    $woo_sku = $product->get_sku();

    // ── Categories & tags ─────────────────────────────────────────────────────
    $cats = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'names']);
    $cat_str = is_array($cats) ? implode(' | ', $cats) : '';

    // ── Main row ──────────────────────────────────────────────────────────────
    $main_row = $blank;

    $vals = [
        $woo_sku ?: $post->post_name,  // name / ID
        $post->post_title,  // item_name
        $cat_str,  // item_group (closest mapping)
        'Piece',  // stock_uom
        $m('_sku'),  // manufacturer_part_no (SKU as part no)
        $m('manufacturer_brand'),  // brand
        '',  // product_group
        $cat_str,  // category
        '',  // business_group
        '',  // purchase_group
        '',  // business_category
        '',  // business_category_code
        $post->post_date,  // creation
        '',  // owner
        '',  // naming_series
        '',  // tax_code
        ($post->post_status === 'publish') ? '0' : '1',  // disabled
        '1',  // is_stock_item
        '0',  // has_variants
        '0',  // opening_stock
        '0',  // valuation_rate
        $m('_regular_price'),  // standard_rate
        '0',  // is_fixed_asset
        wp_strip_all_tags($post->post_content),  // description
        '0',  // is_zero_rated
        '0',  // is_exempt
        $product->get_id(),  // woocommerce_id
        $m('_sku'),  // oen
        '',  // brand_code
        '',  // category_code
        '',  // group_code
        '',  // sub_category
        '',  // sub_category_code
        '',  // custom_hs_code
        '0',  // shelf_life_in_days
        '31-12-2099',  // end_of_life
        'Purchase',  // default_material_request_type
        '',  // valuation_method
        '',  // warranty_period
        $m('_weight'),  // weight_per_unit
        '',  // weight_uom
        '0',  // allow_negative_stock
        '0',  // has_batch_no
        '0',  // has_expiry_date
        '0',  // has_serial_no
        $m('custom_disable_sync'),  // custom_disable_sync
        '',  // custom_verified
        $m('mark_spare_part'),  // custom_vin_required
        '',  // custom_universal
        '',  // custom_disable_sync_if_not_in_stock
        $post->post_title,  // custom_woo_name__arabic (arabic title)
        $post->post_excerpt,  // custom_woo__short_description
        $post->post_content,  // custom_woo_description
        $m('_product_image_gallery'),  // custom_woo_image_url
        $cat_str,  // custom_categories
        '',  // custom_shipping_class
        '',  // variant_of
        '1',  // is_purchase_item
        '0',  // lead_time_days
        $m('_regular_price'),  // last_purchase_rate
        '1',  // grant_commission
        '1',  // is_sales_item
        '0',  // max_discount
        '1',  // include_item_in_manufacturing
    ];

    for ($i = 0; $i < count($vals); $i++) {
        $main_row[$i] = $vals[$i];
    }

    $rows = [$main_row];

    // ── Compatibility child rows ───────────────────────────────────────────────
    // Offset in full row where compat section starts
    $compat_start = $main_cols + 1;  // +1 for the ~ separator col
    $compat_count = (int) get_post_meta($post_id, 'add_compactable_details', true);

    for ($i = 0; $i < $compat_count; $i++) {
        $child_row = $blank;
        $brand = get_post_meta($post_id, "add_compactable_details_{$i}_brand", true);
        $model = get_post_meta($post_id, "add_compactable_details_{$i}_model", true);
        $years = get_post_meta($post_id, "add_compactable_details_{$i}_years", true);
        $fuel = get_post_meta($post_id, "add_compactable_details_{$i}_variant", true);
        $engine = get_post_meta($post_id, "add_compactable_details_{$i}_engine_size", true);

        // compat cols: name, creation, owner, brand, model, years, fuel, engine_size, bodytype
        $child_row[$compat_start + 0] = '';  // name (auto)
        $child_row[$compat_start + 1] = '';  // creation
        $child_row[$compat_start + 2] = '';  // owner
        $child_row[$compat_start + 3] = $brand;
        $child_row[$compat_start + 4] = $model;
        $child_row[$compat_start + 5] = $years;
        $child_row[$compat_start + 6] = $fuel;
        $child_row[$compat_start + 7] = $engine;
        $child_row[$compat_start + 8] = '';  // bodytype

        $rows[] = $child_row;
    }

    // ── Offer categories child rows ────────────────────────────────────────────
    $offer_start = $compat_start + $c_cols + 1;  // +1 for ~ separator
    $offer_cats = wp_get_post_terms($post_id, 'offer_category', ['fields' => 'names']);
    if (is_array($offer_cats)) {
        foreach ($offer_cats as $offer_name) {
            $child_row = $blank;
            $child_row[$offer_start + 0] = '';  // name (auto)
            $child_row[$offer_start + 1] = '';  // creation
            $child_row[$offer_start + 2] = '';  // owner
            $child_row[$offer_start + 3] = $offer_name;  // offer_name
            $rows[] = $child_row;
        }
    }

    // ── WooCommerce server child row ──────────────────────────────────────────
    $woo_start = $offer_start + $o_cols + 1;
    $woo_server = get_option('woocommerce_api_consumer_key') ? get_site_url() : '';

    // Get WooCommerce server name from WPML or first available
    $wc_servers = get_posts(['post_type' => 'woocommerce_server', 'numberposts' => 1]);
    $server_name = !empty($wc_servers) ? $wc_servers[0]->post_title : get_site_url();

    $child_row = $blank;
    $child_row[$woo_start + 0] = '';  // name (auto)
    $child_row[$woo_start + 1] = $server_name;  // woocommerce_server
    $child_row[$woo_start + 2] = $post->post_date;  // creation
    $child_row[$woo_start + 3] = '';  // owner
    $child_row[$woo_start + 4] = '1';  // enabled
    $child_row[$woo_start + 5] = $product->get_id();  // woocommerce_id
    $child_row[$woo_start + 6] = $post->post_modified;  // last_sync_hash
    $rows[] = $child_row;

    return $rows;
}

// ── Admin page HTML ────────────────────────────────────────────────────────────
function erpgulf_export_page()
{
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name']);
    $nonce = wp_create_nonce('erpgulf_export_nonce');
    $wpml_active = defined('ICL_LANGUAGE_CODE');
    ?>
    <div id="erpgulf-wrap">
        <header class="eg-header">
            <div class="eg-logo">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="#00897B"/><path d="M8 16.5L13.5 22L24 11" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>ERPGulf</span>
            </div>
            <h1>Product Exporter → ERPNext Format</h1>
            <p class="eg-subtitle">Exports WooCommerce products as ERPNext Data Import CSV — ready to import directly into ERPNext Items.</p>
        </header>

        <div class="eg-body">
            <section class="eg-card" id="eg-filters">
                <h2><span class="eg-dot"></span> Export Filters</h2>
                <div class="eg-grid">
                    <div class="eg-field">
                        <label>SKU</label>
                        <input type="text" id="eg-sku" placeholder="Leave blank for all">
                    </div>
                    <div class="eg-field">
                        <label>Status</label>
                        <select id="eg-status">
                            <option value="publish" selected>Published</option>
                            <option value="any">Any</option>
                            <option value="draft">Draft</option>
                            <option value="trash">Trash</option>
                        </select>
                    </div>
                    <div class="eg-field">
                        <label>Product Type</label>
                        <select id="eg-type">
                            <option value="">All Types</option>
                            <option value="simple">Simple</option>
                            <option value="variable">Variable</option>
                        </select>
                    </div>
                    <div class="eg-field">
                        <label>Category</label>
                        <select id="eg-category">
                            <option value="">All Categories</option>
                            <?php foreach ((array) $categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?> (<?php echo (int) $cat->count; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($wpml_active): ?>
                    <div class="eg-field">
                        <label>Language (WPML)</label>
                        <select id="eg-lang">
                            <option value="">All Languages</option>
                            <?php foreach (apply_filters('wpml_active_languages', []) as $code => $info): ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($info['native_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="eg-field">
                        <label>Limit <small>(0 = all)</small></label>
                        <input type="number" id="eg-limit" value="0" min="0" step="100">
                    </div>
                    <div class="eg-field">
                        <label>Offset</label>
                        <input type="number" id="eg-offset" value="0" min="0" step="100">
                    </div>
                    <div class="eg-field">
                        <label>WooCommerce Server Name</label>
                        <input type="text" id="eg-woo-server" placeholder="e.g. portal.mrkbatx.com">
                    </div>
                </div>
                <div class="eg-actions">
                    <button id="eg-count-btn" class="eg-btn eg-btn-secondary">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Count Products
                    </button>
                    <button id="eg-start-btn" class="eg-btn eg-btn-primary">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1M4.22 4.22l.71.71m12.73 12.73.71.71M3 12h1m16 0h1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/></svg>
                        Start Export
                    </button>
                </div>
                <div id="eg-count-result" class="eg-count-result" style="display:none;"></div>
            </section>

            <section class="eg-card" id="eg-progress-panel" style="display:none;">
                <h2><span class="eg-dot eg-dot-active"></span> Export Progress</h2>
                <div class="eg-progress-meta">
                    <span id="eg-prog-label">Preparing…</span>
                    <span id="eg-prog-count">0 / 0</span>
                </div>
                <div class="eg-progress-track">
                    <div class="eg-progress-bar" id="eg-progress-bar"></div>
                </div>
                <div id="eg-log" class="eg-log"></div>
                <div class="eg-actions" id="eg-stop-row">
                    <button id="eg-stop-btn" class="eg-btn eg-btn-danger">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2" stroke="currentColor" stroke-width="2"/></svg>
                        Stop Export
                    </button>
                </div>
                <div class="eg-actions" id="eg-download-row" style="display:none;">
                    <a id="eg-download-btn" class="eg-btn eg-btn-success" href="#" target="_blank">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M12 3v13m0 0l-4-4m4 4l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Download CSV (ERPNext Import Format)
                    </a>
                    <button id="eg-new-export-btn" class="eg-btn eg-btn-secondary">New Export</button>
                </div>
            </section>
        </div>
    </div>

    <script>
    (function($){
        const NONCE = '<?php echo esc_js($nonce); ?>';
        const AJAX  = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        const BATCH = 50;
        let totalProducts = 0, exported = 0, exportKey = '', running = false;

        function getFilters() {
            return {
                sku:        $('#eg-sku').val().trim(),
                status:     $('#eg-status').val(),
                type:       $('#eg-type').val(),
                category:   $('#eg-category').val(),
                lang:       $('#eg-lang').val() || '',
                limit:      parseInt($('#eg-limit').val()) || 0,
                offset:     parseInt($('#eg-offset').val()) || 0,
                woo_server: $('#eg-woo-server').val().trim(),
            };
        }

        $('#eg-count-btn').on('click', function(){
            const $btn = $(this).prop('disabled', true).text('Counting…');
            $('#eg-count-result').hide();
            $.post(AJAX, { action: 'erpgulf_export_count', nonce: NONCE, ...getFilters() })
            .done(function(r){
                if(r.success){
                    $('#eg-count-result').html('<strong>' + r.data.count + '</strong> product(s) match your filters.').show();
                } else {
                    $('#eg-count-result').html('<span class="eg-error">' + (r.data||'Error') + '</span>').show();
                }
            })
            .always(function(){ $btn.prop('disabled', false).html('<svg width="16" height="16" fill="none" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> Count Products'); });
        });

        $('#eg-start-btn').on('click', function(){
            if(running) return;
            running = true; exported = 0; exportKey = '';
            $('#eg-filters').addClass('eg-dimmed');
            $('#eg-progress-panel').show();
            $('#eg-stop-row').show();
            $('#eg-download-row').hide();
            $('#eg-log').empty();
            setProgress(0, 0, 'Initialising…');

            $.post(AJAX, { action: 'erpgulf_export_init', nonce: NONCE, ...getFilters() })
            .done(function(r){
                if(!r.success){ return abort(r.data || 'Init failed.'); }
                totalProducts = r.data.total;
                exportKey     = r.data.key;
                log('Found ' + totalProducts + ' product(s). Exporting in ERPNext format…');
                setProgress(0, totalProducts, 'Exporting…');
                runBatch(0);
            })
            .fail(function(){ abort('Server error during init.'); });
        });

        function runBatch(offset){
            // Check if user stopped before firing the next batch
            if(!running) return;

            // NOTE: do NOT spread getFilters() here — user's offset filter is stored in the
            // transient at init time. Spreading it would double-add the offset each batch.
            $.post(AJAX, { action: 'erpgulf_export_batch', nonce: NONCE, key: exportKey, offset: offset, batch: BATCH })
            .done(function(r){
                if(!running) return; // stopped mid-flight
                if(!r.success){ return abort(r.data || 'Batch error.'); }
                exported += r.data.products;
                log('Products exported: ' + exported + ' / ' + totalProducts + ' (' + r.data.rows + ' CSV rows written this batch)');
                setProgress(exported, totalProducts, 'Exporting…');
                if(r.data.done){ finish(r.data.download_url); }
                else { runBatch(offset + BATCH); }
            })
            .fail(function(){ abort('Server error during batch.'); });
        }

        function finish(url){
            running = false;
            setProgress(totalProducts, totalProducts, 'Export complete ✓');
            log('✅ Done! Ready for ERPNext Data Import.');
            $('#eg-download-btn').attr('href', url);
            $('#eg-stop-row').hide();
            $('#eg-download-row').show();
        }

        function abort(msg){
            running = false;
            log('❌ ' + msg);
            setProgress(0, 0, 'Export failed');
            $('#eg-stop-row').hide();
            $('#eg-filters').removeClass('eg-dimmed');
        }

        // Stop button — halts after current batch completes
        $('#eg-stop-btn').on('click', function(){
            if(!running) return;
            running = false;
            log('⏹ Export stopped by user. ' + exported + ' of ' + totalProducts + ' products exported.');
            setProgress(exported, totalProducts, 'Stopped');
            $('#eg-stop-row').hide();
            // Still show download if we exported something
            if(exported > 0){
                $('#eg-download-row').show();
            }
            $('#eg-filters').removeClass('eg-dimmed');
        });

        function setProgress(done, total, label){
            const pct = total > 0 ? Math.min(100, Math.round(done/total*100)) : 0;
            $('#eg-progress-bar').css('width', pct + '%');
            $('#eg-prog-label').text(label);
            $('#eg-prog-count').text(total > 0 ? done + ' / ' + total : '');
        }

        function log(msg){
            const $log = $('#eg-log');
            $log.append('<div class="eg-log-line">' + msg + '</div>');
            $log.scrollTop($log[0].scrollHeight);
        }

        $('#eg-new-export-btn').on('click', function(){
            running = false;
            $('#eg-progress-panel').hide();
            $('#eg-stop-row').show();
            $('#eg-download-row').hide();
            $('#eg-filters').removeClass('eg-dimmed');
            $('#eg-log').empty();
            exported = 0;
        });
    })(jQuery);
    </script>

    <style>
    #erpgulf-wrap * { box-sizing: border-box; }
    #erpgulf-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; max-width: 900px; margin: 20px 20px 40px; color: #1a2332; }
    .eg-header { background: linear-gradient(135deg, #00897B 0%, #004D40 100%); border-radius: 12px; padding: 28px 32px; color: #fff; margin-bottom: 24px; display: flex; flex-direction: column; gap: 6px; }
    .eg-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
    .eg-logo span { font-weight: 700; font-size: 13px; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .eg-header h1 { margin: 0; font-size: 22px; font-weight: 700; color: #fff; }
    .eg-subtitle { margin: 0; opacity: .75; font-size: 13px; }
    .eg-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px 28px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .eg-card h2 { margin: 0 0 20px; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .eg-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; display: inline-block; }
    .eg-dot-active { background: #00897B; box-shadow: 0 0 0 3px rgba(0,137,123,.2); }
    .eg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .eg-field { display: flex; flex-direction: column; gap: 6px; }
    .eg-field label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
    .eg-field label small { font-weight: 400; text-transform: none; letter-spacing: 0; }
    .eg-field input, .eg-field select { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; font-size: 14px; color: #1a2332; background: #f8fafc; outline: none; transition: border-color .15s; width: 100%; }
    .eg-field input:focus, .eg-field select:focus { border-color: #00897B; box-shadow: 0 0 0 3px rgba(0,137,123,.12); background: #fff; }
    .eg-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .eg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: 7px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all .15s; text-decoration: none; }
    .eg-btn-primary  { background: #00897B; color: #fff; }
    .eg-btn-primary:hover  { background: #00796B; color: #fff; }
    .eg-btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
    .eg-btn-secondary:hover { background: #e2e8f0; }
    .eg-btn-success  { background: #16a34a; color: #fff; }
    .eg-btn-success:hover  { background: #15803d; color: #fff; }
    .eg-count-result { margin-top: 14px; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 14px; color: #166534; }
    .eg-error { color: #b91c1c; }
    .eg-progress-meta { display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 10px; }
    #eg-prog-label { font-weight: 600; color: #1a2332; }
    .eg-progress-track { height: 10px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-bottom: 20px; }
    .eg-progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #00897B, #26a69a); border-radius: 99px; transition: width .4s ease; }
    .eg-log { background: #0f172a; border-radius: 8px; padding: 14px 16px; max-height: 200px; overflow-y: auto; font-family: "SF Mono", monospace; font-size: 12px; color: #94a3b8; margin-bottom: 20px; }
    .eg-log:empty { display: none; }
    .eg-log-line { padding: 2px 0; border-bottom: 1px solid rgba(255,255,255,.04); }
    .eg-dimmed { opacity: .45; pointer-events: none; user-select: none; }
    .eg-btn-danger { background: #dc2626; color: #fff; }
    .eg-btn-danger:hover { background: #b91c1c; color: #fff; }
    </style>
    <?php
}

// ── AJAX: Count ───────────────────────────────────────────────────────────────
add_action('wp_ajax_erpgulf_export_count', function () {
    check_ajax_referer('erpgulf_export_nonce', 'nonce');
    if (!current_user_can('manage_woocommerce'))
        wp_send_json_error('Unauthorised');
    $args = erpgulf_build_query_args($_POST);
    $args['fields'] = 'ids';
    $query = new WP_Query($args);
    wp_send_json_success(['count' => $query->found_posts]);
});

// ── AJAX: Init ────────────────────────────────────────────────────────────────
add_action('wp_ajax_erpgulf_export_init', function () {
    check_ajax_referer('erpgulf_export_nonce', 'nonce');
    if (!current_user_can('manage_woocommerce'))
        wp_send_json_error('Unauthorised');

    $filters = $_POST;
    $args = erpgulf_build_query_args($filters);
    $args['fields'] = 'ids';
    $query = new WP_Query($args);
    $total = $query->found_posts;

    if ($total === 0)
        wp_send_json_error('No products match the selected filters.');

    $key = 'erpgulf_exp_' . bin2hex(random_bytes(8));
    set_transient($key, $filters, HOUR_IN_SECONDS);

    // Write file with 4 header rows
    $file = erpgulf_csv_path($key);
    $fh = fopen($file, 'w');
    fwrite($fh, "\u{FEFF}");  // UTF-8 BOM
    foreach (erpgulf_build_header_rows() as $hrow) {
        fputcsv($fh, $hrow);
    }
    fclose($fh);

    wp_send_json_success([
        'total' => $total,
        'key' => $key,
        'download_url' => erpgulf_csv_url($key),
    ]);
});

// ── AJAX: Batch ───────────────────────────────────────────────────────────────
add_action('wp_ajax_erpgulf_export_batch', function () {
    check_ajax_referer('erpgulf_export_nonce', 'nonce');
    if (!current_user_can('manage_woocommerce'))
        wp_send_json_error('Unauthorised');

    $key = sanitize_key($_POST['key'] ?? '');
    $offset = absint($_POST['offset'] ?? 0);
    $batch = absint($_POST['batch'] ?? 50);

    $filters = get_transient($key);
    if (!$filters)
        wp_send_json_error('Export session expired. Please start again.');

    $args = erpgulf_build_query_args($filters);
    $args['fields'] = 'ids';
    $args['posts_per_page'] = $batch;
    // $offset from JS is the batch cursor (0, 50, 100…).
    // erpgulf_build_query_args already sets the user's starting offset from $filters.
    // We override it here: user_offset + batch_cursor
    $user_offset = (int) ($filters['offset'] ?? 0);
    $args['offset'] = $user_offset + $offset;
    $args['no_found_rows'] = true;

    $query = new WP_Query($args);
    $post_ids = $query->posts;
    $products = 0;
    $rows = 0;

    $fh = fopen(erpgulf_csv_path($key), 'a');

    foreach ($post_ids as $post_id) {
        $item_rows = erpgulf_build_item_rows($post_id);
        foreach ($item_rows as $row) {
            fputcsv($fh, $row);
            $rows++;
        }
        $products++;
    }

    fclose($fh);
    wp_cache_flush();

    wp_send_json_success([
        'products' => $products,
        'rows' => $rows,
        'done' => count($post_ids) < $batch,
        'download_url' => erpgulf_csv_url($key),
    ]);
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function erpgulf_build_query_args($p)
{
    $args = [
        'post_type' => 'product',
        'post_status' => sanitize_text_field($p['status'] ?? 'publish'),
        'orderby' => 'ID',
        'order' => 'ASC',
    ];

    $limit = (int) ($p['limit'] ?? 0);
    $offset = (int) ($p['offset'] ?? 0);
    $args['posts_per_page'] = $limit > 0 ? $limit : -1;
    if ($offset > 0)
        $args['offset'] = $offset;

    $sku = sanitize_text_field($p['sku'] ?? '');
    if ($sku) {
        $pid = wc_get_product_id_by_sku($sku);
        if ($pid) {
            $args['post__in'] = [$pid];
            $args['posts_per_page'] = 1;
        }
    }

    $type = sanitize_text_field($p['type'] ?? '');
    if ($type) {
        $args['tax_query'][] = ['taxonomy' => 'product_type', 'field' => 'slug', 'terms' => $type];
    }

    $cat = sanitize_text_field($p['category'] ?? '');
    if ($cat) {
        $args['tax_query'][] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $cat];
    }

    $lang = sanitize_text_field($p['lang'] ?? '');
    if ($lang && defined('ICL_LANGUAGE_CODE')) {
        $args['suppress_filters'] = false;
        do_action('wpml_switch_language', $lang);
    }

    return $args;
}

function erpgulf_csv_path($key)
{
    return wp_upload_dir()['basedir'] . '/' . $key . '.csv';
}

function erpgulf_csv_url($key)
{
    return wp_upload_dir()['baseurl'] . '/' . $key . '.csv';
}
