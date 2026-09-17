# mobile

A new Flutter project.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.

## Google Sign-In setup

1. Create Android, iOS, and Web OAuth clients in Google Cloud Console.
2. Register the Android application ID and SHA-1/SHA-256 fingerprints. The
   current development application ID is `com.example.mobile`. After adding
   the fingerprints, download a fresh `google-services.json` and replace
   `android/app/google-services.json`. The file must contain an Android OAuth
   client (`client_type: 1`), not only the Web client (`client_type: 3`).
3. Put the Web OAuth client ID/secret in the backend `.env` as
   `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`.
4. Copy `.env.example` to `.env`, replace the placeholder client IDs, then run:

```bash
flutter run --dart-define-from-file=.env
```

On Android, `GOOGLE_SERVER_CLIENT_ID` is optional when the Web OAuth client is
present in `google-services.json`; the Google Services Gradle plugin exposes it
to `google_sign_in` automatically. It can still be supplied explicitly for
build environments that do not package that resource.

For web development on Windows, use the project helper. It limits concurrent
debug-module loading and disables browser extensions for Flutter's temporary
debug profile, avoiding DWDS connection timeouts on larger builds:

```powershell
.\tool\run_web.ps1
```

The example API URL uses `10.0.2.2` for the Android emulator. Use your
computer's LAN IP for a physical device, or `http://localhost:8000/api` for
Flutter Web.

For iOS, also pass `GOOGLE_CLIENT_ID=YOUR_IOS_CLIENT_ID` and add the iOS
client's reversed URL scheme to `ios/Runner/Info.plist` as documented by the
`google_sign_in_ios` package. Never commit OAuth client secrets to the mobile
application.
