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
