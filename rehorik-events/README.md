# Rehorik Events

WordPress-Plugin für Event- und Ticket-Verwaltung ohne Tribe Events.
Tickets sind normale WooCommerce-Variable-Produkte (eine Variation pro Termin).

---

## Voraussetzungen

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+
- Composer

---

## Installation

1. Ordner nach `wp-content/plugins/rehorik-events/` kopieren
2. Composer-Abhängigkeiten installieren:

```bash
cd wp-content/plugins/rehorik-events/
composer install --no-dev --optimize-autoloader
```

3. Plugin im WordPress-Admin aktivieren

---

## Asset-Platzhalter

Folgende Dateien müssen manuell in das Plugin-Verzeichnis kopiert werden:

| Pfad (relativ) | Beschreibung |
|---|---|
| `assets/fonts/cond.ttf` | Schrift „Cond" (normal) |
| `assets/fonts/cond-bold.ttf` | Schrift „Cond Bold" |
| `assets/img/logos/logo-391px.png` | Rehorik-Logo für das Ticket-PDF |
| `assets/img/hugo/hugo-365px.png` | Hugo-Maskottchen für das Ticket-PDF |
| `assets/img/footer/footer-ticket-pdf-2480px.png` | Footer-Bild für das Ticket-PDF |

Die Bilder können aus dem Theme-Verzeichnis (`assets/`) übernommen werden.

---

## Struktur

```
rehorik-events/
├── rehorik-events.php              Plugin-Bootstrap, Activation/Deactivation Hooks
├── composer.json                   dompdf Dependency
├── includes/
│   ├── class-reh-events-plugin.php Haupt-Klasse, registriert alles
│   ├── class-reh-event-post-type.php CPT reh_event + Taxonomy reh_event_cat
│   ├── class-reh-event-admin.php   Meta-Boxen (Details + Termine), save_post Hook
│   ├── class-reh-event-wc-sync.php WC Variable Product + Variations synchronisieren
│   ├── class-reh-event-cleanup.php Cron-Job für vergangene Termine
│   ├── class-reh-attendee-list.php REST-Endpoint GET /reh/v1/attendees/{date_id} + Admin-Seite
│   ├── class-reh-checkin-api.php   REST-Endpoint POST /reh/v1/checkin
│   └── class-reh-ticket-pdf.php    PDF-Generierung + E-Mail-Attachment
├── templates/
│   ├── admin-attendee-list.php     Admin-Seite Teilnehmerlisten
│   └── pdf/
│       └── ticket-pdf.php          PDF-HTML-Template (kein QR-Code, kein Tribe)
├── assets/
│   ├── css/admin.css               Admin-Styles
│   ├── js/admin.js                 Termine-Verwaltung + Check-in
│   ├── fonts/                      → Schriften hier ablegen (s. o.)
│   └── img/                        → Bilder hier ablegen (s. o.)
└── README.md
```

---

## Datenmodell

### Post-Meta auf `reh_event`

| Meta-Key | Typ | Beschreibung |
|---|---|---|
| `_reh_venue` | string | Ort |
| `_reh_address` | string | Adresse |
| `_reh_organizer` | string | Veranstalter (Default: Rehorik) |
| `_reh_duration` | int | Dauer in Minuten |
| `_reh_max_capacity` | int | Standard-Teilnehmerzahl |
| `_reh_price` | string | Standard-Preis in € |
| `_reh_is_online` | string | „1" wenn Online-Event |
| `_reh_event_dates` | array | Serialisiertes Array aller Termine |
| `_wc_product_id` | int | ID des verknüpften WC Variable Products |

### Termine-Array (`_reh_event_dates`)

Jeder Eintrag hat:

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | string | Eindeutige ID (z. B. `reh_1714812345678`) |
| `date` | string | Datum (Y-m-d) |
| `time_start` | string | Startzeit (H:i) |
| `time_end` | string | Endzeit (H:i) |
| `capacity` | int\|string | Optionale Kapazität (überschreibt Event-Standard) |
| `price` | string | Optionaler Preis (überschreibt Event-Standard) |
| `wc_variation_id` | int | ID der zugehörigen WC-Variation |
| `status` | string | `active` oder `past` |

### Post-Meta auf WC-Variation

| Meta-Key | Beschreibung |
|---|---|
| `_reh_event_id` | ID des reh_event Posts |
| `_reh_event_date_id` | ID des Termins im Dates-Array |
| `_event_timestamp_start` | Unix-Timestamp Startzeit |
| `_event_timestamp_end` | Unix-Timestamp Endzeit |

---

## REST-API

### `GET /reh/v1/attendees/{event_date_id}`

Gibt alle Teilnehmer eines Termins zurück.

**Berechtigung:** `teilnehmerliste_bei_events_anschauen`

**Response:**

```json
[
  {
    "order_id": 123,
    "variation_id": 456,
    "index": 0,
    "name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "0941 123456",
    "status": "completed",
    "checked_in": false,
    "checked_in_at": null
  }
]
```

### `POST /reh/v1/checkin`

Führt Check-in für einen Teilnehmer durch.

**Berechtigung:** `teilnehmerliste_bei_events_anschauen`

**Body:**

```json
{
  "order_id": 123,
  "variation_id": 456,
  "index": 0
}
```

**Response:**

```json
{
  "success": true,
  "checked_in_at": "2026-05-15 14:05:23"
}
```

---

## Cron-Job

Der Cron-Job `reh_cleanup_past_event_dates` läuft täglich und:

1. Findet alle Termine deren `date` + `time_end` älter als 7 Tage ist
2. Setzt `status` auf `past`
3. Löscht die zugehörige WC-Variation (force delete)
4. Setzt das Produkt auf `catalog_visibility: hidden` wenn keine aktiven Variationen mehr vorhanden

---

## Konstanten

```php
REH_TICKET_CATEGORY_SLUG     = 'veranstaltungen'
REH_EVENT_DATE_START_META    = '_event_timestamp_start'
REH_EVENT_DATE_END_META      = '_event_timestamp_end'
REH_PERMISSION_ATTENDEE_LIST = 'teilnehmerliste_bei_events_anschauen'
```
