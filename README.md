## ⚙️ การติดตั้งและรันโปรเจกต์

### 1. 🌐 Frontend — React + Vite

```bash

# สร้างโปรเจกต์
npm create vite@latest frontend -- --template react

# เข้าไปในโฟลเดอร์
cd frontend

# ติดตั้ง dependencies
npm install

# รันในโหมด development
npm run dev

```

### 2. 📱 Mobile — Flutter

```bash

# เข้าไปในโฟลเดอร์
cd mobile

flutter clean

# ติดตั้ง dependencies
flutter pub get

# รันบน Emulator หรืออุปกรณ์จริง
flutter run

flutter run -d chrome --dart-define=API_BASE_URL=http://localhost:8000/api

flutter run --dart-define-from-file=.env

flutter run -d chrome --web-port 5000 --dart-define-from-file=.env

```

### 3. 🔧 Backend — Laravel

```bash

# สร้างโปรเจกต์
composer create-project laravel/laravel backend

# เข้าไปในโฟลเดอร์
cd backend

# คัดลอก config
cp .env.example .env

# สร้าง application key
php artisan key:generate

# ตั้งค่าฐานข้อมูลใน .env แล้ว migrate
php artisan migrate

# รัน server
php artisan serve

php artisan serve --host=0.0.0.0 --port=8000

php artisan schedule:work

```

## 📦 Scripts ที่ใช้บ่อย

### Frontend

| Command           | คำอธิบาย
|-------------------|--------------------------
| `npm run dev`     | รันในโหมด development
| `npm run build`   | Build สำหรับ production
| `npm run preview` | Preview production build

### Flutter

| Command              | คำอธิบาย
|----------------------|-------------------------
| `flutter run`        | รันแอปบน device/emulator
| `flutter build apk`  | Build Android APK
| `flutter build ios`  | Build iOS App
| `flutter test`       | รัน unit tests

### Laravel

| Command                       | คำอธิบาย
|-------------------------------|------------------------
| `php artisan serve`           | รัน development server
| `php artisan migrate`         | รัน database migrations
| `php artisan make:model`      | สร้าง Model ใหม่
| `php artisan make:controller` | สร้าง Controller ใหม่
| `php artisan storage:link`    | เชื่อม storage
| `php artisan config:cache`    | cache config (production)

---

## 🚀 การ Deploy

### Frontend → Vercel / Netlify
```bash

npm run build
# อัปโหลดโฟลเดอร์ dist/

```

### Mobile → Play Store / App Store
```bash

# Android
flutter build appbundle

# iOS
flutter build ios

```

### Backend → VPS / Shared Hosting
```bash

php artisan config:cache
php artisan route:cache
php artisan migrate --force

```"# project" 
