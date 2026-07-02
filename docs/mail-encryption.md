# Mail-Verschlüsselung – Sequenzdiagramm

Zero-Knowledge-Ansatz: Der Server speichert niemals den Klartext-Key. Admin mit Datenbankzugriff kann Mail-Credentials nicht entschlüsseln.

## Login & Key-Ableitung

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Controller as AuthenticatedSession<br/>Controller
    participant EncService as UserEncryption<br/>Service
    participant Repo as MailCredentials<br/>Repository

    User->>Browser: Login (E-Mail + Passwort)
    Browser->>Controller: POST /api/login
    Note over Browser,Controller: Cookie enc_key wird vom Browser mitgeschickt<br/>und von EncryptCookies (implizit, Teil der<br/>web-Middleware) automatisch ver-/entschlüsselt
    Controller->>Controller: Auth::attempt() ✓
    Controller->>Controller: random_bytes(32) → Salt
    Controller->>EncService: deriveKey(Passwort, Salt)
    EncService->>EncService: PBKDF2-SHA256(Passwort, Salt, 100k) → Key (32 Byte)
    EncService-->>Controller: base64(Key)
    Controller->>Repo: Salt in Repo-Kontext ablegen (session['enc_salt'])
    Controller-->>Browser: Cookie enc_key = base64(Key)<br/>HttpOnly · Secure · SameSite=Strict
    Note over Browser,Repo: Key verlässt den Server nie im Klartext.<br/>Repo speichert nur den Salt, nicht den Key.
```

## Mail-Credentials speichern

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant ResolveKey as ResolveEncryptionKey<br/>Middleware
    participant Controller
    participant Repo as MailCredentials<br/>Repository

    Note over Browser,ResolveKey: EncryptCookies (implizit, Teil der web-Middleware)<br/>ver-/entschlüsselt den enc_key-Cookie automatisch<br/>bei jedem Request – kein manueller Aufruf nötig
    User->>Browser: Speichert IMAP/SMTP-Daten
    Browser->>ResolveKey: POST /mail/account<br/>+ Cookie enc_key (von EncryptCookies bereits entschlüsselt)
    ResolveKey->>ResolveKey: base64_decode(enc_key) → Raw Key
    ResolveKey->>ResolveKey: app()->instance('user.enc_key', RawKey)
    ResolveKey->>Controller: Request weiterleiten
    Controller->>Controller: testConnection(credentials) ✓
    Controller->>Repo: store(MailCredentialsData)
    Repo->>Repo: AES-256-GCM encrypt(JSON, RawKey)<br/>→ nonce:ciphertext:tag (base64)
    Repo->>Repo: Verschlüsselten Blob persistieren
    Note over Repo: Aktuell: Session<br/>Später austauschbar: Datenbank, Redis, …<br/>Ohne enc_key-Cookie nie lesbar.
```

## Mail abrufen (Folge-Request)

```mermaid
sequenceDiagram
    participant Browser
    participant ResolveKey as ResolveEncryptionKey<br/>Middleware
    participant Controller as Api\\MailController
    participant Repo as MailCredentials<br/>Repository
    participant IMAP

    Note over Browser,ResolveKey: EncryptCookies (implizit, Teil der web-Middleware)<br/>entschlüsselt den enc_key-Cookie automatisch
    Browser->>ResolveKey: GET /api/advices/{id}/mails<br/>+ Cookie enc_key (von EncryptCookies bereits entschlüsselt)
    ResolveKey->>ResolveKey: app()->instance('user.enc_key', RawKey)
    ResolveKey->>Controller: Request weiterleiten
    Controller->>Repo: get()
    Repo->>Repo: Ciphertext laden
    Repo->>Repo: AES-256-GCM decrypt(Ciphertext, RawKey)<br/>→ {imapHost, username, password, …}
    Repo-->>Controller: MailCredentialsData
    Controller->>IMAP: connect(host, user, pass)
    IMAP-->>Controller: Messages
    Controller-->>Browser: JSON [MailHeaderData]
    Note over Repo: Bei Entschlüsselungsfehler (falscher Key,<br/>z. B. nach Passwortänderung):<br/>Blob löschen, null zurückgeben → 403
```

## Passwort ändern

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Controller as UserController
    participant Repo as MailCredentials<br/>Repository

    User->>Browser: Neues Passwort eingeben
    Browser->>Controller: PUT /users/{id}/password
    Controller->>Controller: $user->password = newHash
    alt Eigenes Passwort
        Controller->>Repo: clear()
        Controller-->>Browser: Cookie enc_key löschen (Cookie::forget)
        Note over Browser,Repo: Key-Ableitung war an altes Passwort gebunden.<br/>Credentials sind verloren → User muss neu einrichten.
    else Admin ändert fremdes Passwort
        Note over Controller: Fremde Session nicht erreichbar.<br/>Beim nächsten Request des Users schlägt<br/>die Entschlüsselung fehl → Repository leert sich automatisch.
    end
```

## Doppelte Verschlüsselung auf einen Blick

```
Browser-Cookie:   base64(Key)  ←→  APP_KEY-Verschlüsselung durch EncryptCookies
                                    (implizit, Teil der web-Middleware – kein manueller Aufruf)
                                    (Layer 1: Schutz des Keys im Transit)

Repository:       nonce:ciphertext:tag
                  ←→  AES-256-GCM mit PBKDF2(Passwort)-Key
                                    (Layer 2: Zero-Knowledge, Admin kann nicht lesen)
```
