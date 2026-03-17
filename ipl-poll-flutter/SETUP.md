# IPL Poll — Flutter App Setup Guide

## Requirements
- Flutter SDK >= 3.2.0
- Dart >= 3.2.0
- Android Studio / VS Code with Flutter extension
- Android Emulator or physical device

---

## Step 1 — Extract and open the project

```bash
cd ipl-poll-flutter
```

---

## Step 2 — Configure the API URL

Open `lib/core/constants.dart` and set `baseUrl` to point to your Laravel backend:

```dart
// Android emulator (default)
static const String baseUrl = 'http://10.0.2.2:8000/api';

// iOS simulator
static const String baseUrl = 'http://localhost:8000/api';

// Physical device on same WiFi (use your machine's local IP)
static const String baseUrl = 'http://192.168.1.xxx:8000/api';

// Production
static const String baseUrl = 'https://your-domain.com/api';
```

---

## Step 3 — Install dependencies

```bash
flutter pub get
```

---

## Step 4 — Create assets directories

```bash
mkdir -p assets/images assets/icons
```

---

## Step 5 — Run the app

```bash
flutter run
```

Or specify a device:

```bash
flutter run -d emulator-5554     # Android emulator
flutter run -d                   # Lists available devices
```

---

## App Flow

```
App Start
    ↓
Splash Screen (checks saved token)
    ├── No token → Login Screen
    └── Token found → fetch profile
              ↓
         must_change_password?
              ├── YES → Change Password Screen (back button disabled)
              └── NO  → Home Screen
```

---

## Screens Overview

| Screen              | Route              | Description                              |
|---------------------|--------------------|------------------------------------------|
| Login               | `/login`           | Mobile + password login                  |
| Change Password     | `/change-password` | Forced on first login, optional later    |
| Home                | `/home`            | All IPL matches with poll status         |
| Match Detail        | `/match/:id`       | Pick team, set bid, community stats      |
| My Predictions      | `/my-polls`        | All user's polls with won/lost badges    |
| Wallet              | `/wallet`          | Coin balance + transaction history       |
| Leaderboard         | `/leaderboard`     | Top 50 users ranked by coins             |
| Profile             | `/profile`         | Stats, change password, logout           |

---

## Key Packages Used

| Package                    | Purpose                           |
|----------------------------|-----------------------------------|
| `dio`                      | HTTP client for API calls         |
| `flutter_riverpod`         | State management                  |
| `go_router`                | Navigation                        |
| `flutter_secure_storage`   | Secure token persistence          |
| `intl`                     | Date/number formatting            |

---

## Build for Release

### Android APK
```bash
flutter build apk --release
# Output: build/app/outputs/flutter-apk/app-release.apk
```

### Android App Bundle (for Play Store)
```bash
flutter build appbundle --release
```

### iOS (Mac required)
```bash
flutter build ios --release
```

---

## Troubleshooting

**"Connection refused" error**
- Make sure Laravel server is running (`php artisan serve`)
- Check the `baseUrl` in `constants.dart` matches your setup
- For Android emulator use `10.0.2.2`, NOT `localhost`

**"Cleartext HTTP traffic not permitted" on Android**
- Add to `android/app/src/main/AndroidManifest.xml` inside `<application>`:
  ```xml
  android:usesCleartextTraffic="true"
  ```
  (Development only — use HTTPS in production)

**Token not persisting between app restarts**
- `flutter_secure_storage` requires keystore on Android
- Run on a real device or properly configured emulator
