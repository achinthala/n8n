# Dcw_AiContentCreator (AC-518)

Custom Magento Admin **AI Content Creator** for Adobe Commerce Cloud 2.4.5-p17.

## Scope (MVP)

- Admin-only generation for native product attributes:
  - `description`
  - `short_description`
  - `meta_title`
  - `meta_description`
- Providers: OpenAI and Google Gemini (selectable)
- Flow: **Generate → Preview/Edit → Apply Draft (form only) → Product Save**
- No Amasty AI dependency; no new product attributes; no bulk/CMS/categories/images

## Theme / layer

**adminhtml | PHP-only** — no Hyvä/Luma storefront templates.

## Deploy notes (do not run locally in agent)

1. Deploy branch via Cloud pipeline.
2. Ensure module is enabled (`Dcw_AiContentCreator` in `app/etc/config.php`).
3. On environment: `bin/magento setup:upgrade` (Cloud deploy handles this).
4. Flush cache after deploy.
5. Configure **Stores → Configuration → DCW → AI Content Creator**:
   - Enable module
   - Set default provider
   - Enter OpenAI and/or Gemini API keys (encrypted)
   - Adjust prompts as needed
6. Confirm Cloud egress allows:
   - `https://api.openai.com`
   - `https://generativelanguage.googleapis.com`
7. Assign ACL:
   - `Dcw_AiContentCreator::config` for configuration
   - `Dcw_AiContentCreator::generate` for product Generate/Apply Draft

## SEO Toolkit handoff

AI generates/improves product copy and optional meta. Amasty SEO Toolkit / Meta Tag Templates remain owner of analysis/templates/remediation. Prefer product-level meta (after AI + Save) over empty-template fill; confirm with SEO in QA.

## PIM note

Does not write `incstores_pim_*` or other PIM-owned attributes. Live Hyvä PDP body remains PIM-driven; MVP shopper-visible impact is mainly meta (+ PLP short description where shown).
