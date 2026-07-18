# wachtblad-ics

Console-tool die je wachtdienst-shiften ophaalt van het CPZ-wachtblad-intranet en omzet naar een `.ics`-bestand, zodat je ze kan importeren in je agenda (Google Calendar, Outlook, Apple Calendar, ...).

## Vereisten

- PHP >= 8.1
- [Composer](https://getcomposer.org/)

## Installatie

```bash
composer install
```

## Cookie ophalen

De tool heeft een geldige sessie-cookie van het wachtblad-intranet nodig, want er is geen publieke login-API:

1. Log in op het wachtblad via je browser.
2. Open de DevTools (F12) en ga naar het tabblad **Network**.
3. Klik op een willekeurig verzoek naar `wachtblad.intranetcpz.be` en kopieer de volledige waarde van de **Cookie**-header.
4. Zet die waarde in het `.env` bestand:

```bash
SESSION_COOKIE="jouw gekopieerde cookie-string"
```

> De cookie verloopt na verloop van tijd. Krijg je een `401`/`403`-foutmelding, log dan opnieuw in en herhaal deze stappen.

## Gebruik

```bash
bin/console wachtblad:sync-ics --start 2026-07-03 --end 2026-08-03 --out wachtblad.ics
```

Dit schrijft een `.ics`-bestand weg met je eigen wachtdienst-shiften tussen de opgegeven start- en einddatum.

### Opties

| Optie | Verplicht | Standaard | Omschrijving |
|---|---|---|---|
| `--start` | ja | - | Startdatum van de periode (`YYYY-MM-DD`) |
| `--end` | ja | - | Einddatum van de periode (`YYYY-MM-DD`) |
| `--out` | nee | `wachtblad.ics` | Pad naar het output `.ics`-bestand |
| `--all` | nee | uit | Neem alle events op in het bestand, niet enkel je eigen shiften |

### Voorbeeld: alle events (niet enkel eigen shiften)

```bash
bin/console wachtblad:sync-ics --start 2026-07-03 --end 2026-08-03 --out wachtblad-all.ics --all
```

## Agenda importeren

Importeer het gegenereerde `.ics`-bestand in je agenda-applicatie naar keuze (bv. Google Calendar → Instellingen → Importeren en exporteren).
