# Dcw_ShipRegionAvailability

Region-based shipping availability for configurable PDPs (Hyvä).

## Client design reference

**Source of truth:** [`/shipping-module-mockup.html`](../../../shipping-module-mockup.html) (repo root)

| Mockup section | Magento implementation |
|----------------|------------------------|
| `.shipping-saver` (ZIP panel) | `view/frontend/templates/product/ship-region/panel.phtml` + `web/css/ship-region-pdp.css` |
| `#normalView` (Quick Ship + Select A Color) | Existing Hyvä swatch renderer (hidden after ZIP apply) |
| `.region-section` (green / amber) | `product/ship-region/region-swatch-sections.phtml` |
| `updateRegion()` / `onShowColorsClick()` | `integration.phtml` + `swatch-options-patch.phtml` |
| ZIP lookup | `Controller/Zip/Lookup.php` + admin ZIP grid |

### Mockup behavior (implemented)

- **Before ZIP:** Default PDP — Quick Ship Colors + Select A Color (unchanged).
- **After valid ZIP + “Show My Colors”:** Hide default swatch blocks; show **Fastest & Lowest Shipping** and **Ships From Farther Away** with Quick Ship + Standard Shipping Colors per region (child `incstores_pim_ships_from_region`).
- **Near (closest):** child has `incstores_pim_ships_from_region` including the ZIP’s region code.
- **Far (longer transit):** child has a different region, or the attribute is empty / not set.

### Mockup behavior (not yet implemented)

From mockup annotations / JS — planned follow-ups:

- Thickness gate (`#thicknessPrompt`) before revealing region sections
- Per-region active color readout (`#fastestActiveLine`, ship date ranges)
- Auto-select cheapest color across regions
- Color count badges per subsection
- Commercial / large-order prompts

## Admin

- **Catalog → Ship Region Availability → Regions** — region codes (e.g. `midwest`, `west`)
- **Catalog → Ship Region Availability → ZIP Codes** — ZIP → region; **Import CSV** (sample CSV download and export of existing mappings on the import page)

CSV: `zip_code`, `region_code` (aliases `zip`, `region`). Codes normalized to lowercase on import.

## Product attributes

| Code | Scope | Notes |
|------|--------|--------|
| `incstores_pim_ships_from_region` | Simple (child) | Multiselect; **options and values from PIM/EAV** (API sync). ZIP grid `code` should match attribute option values. |
| `incstores_pim_ship_region_availability` | Configurable (parent) | Yes — enables PDP UI |

## Storefront

- ZIP panel: `product-info.phtml` — after **Order Free Sample**, before `product.info.form` (configurable swatches), per `shipping-module-mockup.html`
- Lookup: `GET /dcw_shipregion/zip/lookup?zip=10001`
- Hyvä: `initSwatchOptions` patch + theme `renderer.phtml` (desktop + mobile)
- `localStorage`: `dcw_ship_region_zip`

## Module enable (required)

**Stores → Configuration → DCW Store Config → Ship Region Availability → Enable Module**

Runs when **Enable Module** = Yes **and** parent attribute = Yes. Admin grids work regardless.

```bash
bin/magento cache:flush
```

## Test on PDP

1. Enable module + parent attribute.
2. Sync child **Ships From Region** via PIM/API (attribute options + product values).
3. Map test ZIP in admin.
4. Enter ZIP → **Show My Colors** → near/far sections appear.
