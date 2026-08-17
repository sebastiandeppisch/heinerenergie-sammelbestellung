# Changelog

## [2026-04.1] – main

### Neue Funktionen

#### Für Administratoren

- **Checklisten-Vorlagen verwalten.** Gruppenadministratoren können Checklisten-Vorlagen erstellen und pflegen, die Berater:innen in ihren Beratungen nutzen. #49
- **Nextcloud-Benutzerabgleich.** Benutzer aus einer Nextcloud-Gruppe können abgeglichen und übernommen werden, sodass bestehende Nextcloud-Konten ohne erneute Dateneingabe importiert werden. #54
- **Berater:innen deaktivieren.** Ein Benutzerkonto kann deaktiviert werden; deaktivierte Benutzer können sich nicht mehr anmelden und werden gebeten, sich an einen Administrator zu wenden. #11
- **Individuelle Farbanpassung pro Gruppe.** Jede Gruppe kann ihre eigene Primärfarbe festlegen, die in der gesamten Oberfläche angewendet wird. #57
- **Neue Dashboard-Diagramme.** Ein Diagramm zur monatlichen Anzahl der Beratungen, ein Diagramm zur Statusverteilung im Zeitverlauf sowie ein Diagramm zur aktuellen Statusverteilung sorgen für einen besseren Überblick.

#### Für Berater:innen

- **Checklisten in Beratungen.** Vorgefertigte Checklisten können während einer Beratung genutzt und ausgefüllt werden. Einmal in einer Beratung angelegt, bleiben sie unverändert, auch wenn die Vorlage später bearbeitet wird. #49
- **Nextcloud-Dateien in Beratungen.** Nextcloud-Ordner direkt aus einer Beratung heraus durchsuchen, verknüpfen und anlegen sowie Dateien hochladen. #52 #55
- **Mail-Konto verbinden und Beratungs-E-Mails lesen und schreiben.** Das eigene Mail-Konto kann verbunden werden. Die gesamte Kommunikation mit dem/der Klient:in wird angezeigt – alle E-Mails an und von der Beratungs-E-Mail-Adresse – und E-Mails lassen sich direkt aus der Anwendung schreiben und versenden. #56
- **Karten- und Tabellenansicht für Formulareinträge.** Formulareinträge können nun als Karten oder in einer Tabelle angezeigt werden – mit Seitenblätterung für lange Listen. #53
- **Änderungen an personenbezogenen Daten werden protokolliert.** Änderungen an den personenbezogenen Daten einer Beratung werden als Ereignisse erfasst und in der Beratungs-Timeline angezeigt. #9 #51
- **Übersichtlichere Formulareinträge.** Adressen und Koordinaten werden lesbarer dargestellt.
- **Einfachere Beratungsfreigabe.** Mehrere Empfänger lassen sich beim Freigeben einer Beratung nun bequemer über eine Tag-Auswahl hinzufügen.
- **Überarbeitete Oberfläche.** Große Teile der Anwendung wurden auf ein moderneres, einheitlicheres Design umgestellt – darunter Anmeldung, Beratungstabelle, Benutzer- und Gruppenverwaltung sowie das Anlegen einer Beratung. Benachrichtigungen erscheinen jetzt als Toasts.

### Fehlerbehebungen

- Dialoge und Kartenkomponenten werden jetzt korrekt übereinander angezeigt (Ebenen-/Z-Index-Problem behoben).
- Formulare können per iframe jetzt auch auf externe Domains eingebunden werden (#62)