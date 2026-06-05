# Ngrok + Moodle Live Setup Guide (Hinglish)

Ye file explain karti hai ke ANME LearnOps business platform aur Moodle/ANME Academy ko ngrok par kaise expose kiya gaya hai, aur kal ko disable/enable kaise karna hai.

## 1. Problem Kya Thi?

Pehle plan ye tha:

- Business platform ke liye ngrok tunnel: `http://localhost:8000`
- Moodle ke liye doosra ngrok tunnel: `http://localhost:80`

Lekin ngrok free plan ne dono tunnels ko same dev domain de diya. Is se conflict/redirect issue aa raha tha.

## 2. Final Solution Kya Hai?

Ab ek hi ngrok domain use hota hai:

- Business platform: `https://abraham-epiphylline-spasmodically.ngrok-free.dev`
- Moodle / ANME Academy: `https://abraham-epiphylline-spasmodically.ngrok-free.dev/academy`

Iska matlab:

- Root `/` par business website open hogi.
- `/academy` par Moodle open hoga.

## 3. Files Jahan Changes Ki Gayi Hain

### A) Ngrok Config

File:

`C:\Users\waqas\AppData\Local\ngrok\ngrok.yml`

Kaam:

- Sirf ek tunnel rakha gaya hai: `anme`
- Ye tunnel Apache port `80` ko public karta hai.

Important part:

```yaml
tunnels:
  anme:
    proto: http
    addr: 80
```

### B) Apache/Laragon Virtual Host

File:

`C:\laragon\etc\apache2\sites-enabled\auto.anme-ngrok.conf`

Kaam:

- Same ngrok domain par Laravel/business app root par serve hoti hai.
- Moodle ko `/academy` path par alias kiya gaya hai.

Important part:

```apache
DocumentRoot "D:/web/anme-learnops/public"
Alias /academy "D:/web/moodle1/public"
DirectorySlash Off
```

`DirectorySlash Off` zaroori hai. Iske baghair Apache `/academy` ko `/academy/` par bhejta hai, aur Moodle wapas `/academy` par redirect karta hai. Is se browser mein “too many redirects” aa jata hai.

### C) Moodle Config

File:

`D:\web\moodle1\config.php`

Kaam:

- Local mode mein Moodle `http://moodle.test` par chalega.
- Ngrok/proxy mode mein Moodle URL `https://ngrok-domain/academy` banega.
- `HTTP_X_FORWARDED_PROTO` aur `HTTP_X_FORWARDED_HOST` read kiye gaye hain taake HTTPS/ngrok URL sahi bane.

### D) Laravel App Env

File:

`D:\web\anme-learnops\.env`

Current safe/local Moodle mode:

```env
MOODLE_BASE_URL=http://moodle.test
MOODLE_REST_URL=http://moodle.test/webservice/rest/server.php
```

Public Moodle mode ke liye:

```env
MOODLE_BASE_URL=https://abraham-epiphylline-spasmodically.ngrok-free.dev/academy
MOODLE_REST_URL=https://abraham-epiphylline-spasmodically.ngrok-free.dev/academy/webservice/rest/server.php
```

## 4. Public Moodle Mode Enable Kaise Karna Hai?

Step 1: Laragon restart karo:

- Laragon open karo
- `Stop All`
- `Start All`

Step 2: ngrok start karo:

```powershell
ngrok start --all
```

Step 3: Laravel project folder mein command run karo:

```powershell
cd D:\web\anme-learnops
powershell -ExecutionPolicy Bypass -File .\scripts\activate-moodle-ngrok.ps1
```

Ye command:

- `.env` mein Moodle URL ko `/academy` public URL par set karegi.
- `php artisan config:clear` run karegi.
- `php artisan moodle:check` run karegi.

Agar `moodle:check` pass ho gaya, Moodle public URL ready hai.

## 5. Local Moodle Mode Par Wapas Kaise Jana Hai?

Agar public `/academy` setup fail ho ya local testing karni ho:

```powershell
cd D:\web\anme-learnops
powershell -ExecutionPolicy Bypass -File .\scripts\use-local-moodle.ps1
```

Ye `.env` ko wapas local Moodle par set karega:

```env
MOODLE_BASE_URL=http://moodle.test
MOODLE_REST_URL=http://moodle.test/webservice/rest/server.php
```

## 6. Disable Kaise Karna Hai?

Agar ye ngrok/Moodle alias setup disable karna ho:

1. Apache config file rename/delete karo:

`C:\laragon\etc\apache2\sites-enabled\auto.anme-ngrok.conf`

2. Local Moodle mode script run karo:

```powershell
cd D:\web\anme-learnops
powershell -ExecutionPolicy Bypass -File .\scripts\use-local-moodle.ps1
```

3. Laragon restart karo:

- `Stop All`
- `Start All`

## 7. Important Notes

- Ngrok free URL kabhi kabhi change ho sakta hai.
- Agar URL change ho, Google Cloud callback URL bhi update karna hoga.
- Moodle public URL tabhi fully chalega jab Apache/Laragon naya `/academy` alias load karega.
- Agar `/academy` par redirect loop aaye to confirm karein ke Laragon restart ho chuka hai aur Apache config mein `DirectorySlash Off` active hai.
- Agar `php artisan moodle:check` fail ho, pehle local mode par wapas jao aur issue trace karo.

## 8. Useful Commands

Check Moodle API:

```powershell
php artisan moodle:check
```

Clear Laravel config:

```powershell
php artisan config:clear
```

Start ngrok:

```powershell
ngrok start --all
```

Local Moodle mode:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\use-local-moodle.ps1
```

Public Moodle mode:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\activate-moodle-ngrok.ps1
```
