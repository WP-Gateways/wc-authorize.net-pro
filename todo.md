# Authorize.net (free) – pending items

## Plugins screen and Payments list links (from Clover, 2026-11-25)

Do both with this plugin's next change (not as a PR of their own), test first, updating `DocumentationLinksTest`.

- [ ] **Support link to the row meta.** Move Support from `plugin_action_links()` to `plugin_row_meta()`, after Documentation. Settings and License (from the updater) stay under the plugin name; off-site links go in the row meta. Pattern: Clover `woocommerce-gateway-clover` 9795f7c, wallets fb49750.
- [ ] **Docs line out of `method_description`.** WooCommerce's React Payments list shows `method_description` as plain text, so `WC_Authnet::docs_link()` and the sandbox/notes HTML run together there. Set `method_description` to a plain-text summary of the gateway, and add the docs line above it and the HTML notes below it only in `admin_options()`, restoring the description afterwards. Files: `includes/class-wc-gateway-authnet.php`, `includes/aim/class-wc-gateway-authnet.php`. Pattern: Clover 93b82af (Advanced) / 020a35a (Basic), wallets fb49750 and 81d8104.
