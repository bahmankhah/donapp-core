# Donapp Core

Lightweight WordPress plugin + micro-framework providing REST APIs, SSO auth, wallet & vendor adapters, WooCommerce cart gateway, and an admin dashboard (FA language labels included) for operational management.

## Table of Contents
1. Features
2. Quick Start
3. Environment Variables
4. Architecture Overview
5. API Reference
6. Adapters (Auth / Vendor / Wallet)
7. WooCommerce Integration
8. Admin Dashboard
9. SSO Flow
10. Extending (Routes, Middleware, Adapters, Wallet Types)
11. Project Structure
12. Development Tips
13. License

## 1. Features
* Custom micro-framework (DI container, routing, pipeline middleware)
* REST endpoints (blog, video, products, temporary cart, wallet operations)
* Wallet system: credit, coin, cash, suspended + virtual composition (VirtualCreditCash)
* SSO authentication guard (Keycloak-style) with automatic login redirect (`?login=true`)
* Vendor adapter (Donap) for external purchase verification & redirects
* WooCommerce gateway registration + programmatic cart population via token
* Admin pages: dashboard, settings, wallets, transactions, reports, SSO users
* Reporting & gift (bonus) configuration per recharge tiers

## 2. Quick Start
```bash
git clone <repository-url> donapp-core
# Place inside wp-content/plugins/donapp-core
cp .env.example .env   # If you create one; see vars below
```
Activate the plugin in WP Admin → Plugins. Activation runs registration & creates tables (via WP upgrade routines).

## 3. Environment Variables
Create an `.env` in the plugin root (loaded indirectly—ensure your environment loader or server exports these):
```env
APP_URL="https://example.com"
DONAPP_API_KEY="internal-api-key"          # Used for protected internal endpoints (X-API-KEY)
DONAPP_EXT_API_KEY="external-api-key"       # Used by vendor adapter (Donap external services)
AUTH_SSO_LOGIN_URL="https://sso.example/auth"
AUTH_SSO_CLIENT_ID="client-id"
AUTH_SSO_VALIDATE_URL="https://sso.example/validate"
```
Values are accessed with `getenv()` and trimmed where needed.

## 4. Architecture Overview
Directory split:
* `Kernel/` – framework primitives (Container, RouteManager, Middleware pipeline, Facades, Auth guards, Adapters base)
* `src/` – application domain (Configs, Providers, Controllers, Services, Models, Adapters contexts, Routes)
* `views/` – PHP view templates (admin UI + components)

Bootstrap flow (`donapp-core.php`):
1. Load Kernel autoload + helpers
2. Register activation hook (AppServiceProvider->register)
3. On `plugins_loaded` run App + Hook/Filter provider; register Woo gateway if WooCommerce present
4. On `init` boot remaining service providers (Elementor, Routes, Shortcodes, SSO, Woo, Admin)
5. If `?login=true` and user not logged in → redirect to SSO login URL via Auth facade

Global middlewares (configured in `src/configs/app.php`): `AppMiddleware`, `ExceptionMiddleware`, `ResponseMiddleware`.

## 5. API Reference
Namespace base: `wp-json/dnp/v1/`

Current routes (see `RouteServiceProvider`):
* `POST /product` – product auth / processing (AuthController@product)
* `GET /blog` – list blog posts
* `GET /blog/video` – list video posts
* `POST /cart` – create a temporary stored cart (protected)
* `POST /wallet/{type}` – modify wallet (protected) – `{type}` in `[credit|coin|cash|suspended|virtualCreditCash]` where applicable

Protected endpoints require header:
```
X-API-KEY: <DONAPP_API_KEY>
```

Example wallet credit top-up:
```bash
curl -X POST \
   -H "X-API-KEY: $DONAPP_API_KEY" \
   -H "Content-Type: application/json" \
   -d '{"identifier":123,"amount":50000,"action":"add","description":"Topup"}' \
   https://example.com/wp-json/dnp/v1/wallet/credit
```

Response middleware ensures consistent JSON formatting for API responses.

## 6. Adapters
Defined in `src/configs/adapters.php`.
* Vendor: default `donap` context (external API key, access verification & redirect URLs)
* Auth: default `sso` using `Kernel\Auth\Guards\SSOGuard`
* Wallet: contexts map to concrete classes under `Adapters/Wallet/Contexts` assigning a logical type constant

Switching default adapters = change `default` key in config.

## 7. WooCommerce Integration
* Registers a custom payment gateway (`App\Core\WCDonapGateway`) when WooCommerce present.
* Temporary cart creation: backend stores product IDs & returns an identifier. Visiting site with `?dnpuser=<token>` triggers `WooServiceProvider` to hydrate the Woo cart, then the temporary store row is deleted.
* Ensures `WC()->cart->set_session()` so items persist.

## 8. Admin Dashboard
Menu slug base: `donap-dashboard` (FA language labels for end users). Pages:
* Dashboard (metrics: users, total balance, transactions, recent activity)
* Settings (general + gift value tiers)
* Wallets (list, create wallet, modify balances, filtering + stats)
* Transactions (pagination, filters, stats)
* Reports (transactions & wallets export-style table view)
* SSO Users (paginated directory)

Gift tiers (configurable): up to 50k, 50–100k, 100–200k, above 200k (values stored in `donap_gift_values`).

## 9. SSO Flow
* Unauthenticated visit to any page with `?login=true` → redirect to `AUTH_SSO_LOGIN_URL` via `Auth::sso()->getLoginUrl()`.
* Guard validation uses `AUTH_SSO_VALIDATE_URL` and client credentials.

## 10. Extending
Routes:
```php
Route::get('example', [ExampleController::class, 'index'])->make();
```
Add middleware:
```php
Route::post('secure', [SecureController::class,'store'])
      ->middleware(ApiKeyMiddleware::class)
      ->make();
```
New middleware: create class under `App/Middlewares`, register globally in `configs/app.php` or per-route.

New wallet type: create context class under `Adapters/Wallet/Contexts`, then add entry in `adapters.php` with `type` mapping (or `null` for virtual).

Vendor / Auth adapters: add context class & entry in `adapters.php` and switch `default`.

Views: add PHP templates under `views/` and call via `view('path/name', $data)`.

## 11. Project Structure (summary)
```
Kernel/        Micro-framework core
src/configs    App + adapters configuration
src/Providers  Service providers (boot logic per domain)
src/Routes     Route registration
src/Services   Business services (Wallet, Transaction, etc.)
src/Models     Data abstractions (WP DB usage)
views/         Admin & component views
```

## 12. Development Tips
* Use the Container: `Container::resolve('WalletService')` to fetch services.
* Keep controllers thin; put logic in services.
* When adding DB interactions, prefer existing model pattern (`->where()->first()` etc.).
* Add error handling in services; global `ExceptionMiddleware` standardizes responses.
* For new protected endpoints, always validate `X-API-KEY` via `ApiKeyMiddleware`.

## 13. License
This project is provided as-is without any warranty.
