# Plan: ard-umbau

Branch: `feature/ard-umbau` (Paket-Repo ai-manifest, von Stand e545ebb) · Spec: `spec.md` · Dashboard-Task #1430
Spec und Plan selbst freigegeben (automode 2026-11-17-0939).

## Aufgaben
- [x] T1 (AK1–AK12) test-writer — Tests: `tests/Feature/ArdManifestTest.php`, `tests/Feature/ArdEintragTest.php`, `tests/Feature/LlmsTxtTest.php`, `tests/Feature/ArdLinkKomponenteTest.php`, `tests/Feature/AiManifestGenerateCommandTest.php`; alte Tests des 1.x-Formats (`AiManifestRouteTest`, `AiManifestServiceTest`, `AiManifestBuilderTest`, `AiManifestCacheFingerprintTest`) werden dabei angepasst oder gelöscht
- [x] T2 (AK1–AK12) [senior] src/Ard/, Controller, Service, Provider, Config, Komponente, Befehl — Tests: dieselben Dateien
- [x] T3 (AK13) [junior] README, `Entscheidung` verlinken — Tests: `tests/Feature/ArdReadmeTest.php`
- [x] T4 NACHWEIS (Moderator): GATE, Paket-Tests, Lighthouse `ard-schema`/`llms-txt` gegen den Host-Server
- [ ] T5 REVIEW (Risiko kritisch)

Bestehende Tests: `tests/Unit/CapabilityEntryTest.php`, `tests/Unit/MechanismTest.php`

## Stand
T1–T4 erledigt (test-writer a38985129d36f6db2, coder-senior a1ddfc21dcc175947, GATE GRÜN 10:12: 44 Tests). Review am Ende des Stapels.

Offener Punkt a1ddfc21dcc175947: AK-fremd (1 Publisher/Namespace bereinigt folgt aus AK4; 2 datei absolut ist Config-Kommentar; 3 datei unlesbar 404 steht wörtlich in AK9; 4 ungültige Links übersprungen betrifft keine AK-Aussage; 5 Summary gleich „METHOD path“ nur Lighthouse-Warnung; 6 Cache: AK11 verlangt Fingerabdruck des Bodys; 7 composer.json illuminate/view,http: Folge-Task im Paket; 8 CHANGELOG.md: Doku-Writer nach Review; 9 README-Beispiele im Test: Backlog)
Abweichung a1ddfc21dcc175947: keine (Fehlalarm: Abweichungen-Zeile des Berichts lautet „keine“)
Annahme im Code: keine
Echtlauf: browser: Lighthouse 13.6.0 (agentic-browsing) in Chrome 156 gegen den Host-Server Port 8004: ard-schema Score 1 (Antwort {"entries":[]}), llms-txt notApplicable (404 ohne Config). llms.txt mit Beispiel-Config per CLI-Request der Host-App (ohne Server) und Lighthouse-Audit llms-txt: Score 1 (200, text/plain; charset=utf-8). ard.json mit zwei Routen und einem manuellen Eintrag gegen den ARD-Konformitätstest (ConformanceTester aus Lighthouse): 0 Fehler, 0 Warnungen.
Layout: entfällt (keine sichtbare Darstellung, die Komponente erzeugt nur ein <link> im head)

## Nachbesserung 2026-11-17 (automode): #1444 (sollte-Funde aus Review 0/2)
Branch `fix/ard-nacharbeit`. Spec-Lücke AK14 (Punkte 1–3) selbst freigegeben; Punkt 4 (Cache ohne Wirkung, AK11 dem Wortlaut nach erfüllt) bleibt: Entscheidung bei Joerg, Task bleibt dafür offen.
- [x] test-writer a9c3288499f970e4c (AK14) — Tests: `tests/Feature/ArdEintragTest.php`
- [x] [pro] coder-pro a1b2a3651f87ffedb (AK14) — GATE GRÜN 10:23, Paket 47 Tests grün
Offener Punkt a1b2a3651f87ffedb: keine
Abweichung a1b2a3651f87ffedb: keine (Fehlalarm: Abweichungen-Zeile des Berichts lautet „keine“)
Review: entfällt (Bugfix/Spec-Lücke ohne Sicherheitsbezug, kleine Änderung; Rule agent-lifecycle.md)

## Nachbesserung 2026-11-17 (automode): #1444 Punkt 4 (Cache entfernen, Entscheidung Joerg: A)
Branch `fix/ard-cache-entfernen`. Spec: AK11 ersetzt („Kein Cache“), AK12 ohne `cache_ttl` (Spec-Änderung nach Entscheidung des Users, Task-Protokoll #3111).
- [ ] test-writer (AK11, AK12) — Tests: `tests/Feature/AiManifestCacheFingerprintTest.php`, `tests/Feature/ArdEintragTest.php`, `tests/Feature/ArdReadmeTest.php`; `abdeckung.md` AK11/AK12 nachziehen
- [ ] [pro] Controller ohne Cache, `ArdManifest::fingerabdruck()` und Config `cache_ttl` entfernen, README und CHANGELOG nachziehen — Tests: dieselben Dateien plus `tests/Feature/ArdManifestTest.php`, `tests/Feature/AiManifestServiceTest.php`, `tests/Feature/AiManifestGenerateCommandTest.php`
- [x] test-writer a42944554a3753bf2 (AK11, AK12) — Commit 11b9416
- [x] [pro] coder-pro a01bb952e133ca99d (AK11, AK12) — GATE GRÜN 10:49, Paket 48 Tests grün
Offener Punkt a01bb952e133ca99d: keine
Abweichung a01bb952e133ca99d: keine (Fehlalarm: Abweichungen-Zeile des Berichts lautet „keine“)
Annahme im Code: keine
Echtlauf: entfällt (Entfernung von Cache-Code, ohne Oberfläche; Paket-Tests über Host-Testbench grün)
Review: entfällt (Entfernung eines wirkungslosen Caches, kein Sicherheitsbezug, keine neue Route, unter 50 Zeilen Produktivcode)
