<p align="center">
  <img src="public/assets/img/blasti-logo.png" alt="Blasti" height="90">
</p>

<h3 align="center">Blasti — bus tickets between Moroccan cities</h3>
<p align="center"><em>« Blasti » (بلاصتي) means “my seat” in Darija: every ticket is a seat reserved for you.</em></p>

---

Blasti is a bus-ticket booking platform built with Laravel 12. Travellers search for a trip, pick their seat on the bus map and get a PDF ticket. Bus companies manage their trips, fleets, counter sales, boarding and refunds from a back office, and drivers check tickets with a QR scanner.

## Features

**Public site and client area**
- Search by departure city, arrival city and date. If no bus runs that day, the next departures are shown.
- **Buses with intermediate stops**, for example Fès → Imouzzer → Ifrane → … → Marrakech:
  - A client can ride any part of the line and pays only that segment.
  - A seat freed at a stop is sold again for the rest of the trip.
  - The bus can still be boarded at later stops after it has left the first one.
- Seat map per segment: seats sold on an overlapping segment are shown as taken.
- Payment at boarding or by card (CMI, the Moroccan card-payment gateway: 3D Pay Hosting, ver3 SHA-512 hash).
- Client account with a dashboard, tickets (web + PDF), profile and settings.
- Promo codes, seat alerts on full buses, favourite trips, and changing a ticket to another departure.
- Share a ticket on WhatsApp (trip, seats and PDF link), no WhatsApp Business account needed.
- Reviews after the trip, published by the admin.
- Cancellation until the bus leaves, with **refunds that depend on how early the client cancels** (see below).
- E-mails: confirmation (PDF attached), reminder the day before, cancellation with the refund amount, review request after the trip.
- Pages: Destinations, Companies, Help (FAQ + refund table), About, Contact.
- **French, English and Arabic** (right-to-left layout), plus light and dark mode.

**Admin panel**
- Trips with their stops (time and price from the first stop), buses, companies, cities, trip types, payment methods.
- Bookings:
  - Filter by status (to pay, paid, cancelled, to refund).
  - Mark a booking as paid.
  - Cancel a booking (the client is refunded 100 %).
  - Record a refund, with its amount.
- Dashboard with revenue and occupancy, statistics, control alerts, CSV exports.
- **Ticket counter (guichet)**: sell several seats in one order, edit passenger and seat, print the tickets.
- **QR scanner**: checks each ticket at the bus door (paid, to pay, already boarded), with every scan logged.
- Users, roles and permissions, including a **company role** that only sees its own buses, departures, tickets and reviews.
- **Apparence**: platform name, one brand colour that recolours the whole site and the logo, and an optional uploaded logo (light and dark versions).

**Driver space**
- The driver's departures with the passenger list, and a button to report a delay.

## Tech stack

- PHP 8.2+, Laravel 12, Blade, Bootstrap 5
- MySQL (production) or SQLite (local and tests)
- barryvdh/laravel-dompdf for PDF tickets
- PHPUnit feature tests

## Getting started

```bash
git clone https://github.com/yazaminoura/blasti_app.git
cd blasti_app
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Choose a database in `.env`:

- **SQLite (quickest):** create the file `database/database.sqlite`, then set `DB_CONNECTION=sqlite` and remove the other `DB_*` lines.
- **MySQL:** fill in `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`.

Then run:

```bash
php artisan migrate        # also loads the demo data on an empty database
php artisan storage:link
php artisan serve
```

Open http://127.0.0.1:8000.

### Demo data

While `DEMO_DATA=true` (the default), `php artisan migrate` fills an empty database with realistic data:
- 22 cities and 6 bus companies with their buses;
- about 270 trips, from two weeks ago to three weeks ahead, several of them with intermediate stops;
- about 2,600 bookings, some covering only part of a line.

The same data is created on every machine.

| Account | E-mail | Password |
|---|---|---|
| Admin | `admin@blasti.ma` | `password` |
| Bus company | `compagnie@blasti.ma` | `password` |
| Clients | e.g. `salma.bennani@example.com` | `password` |

> Change these passwords, or set `DEMO_DATA=false`, before going live. To start over: `php artisan migrate:fresh`.

## Configuration

| `.env` key | Purpose |
|---|---|
| `APP_LOCALE` | Default language: `fr`, `en` or `ar` |
| `APP_TIMEZONE` | `Africa/Casablanca` (departure times and the cancellation deadline depend on it) |
| `SAFAR_TELEPHONE`, `SAFAR_EMAIL`, `SAFAR_ADRESSE` | Contact details shown in the footer and on the contact page |
| `SAFAR_FACEBOOK`, `SAFAR_INSTAGRAM`, `SAFAR_TIKTOK`, `SAFAR_X`, `SAFAR_LINKEDIN` | Social links; empty ones are hidden |
| `CMI_CLIENT_ID`, `CMI_STORE_KEY`, `CMI_GATEWAY_URL` | Card payment. Card payment stays hidden until the ID and store key are set **and** a payment method is marked “en ligne” in the admin |
| `MAIL_*` | SMTP settings for the e-mails (`MAIL_MAILER=log` writes them to `storage/logs`) |
| `DEMO_DATA` | `false` to start with an empty database |

**Refund policy**: `config/safar.php` → `annulation.paliers` (hours before boarding ⇒ % refunded):

| Client cancels | Refunded |
|---|---|
| more than 3 days before | 100 % |
| 2 to 3 days before | 80 % |
| 1 to 2 days before | 60 % |
| less than 1 day before | 50 % |
| after the bus has left | cancellation impossible |

Some tickets are refunded differently:
- Unpaid tickets (pay at boarding) are cancelled for free.
- A cancellation by the company (admin) is refunded 100 %.

### Scheduled tasks

Add one cron entry on the server:

```
* * * * * cd /path/to/blasti_app && php artisan schedule:run >> /dev/null 2>&1
```

It runs these commands:
- `reservations:rappels` (daily at 18:00): e-mails a reminder for tomorrow's trips.
- `reservations:expirer` (every 5 minutes): frees the seats of card payments left unfinished.
- `reservations:presence` (every 15 minutes): asks unpaid clients to pay or confirm, and cancels the ones who don't answer.
- `reservations:agence` (every 15 minutes): cancels "pay at an agency" orders not paid in time.
- `reservations:avis` (daily at 10:00): e-mails a review request to clients who travelled.

To create an admin account on a fresh server: `php artisan blasti:admin you@example.com`.

## Tests

```bash
php artisan test
```

The tests run on an in-memory SQLite database. They cover:
- booking and payment flows;
- seat resale between stops;
- the refund tiers;
- the ticket counter, the scanner and the driver space;
- admin permissions and business rules.

## Project layout

| Path | Contents |
|---|---|
| `app/Models/Voyage.php` | Trips, stops, segment price, seats taken (`serving()`, `segmentFor()`, `seatsTaken()`) |
| `app/Models/VoyageArret.php` | A stop of a trip |
| `app/Models/Reservation.php` | Booking status, payment, cancellation and refund (`refundQuote()`, `cancel()`) |
| `app/Http/Controllers/Client/` | Booking, CMI payment, client area |
| `app/Http/Controllers/Admin/`, `VoyageController.php` | Admin panel and public search |
| `app/Support/Cmi.php` | CMI hash, form fields and callback check |
| `app/Support/BrandImages.php` | Recolours the logos to the brand colour |
| `database/seeders/DemoDataSeeder.php` | Demo data |
| `lang/{fr,en,ar}.json` | Translations. The keys are the French texts |
| `public/assets/css/blasti.css`, `public/assets/admin/` | Site and admin styles |
