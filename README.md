# KM Decor API

Laravel API for the KM Decor product catalog, service showcase, and customer inquiries.

## Public endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/categories` | Active categories with published product counts |
| GET | `/api/brands` | Active brands with published product counts |
| GET | `/api/products` | Paginated published product catalog |
| GET | `/api/products/{slug}` | Published product detail |
| GET | `/api/services` | Active services |
| GET | `/api/services/{slug}` | Active service detail |
| GET | `/api/search?q={term}` | Grouped catalog autocomplete search |
| POST | `/api/contact` | General contact request |
| POST | `/api/inquiries` | Service quote inquiry |
| GET | `/api/inquiries` | Authenticated customer's inquiry history |
| GET | `/api/inquiries/{id}` | Authenticated customer's inquiry detail |
| POST | `/api/register` | Create customer account and token |
| POST | `/api/login` | Authenticate and create token |
| POST | `/api/forgot-password` | Email a password reset link when the account exists |
| POST | `/api/reset-password` | Reset password with an emailed token and revoke existing tokens |
| GET | `/api/email/verify/{user}/{hash}` | Verify an email address using a signed email link |
| POST | `/api/email/verification-notification` | Resend verification email for the authenticated customer |
| GET | `/api/me` | Current authenticated customer |
| POST | `/api/logout` | Revoke current token |
| PATCH | `/api/profile` | Update authenticated customer details |
| PUT | `/api/profile/password` | Change password and revoke all tokens |
| GET | `/api/addresses` | Customer address book |
| POST | `/api/addresses` | Add a saved address |
| PATCH | `/api/addresses/{id}` | Update or set default address |
| DELETE | `/api/addresses/{id}` | Archive a saved address |
| GET | `/api/wishlist` | Customer's saved products |
| POST | `/api/wishlist` | Save a published product |
| DELETE | `/api/wishlist/{product_id}` | Remove a saved product |
| DELETE | `/api/wishlist` | Clear all saved products |
| GET | `/api/cart` | Current customer's cart |
| POST | `/api/cart/items` | Add a product to cart |
| PATCH | `/api/cart/items/{id}` | Update cart item quantity |
| DELETE | `/api/cart/items/{id}` | Remove a cart item |
| POST | `/api/checkout` | Submit authenticated cart or guest item request |
| GET | `/api/orders` | Authenticated customer's order history |
| GET | `/api/orders/{id}` | Authenticated customer's order detail |
| POST | `/api/orders/{id}/reorder` | Add available historical items to cart |
| POST | `/api/orders/{id}/payments` | Submit bank-transfer proof or cash-on-delivery selection |
| POST | `/api/admin/payments/{id}/confirm` | Admin payment confirmation |
| POST | `/api/admin/payments/{id}/reject` | Admin payment rejection |
| GET | `/api/admin/orders` | Staff order queue and filters |
| PATCH | `/api/admin/orders/{id}` | Validated order status update |
| GET | `/api/admin/inquiries` | Sales inquiry queue and filters |
| PATCH | `/api/admin/inquiries/{id}` | Lead status, assignment, quote, and notes |
| CRUD | `/api/admin/categories` | Admin category management |
| CRUD | `/api/admin/brands` | Admin brand management |
| CRUD | `/api/admin/products` | Admin product publishing and inventory management |
| POST/PATCH/DELETE | `/api/admin/products/{id}/images` | Admin product image management |
| CRUD | `/api/admin/services` | Admin service management |
| GET/POST/PATCH | `/api/admin/users` | Customer and staff account administration |

Product filters: `search`, `category`, `brand`, `featured`, `in_stock`, `sort`, and `per_page`.

Send protected requests with `Authorization: Bearer <token>`.

## Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Run checks with:

```bash
./vendor/bin/pint --test
php artisan test
```

## Laravel

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
