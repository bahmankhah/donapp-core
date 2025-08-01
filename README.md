# Donapp Core

Donapp Core is a WordPress plugin that provides REST endpoints, wallet management, and vendor integration through a lightweight PHP framework.

## Features

- Custom micro-framework with dependency container, routing, and middleware
- REST API endpoints for blog posts, products, video content, and WooCommerce carts
- Wallet system supporting credit, coin, and cash balances
- Single Sign-On (SSO) authentication via Keycloak
- Vendor adapter for granting product access through Donap services

## Installation

1. Copy this repository into your `wp-content/plugins` directory:
   ```bash
   git clone <repository-url> donapp-core
   ```
2. Create an `.env` file in the plugin root and define required keys:
   ```env
   DONAPP_API_KEY="internal-api-key"
   DONAPP_EXT_API_KEY="external-api-key"
   ```
3. Activate **Donapp Core** from the WordPress admin panel. Activation will create the necessary database tables.

## Usage

API routes are available under `/wp-json/donapp-core/v1/`. Example endpoints:

- `GET /blog` – list posts
- `GET /blog/video` – list video posts
- `POST /cart` – add a WooCommerce product to a temporary cart
- `POST /wallet/{type}` – adjust a user's wallet balance

Requests to protected routes must include the `X-API-KEY` header matching `DONAPP_API_KEY`.

## Configuration

Application settings live in `src/configs`. Adjust global middleware and plugin metadata in `src/configs/app.php`, and customize vendor/auth/wallet contexts in `src/configs/adapters.php`.

## Development

Framework code resides in the `Kernel` directory while application logic is under `src`. The plugin bootstraps by loading these classes in `donapp-core.php`.

## License

This project is provided as-is without any warranty.
