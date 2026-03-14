# IPL Poll — Laravel Backend Setup Guide

## Requirements
- PHP >= 8.2
- Composer
- MySQL >= 8.0
- A terminal / command prompt

---

## Step 1 — Clone / Extract the project

Place the `ipl-poll-backend` folder anywhere on your machine, then open a terminal inside it.

---

## Step 2 — Install PHP dependencies

```bash
composer install
```

---

## Step 3 — Create the environment file

```bash
cp .env.example .env
```

Then open `.env` and update the database section:

```env
DB_DATABASE=ipl_poll
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password
```

---

## Step 4 — Generate app key

```bash
php artisan key:generate
```

---

## Step 5 — Create the MySQL database

Log into MySQL and run:

```sql
CREATE DATABASE ipl_poll CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## Step 6 — Run migrations and seed

```bash
php artisan migrate --seed
```

This creates all tables and seeds:
- **Admin account** → Mobile: `9999999999` | Password: `Admin@123`
- **Default settings** → bonus_coins: 1000, min_bid: 10, max_bid: 5000

---

## Step 7 — Set up the jobs queue (for match result processing)

Create the jobs table:

```bash
php artisan queue:table
php artisan migrate
```

Start the queue worker (keep this running in a separate terminal):

```bash
php artisan queue:work --tries=3
```

> **Production tip:** Use Supervisor to keep `queue:work` running persistently.

---

## Step 8 — Start the development server

```bash
php artisan serve
```

API is now available at: `http://localhost:8000/api`

---

## Default Admin Credentials

| Field    | Value         |
|----------|---------------|
| Mobile   | 9999999999    |
| Password | Admin@123     |

> Change this immediately after first login via `POST /api/auth/change-password`

---

## API Quick Reference

### Auth
| Method | Endpoint                    | Body / Notes                              |
|--------|-----------------------------|-------------------------------------------|
| POST   | `/api/auth/login`           | `{ mobile, password }`                    |
| POST   | `/api/auth/change-password` | `{ old_password, new_password, new_password_confirmation }` |
| GET    | `/api/auth/profile`         | Bearer token required                     |
| POST   | `/api/auth/logout`          | Bearer token required                     |

### User Endpoints (Bearer token required)
| Method | Endpoint                    | Notes                          |
|--------|-----------------------------|--------------------------------|
| GET    | `/api/matches`              | All matches + user's poll      |
| GET    | `/api/matches/{id}`         | Single match + community stats |
| POST   | `/api/polls`                | `{ match_id, selected_team, bid_amount }` |
| PUT    | `/api/polls/{id}`           | Update pick/bid before lock    |
| GET    | `/api/polls/my`             | User's poll history            |
| GET    | `/api/wallet/balance`       | Coin balance + summary         |
| GET    | `/api/wallet/transactions`  | Transaction log (paginated)    |
| GET    | `/api/leaderboard`          | Top 50 users                   |
| GET    | `/api/leaderboard/my-rank`  | Caller's rank                  |

### Admin Endpoints (`is_admin = true` + Bearer token)
| Method | Endpoint                              | Notes                           |
|--------|---------------------------------------|---------------------------------|
| GET    | `/api/admin/dashboard`               | Stats overview                  |
| GET    | `/api/admin/users`                   | All users, searchable           |
| POST   | `/api/admin/users`                   | Create user + award bonus       |
| PUT    | `/api/admin/users/{id}`              | Update name/mobile              |
| POST   | `/api/admin/users/{id}/reset-password` | New temp password              |
| PATCH  | `/api/admin/users/{id}/toggle-active`| Enable/disable user             |
| POST   | `/api/admin/users/{id}/adjust-coins` | Credit or debit coins           |
| POST   | `/api/admin/users/award-bonus-all`   | Bulk bonus to all users         |
| GET    | `/api/admin/matches`                 | All matches                     |
| POST   | `/api/admin/matches`                 | Create match                    |
| PUT    | `/api/admin/matches/{id}`            | Edit match details              |
| PATCH  | `/api/admin/matches/{id}/status`     | Set upcoming/live/completed     |
| POST   | `/api/admin/matches/{id}/set-result` | Declare winner → triggers settlement |
| GET    | `/api/admin/settings`                | View all settings               |
| PUT    | `/api/admin/settings`                | Update bonus_coins, min/max_bid |

---

## Key Business Rules

1. **No self-registration** — only admin can create users via `POST /api/admin/users`
2. **Temporary password** — `must_change_password = true` on creation; user must change it on first login
3. **Bid deducted immediately** when a poll is placed
4. **Polls can be changed** (team + bid amount) until admin sets match status to `live`
5. **Result settlement** runs as a background queue job after admin calls `set-result`
6. **Winners receive** `bid × win_multiplier` (default 1.9×)
7. **Losers** simply don't get their bid back

---

## Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Set `QUEUE_CONNECTION=redis` (install Redis + `predis/predis`)
- [ ] Configure Supervisor for `queue:work`
- [ ] Set up HTTPS (Nginx + Let's Encrypt)
- [ ] Change default admin password
- [ ] Set strong `DB_PASSWORD`
- [ ] Run `php artisan config:cache && php artisan route:cache`
