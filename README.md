# ERPGulf WooCommerce Product Exporter

**Version:** 2.0.0  
**Author:** ERPGulf  
**Stack:** WordPress + WooCommerce + ACF + WPML  

---

## What It Does

Exports WooCommerce products to a **ERPNext Data Import CSV** — ready to import directly into ERPNext Items without any manual formatting. The export runs in AJAX batches so it never times out, even on large catalogs.

---

## Installation

```bash
# 1. Create the plugin folder
mkdir /var/www/woocommerce/wp-content/plugins/erpgulf-woo-export

# 2. Copy erpgulf-woo-export.php into that folder (via VS Code / SCP)

# 3. Activate via WP-CLI
wp plugin activate erpgulf-woo-export --path=/var/www/woocommerce

# 4. Access in WP Admin → ERPGulf Export (left sidebar)
```

---

## How to Use

1. Go to **WP Admin → ERPGulf Export**
2. Set your filters (optional)
3. Click **Count Products** to preview how many rows will export
4. Click **Start Export**
5. Watch the progress bar — each batch of 50 products is written to disk
6. Click **Stop Export** at any time — partial file is still downloadable
7. When complete, click **Download CSV (ERPNext Import Format)**

---

## Filters Available

| Filter | Description |
|---|---|
| SKU | Export a single product by SKU |
| Status | publish / draft / any / trash |
| Product Type | simple / variable / all |
| Category | Filter by product category slug |
| Language | WPML language code (ar / en) — only shown if WPML is active |
| Limit | Max products to export (0 = all) |
| Offset | Skip first N products (for pagination) |
| WooCommerce Server Name | Written into the WooCommerce server child row |

---

## CSV Format

The output is an **ERPNext Data Import CSV** with 4 header rows followed by data.

### Header rows
| Row | Content |
|---|---|
| Row 1 | Section labels: `Item`, `~`, `Compatibility`, `custom_compatibility`, `~`, `Offer Categories`, `custom_offer_categories`, `~`, `Item WooCommerce Server`, `woocommerce_servers` |
| Row 2 | Field labels (human readable) |
| Row 3 | API field names (ERPNext fieldnames) |
| Row 4 | Required flags (`Yes` / `No`) |

### Data rows (per product)

Each product produces **multiple rows**:

```
[Main row]         — all product fields filled, child columns blank
[Compat row 1]     — main columns blank, compatibility cols filled (brand/model/years/fuel/engine)
[Compat row 2]     — ...repeat for each compatibility entry
[Offer cat row]    — main columns blank, offer_category cols filled
[WooCommerce row]  — main columns blank, woocommerce_servers cols filled
```

### Columns exported

**Main Item fields (63 columns)**
- `name` (SKU), `item_name`, `item_group`, `stock_uom`, `brand`, `manufacturer_part_no`
- `creation`, `disabled`, `is_stock_item`, `standard_rate`, `description`
- `custom_woo_name__arabic`, `custom_woo__short_description`, `custom_woo_description`
- `custom_woo_image_url`, `custom_shipping_class`, `custom_vin_required`
- `custom_disable_sync`, `custom_categories`, `woocommerce_id`
- ... and all other standard ERPNext Item fields

**Compatibility child table (`custom_compatibility`)**
- `brand`, `model`, `years`, `fuel`, `engine_size`, `bodytype`
- Pulled from ACF meta: `add_compactable_details_{N}_brand`, `_model`, `_years`, `_variant`, `_engine_size`
- Supports up to 20 compatibility rows per product

**Offer Categories child table (`custom_offer_categories`)**
- `offer_name`
- Pulled from WooCommerce taxonomy: `offer_category`

**WooCommerce Server child table (`woocommerce_servers`)**
- `woocommerce_server`, `enabled`, `woocommerce_id`, `woocommerce_last_sync_hash`

---

## ACF Field Mapping

| WP Meta Key | ERPNext Field |
|---|---|
| `add_compactable_details` | row count |
| `add_compactable_details_{N}_brand` | `custom_compatibility.brand` |
| `add_compactable_details_{N}_model` | `custom_compatibility.model` |
| `add_compactable_details_{N}_years` | `custom_compatibility.years` |
| `add_compactable_details_{N}_variant` | `custom_compatibility.fuel` |
| `add_compactable_details_{N}_engine_size` | `custom_compatibility.engine_size` |
| `branch_stock_{N}_branch` | (for reference — not in ERPNext import) |
| `branch_stock_{N}_stock_qty` | (for reference — not in ERPNext import) |
| `manufacturer_brand` | `brand` |
| `mark_spare_part` | `custom_vin_required` |

---

## Importing into ERPNext

1. Download the CSV from the export UI
2. In ERPNext go to: **Settings → Data Import**
3. Select Doctype: **Item**
4. Select Action: **Insert New Records** or **Update Existing Records**
5. Upload the CSV
6. Click **Import**

> ⚠️ Always test on staging before importing to production.  
> ⚠️ Run the import in batches of 500–1000 items if you have a large catalog.

---

## Updating the Plugin

```bash
# Backup current version
cp /var/www/woocommerce/wp-content/plugins/erpgulf-woo-export/erpgulf-woo-export.php \
   /var/www/woocommerce/wp-content/plugins/erpgulf-woo-export/erpgulf-woo-export.php.bak

# Then paste the new file via VS Code
```

No restart or cache clear needed after updating — the plugin takes effect immediately.

---

## Troubleshooting

**Export count shows 0**  
→ Check the status filter — default is `publish`. Try setting it to `any`.

**Compatibility columns are empty**  
→ Verify the product has ACF data:
```bash
wp post meta get <POST_ID> add_compactable_details --path=/var/www/woocommerce
wp post meta get <POST_ID> add_compactable_details_0_brand --path=/var/www/woocommerce
```

**Export session expired error**  
→ The transient TTL is 1 hour. If the export takes longer, increase batch size or reduce total products using filters.

**CSV opens with garbled Arabic in Excel**  
→ The file includes a UTF-8 BOM. In Excel: Data → From Text/CSV → File Origin: 65001 (UTF-8).

**Download link broken after stop**  
→ The partial CSV is saved in `/var/www/woocommerce/wp-content/uploads/erpgulf_exp_*.csv`. You can download it directly from the server.

---

## File Structure

```
erpgulf-woo-export/
└── erpgulf-woo-export.php    ← entire plugin in one file
```

No dependencies. No paid plugins required. No database tables created.