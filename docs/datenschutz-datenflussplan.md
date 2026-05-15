# Datenschutz Datenflussplan

Übersicht welche Daten wann von wem (Rollen) eingesehen werden können — Advice-zentriert.

Zugehörige Grafik: [`datenschutz-datenfluss.svg`](datenschutz-datenfluss.svg)

---

## Entitäten mit personenbezogenen Daten

### `Advice` (Kundendatensatz)
| Feld | Schutzklasse |
|------|-------------|
| `first_name`, `last_name` | mittel |
| `email`, `phone` | **hoch** — wird in `DataProtectedAdviceData.php` gefiltert |
| `street`, `street_number`, `zip`, `city` | mittel |
| `lng`, `lat` | mittel |
| `commentary`, `place_notes` | mittel |

### `FormSubmission` / `SubmissionField`
Freie Feldstruktur (EMAIL, PHONE, ADDRESS, IMAGE, TEXT u.a.). Wird per `FormDefinitionToAdvice`-Mapping automatisch in ein `Advice` überführt.

---

## Rollen

| Rolle | Beschreibung |
|-------|-------------|
| **Anonym** | Kein Login; kann Formulare einreichen und `/newadvice` nutzen |
| **Berater** | Mitglied einer Gruppe |
| **Gruppen-Admin** | Admin einer Gruppe, Rechte vererben sich auf Untergruppen (transitiv) |
| **System-Admin** | `is_admin = true`, globaler Vollzugriff |

---

## Datenzugriff

### Kontaktdaten (`email`, `phone`) — streng geschützt
Geregelt in `app/Data/DataProtectedAdviceData.php` + `app/Policies/AdvicePolicy.php::viewDataProtected()`:

| Situation | Darf sehen |
|-----------|-----------|
| Advice hat keinen Berater (`advisor_id = null`) | alle Gruppen-Mitglieder/-Admins |
| Advice hat zugewiesenen Berater | nur zugewiesener Berater + Shares + Gruppen-Admin (transitiv) |
| Fremder Berater (andere Gruppe) | **nein** |
| Anonym | **nein** |

### Restliche Advice-Daten (Name, Adresse, Notizen)
Geregelt in `AdvicePolicy::view()`:

| Situation | Darf sehen |
|-----------|-----------|
| Zugewiesener Berater (`advisor_id`) | ja |
| Shared Advisor (`shares_ids`) | ja |
| Gruppen-Admin (transitiv) | ja |
| Andere Berater derselben Gruppe | **nein** |
| Anonym | **nein** |

### Advice-Liste (`viewAny`)
`AdvicePolicy::viewAny()` gibt immer `true` — alle eingeloggten User sehen die Liste, aber Details sind per `view()` geschützt.

### Formulareinreichungen
Nur eingeloggt + Gruppen-Kontext. Nur Einreichungen der eigenen Gruppe sichtbar.

---

## Datenfluss: Einreichung → Advice

```
Anonym / Bürger
    │
    ▼  POST /forms/{formDefinition}  (keine Auth)
FormSubmission + SubmissionFields
    │
    ├─► E-Mail an Kunden (AdviceCreated Mail)
    │
    └─► (wenn FormDefinitionToAdvice konfiguriert)
            │
            ▼
          Advice  ◄── Benachrichtigung an Berater (Job: SendNewAdviceInfoToAdvisors)
            │
            ├─ Berater kann zugewiesen werden (assign)
            ├─ Advice kann mit weiteren Beratern geteilt werden (shares_ids)
            └─ Alle Änderungen landen in AdviceEvent (Audit Trail)
```

---

## Öffentlich zugängliche Daten (ohne Login)

| Route | Inhalt |
|-------|--------|
| `/map` | Veröffentlichte `MapPoints` (Koordinaten, Titel) |
| `/forms/{formDefinition}` | Formular (keine Kundendaten) |
| `/newadvice` | Neue-Advice-Formular (erzeugt Advice ohne Auth!) |
| `/impress`, `/datapolicy` | Statische Seiten |

---

## Datenschutzrelevante Auffälligkeiten

1. **`viewAny: true`** — alle eingeloggten User sehen Advice-Liste (Details gefiltert)
2. **`create: true` ohne Auth** — Gäste können über `/newadvice` Advices anlegen (`AdvicePolicy::create()`)
3. **Öffentliche Karte** `/map` — zeigt publizierte Standorte ohne Login
4. **Form-Bilder** unter `public/form-images/{submission_uuid}/` — bei bekanntem Pfad ohne Auth abrufbar
5. **E-Mail-Tracking** (Wnx\Sends) — Opens & Clicks werden gespeichert (DSGVO-relevant)

---

## Schlüsseldateien

| Zweck | Datei |
|-------|-------|
| Datenschutz-Filterung Kontaktdaten | `app/Data/DataProtectedAdviceData.php` |
| Zugriffsregeln Advice | `app/Policies/AdvicePolicy.php` |
| Zugriffsregeln User | `app/Policies/UserPolicy.php` |
| Zugriffsregeln Gruppe | `app/Policies/GroupPolicy.php` |
| Gruppenkontext / transitive Rechte | `app/Policies/Concerns/GroupContextHelper.php` |
| Formular → Advice Mapping | `app/Models/FormDefinitionToAdvice.php` |
| Formular-Einreichung Controller | `app/Http/Controllers/FormSubmitController.php` |
| Audit Trail | `app/Models/AdviceEvent.php` |
