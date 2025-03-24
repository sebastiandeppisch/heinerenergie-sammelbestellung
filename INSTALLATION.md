# Installation des CRM-Systems auf Shared Hosting

Diese Anleitung erklärt, wie Du das CRM-System auf einem Shared Hosting installieren kannst.

## Voraussetzungen

- PHP 8.4
- MySQL oder MariaDB Datenbank
- FTP/SFTP-Zugang zu deinem Hosting
- Datenbank-Zugangsdaten

## Installationsschritte

### 1. Dateien hochladen

1. Entpacke die ZIP-Datei auf deinem Computer
2. Lade alle Dateien und Ordner per FTP/SFTP in das Verzeichnis auf deinem Server hoch, in dem die Anwendung laufen soll

### 2. Berechtigungen setzen

Setze Schreibrechte für folgende Ordner:
```
chmod 775 storage -R
chmod 775 bootstrap/cache -R
```

Falls du keinen SSH-Zugang hast, kannst du die Berechtigungen über den FTP-Client setzen (meist "755" oder "775" für Ordner).

### 3. Datenbank einrichten

1. Erstelle eine neue Datenbank auf deinem Hosting
2. Erstelle einen Datenbankbenutzer mit Vollzugriff auf diese Datenbank
3. Notiere dir die Zugangsdaten (Datenbankname, Benutzername, Passwort)

### 4. Umgebungsvariablen konfigurieren

1. Kopiere die Datei `.env.example` zu `.env`
2. Bearbeite die `.env`-Datei und passe folgende Einstellungen an:

```
APP_URL=https://deine-domain.de

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dein_datenbankname
DB_USERNAME=dein_datenbankbenutzer
DB_PASSWORD=dein_datenbankpasswort

MAIL_HOST=dein-smtp-mailserver.de
MAIL_PORT=dein_smtp_port
MAIL_USERNAME=deine-email@domain.de
MAIL_PASSWORD=dein-email-passwort
MAIL_FROM_ADDRESS=info@deine-domain.de
```

### 5. Anwendung im Browser aufrufen

Öffne die URL deiner Anwendung im Browser. Du solltest die Installationsseite sehen. Folge den Anweisungen, um die Anwendung zu installieren.

## Problembehebung

### Die Anwendung zeigt nur eine weiße Seite

- Überprüfe, ob der Document Root auf den `public`-Ordner zeigt
- Falls nicht möglich, stelle sicher, dass die .htaccess-Datei im Hauptverzeichnis funktioniert

### Berechtigungsprobleme

- Überprüfe, ob die Ordner `storage` und `bootstrap/cache` für den Webserver beschreibbar sind

### Datenbank-Verbindungsfehler

- Überprüfe die Datenbank-Zugangsdaten in der `.env`-Datei
- Stelle sicher, dass der Datenbankbenutzer Zugriff auf die Datenbank hat

### PHP-Version

- Diese Anwendung benötigt PHP 8.4. Überprüfe in der Hosting-Konfiguration, ob die richtige PHP-Version verwendet wird.

## Support

Bei Fragen oder Problemen wende dich an den Administrator dieser Anwendung. 
