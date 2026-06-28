# WebGuard Mobile App Requirements

## Zielbild

Die Mobile App soll WebGuard-Benutzer auf iOS und Android sofort ueber kritische Monitoring-Ereignisse informieren, aehnlich wie UptimeRobot. Der wichtigste Nutzen ist nicht die vollstaendige Administration, sondern schnelle Reaktion: Push erhalten, Vorfall verstehen, Monitorstatus pruefen und bei Bedarf in WebGuard weiterarbeiten.

Der Backend-Stand nach dieser Aenderung:

- WebGuard ist eine Laravel-App mit REST API unter `/api/v1`.
- API-Authentifizierung laeuft ueber Laravel Sanctum Bearer Tokens.
- Bestehende Monitoring-Endpunkte liefern Monitorlisten, Status, Uptime, Checks, Incidents, SSL und Kalenderdaten.
- Benachrichtigungen werden zentral ueber `NotificationRouter` und `users.notification_channels` versendet.
- Neuer Channel: `mobile_push`.
- Neue API: Mobile Push Devices koennen pro Benutzer registriert, aktualisiert, gelistet und deaktiviert werden.
- Native Mobile Login API: E-Mail/Passwort Login erstellt einen Sanctum Bearer Token fuer die App.
- Push-Zustellung erfolgt serverseitig ueber Firebase Cloud Messaging HTTP v1 oder APNs.

## MVP-Scope

Die App muss im MVP:

- Benutzer per bestehendem WebGuard API Token oder dedizierter Mobile Login API verbinden.
- Nach erfolgreicher Verbindung Push-Berechtigungen abfragen.
- FCM oder APNs Device Token an WebGuard registrieren.
- Den `mobile_push` Channel automatisch aktivieren lassen.
- Aktive Monitorings und deren Status anzeigen.
- Incident-/Recovery-/Expiry-Pushes empfangen.
- Beim Tippen auf eine Push Notification direkt zum passenden Monitor oder Incident-Kontext navigieren.
- Device Token aktualisieren, wenn Firebase ein neues Token ausstellt.
- Device beim Logout oder deaktivierten Push-Berechtigungen im Backend deaktivieren.

Nicht im MVP:

- Vollstaendige Monitor-Erstellung oder Bearbeitung.
- Paket-/Billing-Verwaltung.
- Admin-Funktionen.
- Native Passwort-Registrierung.

## Authentifizierung

Unterstuetzte Varianten:

- Bestehender WebGuard API Token: Der Benutzer erzeugt in der Weboberflaeche einen API Token und kopiert ihn in die App.
- Native Mobile Login API: Die App sendet E-Mail, Passwort und optional einen Geraetenamen an `POST /api/mobile/login` und erhaelt einen Sanctum Bearer Token.

Anforderungen:

- App speichert den Token nur im sicheren OS-Speicher:
  - iOS: Keychain
  - Android: EncryptedSharedPreferences oder Keystore-basierte Loesung
- Alle API Requests senden:

```http
Authorization: Bearer <api-token>
Accept: application/json
Content-Type: application/json
```

- Bei `401`, `403` oder wiederholten Auth-Fehlern:
  - lokale Session als getrennt markieren
  - Push Device im Backend deaktivieren, falls noch moeglich
  - Benutzer zur Token-Eingabe fuehren

Weitere Auth-Endpunkte:

- `GET /api/mobile/me`: aktuelles Benutzerprofil fuer den gespeicherten Bearer Token pruefen.
- `POST /api/mobile/logout`: aktuellen Sanctum Token widerrufen.

Empfohlene Erweiterung nach MVP: Token Rotation und optionale Verwaltung aktiver App-Sessions.

## Neue Mobile Push API

Base URL:

```text
https://<webguard-host>/api/v1
```

### Register Device

Registriert oder aktualisiert ein FCM oder APNs Device Token idempotent. Das Backend nutzt einen SHA-256 Hash des Tokens und gibt das Token nie zurueck.

```http
POST /api/v1/mobile-push-devices
```

Request:

```json
{
  "platform": "ios",
  "push_provider": "apns",
  "push_token": "apns-token",
  "device_name": "iPhone 16",
  "app_version": "1.0.0",
  "locale": "de-DE",
  "timezone": "Europe/Berlin",
  "enabled": true,
  "notifications_authorized_at": "2026-06-26T12:00:00+02:00"
}
```

Felder:

- `platform`: Pflicht, `ios` oder `android`
- `push_provider`: optional, `fcm` oder `apns`, default `fcm`
- `push_token`: Pflicht, FCM Registration Token oder APNs Device Token
- `device_name`: optional, frei lesbarer Geraetename
- `app_version`: optional, App-Version fuer Support/Debugging
- `locale`: optional, BCP-47 aehnlich, z. B. `de-DE`
- `timezone`: optional, IANA Timezone, z. B. `Europe/Berlin`
- `enabled`: optional, default `true`
- `notifications_authorized_at`: optional, Zeitpunkt der OS-Push-Erlaubnis

Response `201 Created` bei neuem Device oder `200 OK` bei bestehendem Token:

```json
{
  "data": {
    "id": "01J...",
    "platform": "ios",
    "push_provider": "apns",
    "device_name": "iPhone 16",
    "app_version": "1.0.0",
    "locale": "de-DE",
    "timezone": "Europe/Berlin",
    "enabled": true,
    "notifications_authorized_at": "2026-06-26T12:00:00+02:00",
    "last_registered_at": "2026-06-26T12:00:01+02:00",
    "last_seen_at": "2026-06-26T12:00:01+02:00",
    "revoked_at": null,
    "created_at": "2026-06-26T12:00:01+02:00",
    "updated_at": "2026-06-26T12:00:01+02:00"
  }
}
```

Backend-Nebeneffekt:

- Wenn `enabled=true`, setzt WebGuard `users.notification_channels.mobile_push.enabled=true`.

### List Devices

```http
GET /api/v1/mobile-push-devices
```

Response:

```json
{
  "data": [
    {
      "id": "01J...",
      "platform": "android",
      "push_provider": "fcm",
      "device_name": "Pixel 9",
      "app_version": "1.0.0",
      "locale": "de-DE",
      "timezone": "Europe/Berlin",
      "enabled": true,
      "notifications_authorized_at": "2026-06-26T12:00:00+02:00",
      "last_registered_at": "2026-06-26T12:00:01+02:00",
      "last_seen_at": "2026-06-26T12:00:01+02:00",
      "revoked_at": null,
      "created_at": "2026-06-26T12:00:01+02:00",
      "updated_at": "2026-06-26T12:00:01+02:00"
    }
  ]
}
```

### Update Device

```http
PATCH /api/v1/mobile-push-devices/{deviceId}
```

Request-Beispiel:

```json
{
  "device_name": "Pixel 9 Pro",
  "app_version": "1.0.1",
  "locale": "de-DE",
  "timezone": "Europe/Berlin",
  "enabled": true
}
```

Verwendung:

- App-Version aktualisieren
- Geraetenamen aktualisieren
- Device nach erneuter Push-Erlaubnis wieder aktivieren
- Device deaktivieren, wenn OS-Push-Berechtigung entzogen wurde

### Revoke Device

```http
DELETE /api/v1/mobile-push-devices/{deviceId}
```

Response:

```http
204 No Content
```

Verwendung:

- Logout
- Benutzer trennt Account
- Push-Berechtigung dauerhaft deaktiviert

Backend-Verhalten:

- Device wird nicht hart geloescht, sondern `enabled=false` und `revoked_at=<now>` gesetzt.
- Wenn keine aktiven Devices mehr existieren, setzt WebGuard `mobile_push.enabled=false`.

## Bestehende Monitoring API fuer die App

Die App kann bestehende Endpunkte verwenden:

- `GET /api/v1/monitorings`: paginierte Liste aller fuer den Benutzer sichtbaren privaten und Team-Monitorings
- `GET /api/v1/monitorings/{monitoring}`: aggregierte Detaildaten
- `GET /api/v1/monitorings/{monitoring}/status`: aktueller Status
- `GET /api/v1/monitorings/{monitoring}/uptime-downtime?days=7`
- `GET /api/v1/monitorings/{monitoring}/uptime-downtime-summary`
- `GET /api/v1/monitorings/{monitoring}/response-times`
- `GET /api/v1/monitorings/{monitoring}/checks`
- `GET /api/v1/monitorings/{monitoring}/incidents`
- `GET /api/v1/monitorings/{monitoring}/ssl`
- `GET /api/v1/monitorings/{monitoring}/uptime-calendar`

Hinweis:

- `GET /api/v1/monitorings` unterstuetzt `per_page` mit einem Bereich von 1 bis 100 und sortiert aktuell nach Name.

## Push Payload

Mobile Push Notification:

```json
{
  "title": "Monitoring incident",
  "body": "Service is down."
}
```

Data Payload:

```json
{
  "event_type": "incident",
  "severity": "critical",
  "monitoring_id": "01J...",
  "monitoring_name": "Primary API",
  "monitoring_target": "https://example.com",
  "occurred_at": "2026-06-26T12:00:00+02:00",
  "notification_id": "01J..."
}
```

Event Types:

- `incident`
- `recovery`
- `ssl_expiring`
- `ssl_expired`
- `domain_expiring`
- `domain_expired`

Severity-Werte sind Strings und derzeit z. B. `critical`, `warning`, `info`.

App-Verhalten:

- `event_type=incident`: lokale rote/kritische Darstellung, Monitor Detail oeffnen.
- `event_type=recovery`: gruene Recovery-Darstellung, Monitor Detail oeffnen.
- Expiry Events: Warn-/Risiko-Darstellung, SSL/Domain-Kontext anzeigen.
- Wenn `monitoring_id` leer ist, zur Notification/Overview oeffnen.

## Push Lifecycle

Beim ersten App-Start nach Auth:

1. API Token validieren, z. B. durch einen leichten Monitoring- oder Device-Request.
2. OS Push Permission anfragen.
3. Push Token vom Plattformdienst abrufen.
4. `POST /api/v1/mobile-push-devices` senden.
5. Device ID lokal speichern.

Bei Push Token Refresh:

1. Neues Token abrufen.
2. `POST /api/v1/mobile-push-devices` mit neuem Token und passendem `push_provider` senden.
3. Neue Device ID lokal speichern.

Bei App-Start:

1. Token aus sicherem Speicher lesen.
2. Falls Device ID vorhanden: `PATCH /api/v1/mobile-push-devices/{id}` mit `app_version`, `locale`, `timezone`, `enabled=true`.
3. Falls Device ID fehlt: Device neu registrieren.

Bei Logout:

1. Falls Device ID vorhanden: `DELETE /api/v1/mobile-push-devices/{id}`.
2. Lokalen API Token loeschen.
3. Lokale Caches loeschen.

Wenn Push Permission entzogen wird:

1. Device lokal als Push-disabled markieren.
2. `PATCH /api/v1/mobile-push-devices/{id}` mit `enabled=false`.

## App Screens

### Connect Screen

Zweck: WebGuard Account verbinden.

Elemente:

- Server URL Eingabe
- API Token Eingabe
- Button "Connect"
- Fehleranzeige fuer ungueltige URL, Auth-Fehler, Netzwerkfehler
- Hinweis, wo der API Token in WebGuard erzeugt wird

Akzeptanzkriterien:

- Token wird nie im Klartext in Logs geschrieben.
- Server URL wird normalisiert, z. B. ohne trailing slash.
- Verbindung prueft API-Erreichbarkeit vor dem Speichern.

### Push Setup Screen

Zweck: Push-Berechtigung und Device Registration.

Elemente:

- Push Permission Status
- Button zum Aktivieren der Benachrichtigungen
- Status "Dieses Geraet ist verbunden"
- Retry bei fehlgeschlagener Registrierung

Akzeptanzkriterien:

- App registriert Device erst nach erfolgreicher OS Permission oder mit `enabled=false`, wenn der Benutzer spaeter aktivieren soll.
- Bei Token Refresh erfolgt erneute Registrierung automatisch.

### Dashboard

Zweck: schneller Operations-Ueberblick.

Elemente:

- Anzahl Monitore nach Status: Up, Down, Unknown, Maintenance
- Liste kritischer aktueller Incidents
- Zuletzt empfangene Push Events lokal oder aus Backend, sofern verfuegbar
- Pull-to-refresh

Hinweis:

- Der Screen kann `GET /api/v1/monitorings` fuer die initiale Liste verwenden und Detaildaten pro Monitoring nachladen.

### Monitor List

Zweck: alle Monitorings durchsuchen und filtern.

Elemente:

- Suchfeld
- Filter: Status, Typ, Favoriten
- Kompakte Statuszeile mit Name, Target, Status, letzter Check
- Sortierung: kritisch zuerst, dann zuletzt geaendert

### Monitor Detail

Zweck: Kontext zu einem Alarm.

Elemente:

- Status Badge
- Monitor Name und Target
- Letzter Check mit Statuscode und Zeitpunkt
- Uptime-Karten fuer 1/7/30/90 Tage
- Response-Time Kurve
- Incident Liste
- SSL/Domain Warnungen, wenn relevant

### Notification Detail

Zweck: Push nachvollziehen.

Elemente:

- Event Type
- Severity
- Zeitpunkt
- Monitor Kontext
- Deep Link zum Monitor
- Rohdaten optional in Debug-Ansicht

### Settings

Elemente:

- Verbundener Server
- Account trennen
- Push Status
- Device Registration Status
- App Version
- Diagnose: letzter erfolgreicher API Call, letzter Push Token Refresh

## Plattformanforderungen

iOS:

- APNs oder Firebase Messaging konfigurieren.
- Notification Permission explizit anfragen.
- Foreground Notifications anzeigen oder im App-Inbox-Bereich darstellen.
- Deep Link Handling aus Notification Tap.

Android:

- Android 13+ Runtime Permission `POST_NOTIFICATIONS` beruecksichtigen.
- Notification Channel `monitoring_alerts` anlegen.
- High Priority FCM fuer kritische Alerts verarbeiten.
- Deep Link Handling aus Notification Tap.

## Sicherheit und Datenschutz

- Push Token sind Secret-aehnliche Identifier und duerfen nicht in Analytics/Logs geschrieben werden.
- API Token nur sicher speichern.
- Keine Monitoring Secrets oder Auth Header aus WebGuard in der App anzeigen.
- Push Payload bewusst klein halten, keine vertraulichen HTTP Bodies oder Zugangsdaten.
- Bei Logout Backend Device deaktivieren.
- Bei App-Deinstallation kann das Backend erst durch Provider-Fehler das Device revoken; der Backend Driver deaktiviert bekannte ungueltige Tokens bei FCM- oder APNs-Fehlern.

## Fehlerverhalten

- Netzwerk offline: lokale letzte Daten anzeigen, deutlich als veraltet markieren.
- API Rate Limit `429`: Retry-After beachten.
- Push Token fehlt: Push Setup als unvollstaendig anzeigen.
- Device Registration `422`: Eingabe/Plattformdaten korrigieren, nicht endlos retryen.
- Device Registration `403`: Benutzer abmelden oder Token erneuern.

## Backend-Konfiguration fuer Push

Produktiv fuer FCM benoetigt:

```env
FCM_PROJECT_ID=<firebase-project-id>
FCM_SERVICE_ACCOUNT_JSON=<json-string>
```

Alternative:

```env
FCM_PROJECT_ID=<firebase-project-id>
FCM_SERVICE_ACCOUNT_PATH=/path/to/service-account.json
```

Nur fuer Entwicklung/Tests:

```env
FCM_PROJECT_ID=<firebase-project-id>
FCM_ACCESS_TOKEN=<short-lived-access-token>
```

Produktiv fuer APNs benoetigt:

```env
APNS_KEY_ID=<apple-key-id>
APNS_TEAM_ID=<apple-team-id>
APNS_BUNDLE_ID=<ios-bundle-id>
APNS_PRIVATE_KEY=<p8-private-key>
APNS_ENVIRONMENT=production
```

Alternative:

```env
APNS_PRIVATE_KEY_PATH=/path/to/AuthKey.p8
```

Docker Compose reicht diese Variablen an PHP, Scheduler und Queue Worker weiter.

## Akzeptanzkriterien MVP

- Ein Benutzer kann die App mit API Token verbinden.
- Ein Benutzer kann sich alternativ ueber `POST /api/mobile/login` verbinden und ueber `POST /api/mobile/logout` abmelden.
- App registriert ein iOS oder Android Device via `/api/v1/mobile-push-devices`.
- WebGuard setzt `mobile_push` fuer den Benutzer automatisch aktiv.
- Ein Statuswechsel in WebGuard loest ueber den bestehenden Notification Dispatcher eine Mobile Push Notification aus.
- Push enthaelt `event_type`, `severity`, `monitoring_id`, `monitoring_name`, `monitoring_target`, `occurred_at`, `notification_id`.
- Tippen auf Push oeffnet den passenden Monitor Detail Screen.
- Logout deaktiviert das Device im Backend.
- Reinstall oder Token Refresh erzeugt keine doppelten aktiven Eintraege fuer dasselbe Push Token.
- App funktioniert mit deutscher und englischer Locale.
- Keine Tokens werden in UI Logs oder Crash Reports im Klartext ausgegeben.

## Offene Backend-Folgeaufgaben

- Optional: Filter fuer `GET /api/v1/monitorings`, z. B. Status, Typ, Suche und Team.
- Optional: Notification History API fuer mobile Inbox.
- Optional: Per-Monitor Mobile-Push Auswahl direkt in der App.
- Optional: Quiet Hours und kritische Alerts, die Quiet Hours umgehen duerfen.
