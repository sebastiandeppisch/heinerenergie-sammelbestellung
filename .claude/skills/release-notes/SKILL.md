---
name: release-notes
description: Write user-facing German release notes / CHANGELOG entries for the heinerenergie-sammelbestellung project. Use when preparing a release, summarizing commits since the last tag, or updating CHANGELOG.md.
---

# Release Notes erstellen

Ziel: aus den Commits seit dem letzten Release eine **endnutzerfreundliche, deutschsprachige** Changelog-Sektion erzeugen und in `CHANGELOG.md` (Repo-Wurzel) ablegen.

## Ablauf

1. **Basis bestimmen.** Startpunkt ist das letzte Release (Tag oder der vom Nutzer genannte Commit). Commits auflisten:
   `git log <basis>..HEAD --oneline`
   Bei unklaren Commit-Messages die Diffs/Stats ansehen: `git show <sha> --stat` bzw. den vollen Diff.

2. **Issues nachschlagen.** Commits referenzieren Issues (`#49`, `fixes #49`). Für jede Nummer den Issue-Titel/-Beschreibung lesen, um die Änderung aus **Nutzersicht** zu beschreiben.
   - Primär: `gh issue view <nr>`.
   - Falls `gh` fehlt: direkt per WebFetch von
     `https://github.com/sebastiandeppisch/heinerenergie-sammelbestellung/issues/<nr>`.
   - Das **Kanban-/Projektboard** ist die beste Quelle für die Issue-Nummern und die Admin/Berater-Zuordnung — nach dem Board-Stand abgleichen, ob im Changelog etwas fehlt.

3. **Filtern.** Reine Chores ignorieren: Auto-Format (Pint/Prettier/ESLint/Rector), Merge-Commits, `fix test`, `fix type`/phpstan, CI, Dependency-Bumps, Tooling-Installs (Sail/Playwright), Tippfehler.

4. **Formulieren.**
   - Sprache: **Deutsch**, aus Endnutzersicht.
   - **Keine technischen Bibliotheks-/Framework-Namen** (z. B. shadcn, DevExtreme, Tiptap, Inertia) — Endnutzer:innen interessiert das Verhalten, nicht die Technik.
   - Personenbezeichnung: **„Berater:innen"** (nicht „Beratende") und **„Klient:innen"**.
   - Die `#123`-Referenz **ans Ende** der jeweiligen Zeile setzen, damit GitHub verlinkt. Mehrere möglich (`#52 #55`).
   - Nur Features aufnehmen, die tatsächlich als Code ausgeliefert wurden — reine Doku (z. B. Datenschutz-Diagramme) ist **kein** Feature.

5. **Struktur.** Gruppierung nach:
   - `### Neue Funktionen` → unterteilt in `#### Für Administratoren` und `#### Für Berater:innen`
   - `### Fehlerbehebungen`

   Admin vs. Berater:in am Code festmachen: admin-gated ist alles hinter `is_acting_as_admin` (z. B. Dashboard-Bearbeitung, Farbanpassung pro Gruppe, Benutzerverwaltung/-import, Deaktivieren). Berater:innen-Alltag: Arbeit an einer Beratung (Dateien, Mails, Checklisten nutzen, Formulareinträge, Freigabe). Features mit beiden Rollen (z. B. Checklisten) ggf. in beide Abschnitte splitten (Vorlagen verwalten = Admin, nutzen = Berater:in).

6. **Ausgabe.** Eintrag in `CHANGELOG.md` unter der Wurzel mit Versions-Überschrift und Datum:
   `## [<version>] – <YYYY-MM-DD>`
   (Bei Bedarf zusätzlich eine reine Body-Version ohne Überschrift, wenn der Nutzer eine separate Datei will.)

## Beispieleintrag

```markdown
## [2026-07.1] – 2026-07-20

### Neue Funktionen

#### Für Administratoren
- **Individuelle Farbanpassung pro Gruppe.** Jede Gruppe kann ihre eigene Primärfarbe festlegen. #57

#### Für Berater:innen
- **Nextcloud-Dateien in Beratungen.** Ordner durchsuchen, verknüpfen, anlegen und Dateien hochladen. #52 #55

### Fehlerbehebungen
- Dialoge und Kartenkomponenten werden jetzt korrekt übereinander angezeigt.
```
