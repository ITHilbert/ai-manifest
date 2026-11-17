# Plan: ard-umbau

Branch: `feature/ard-umbau` (Paket-Repo ai-manifest, von Stand e545ebb) · Spec: `spec.md` · Dashboard-Task #1430
Spec und Plan selbst freigegeben (automode 2026-11-17-0939).

## Aufgaben
- [ ] T1 (AK1–AK12) test-writer — Tests: `tests/Feature/ArdManifestTest.php`, `tests/Feature/ArdEintragTest.php`, `tests/Feature/LlmsTxtTest.php`, `tests/Feature/ArdLinkKomponenteTest.php`, `tests/Feature/AiManifestGenerateCommandTest.php`; alte Tests des 1.x-Formats (`AiManifestRouteTest`, `AiManifestServiceTest`, `AiManifestBuilderTest`, `AiManifestCacheFingerprintTest`) werden dabei angepasst oder gelöscht
- [ ] T2 (AK1–AK12) [senior] src/Ard/, Controller, Service, Provider, Config, Komponente, Befehl — Tests: dieselben Dateien
- [ ] T3 (AK13) [junior] README, `Entscheidung` verlinken — Tests: `tests/Feature/ArdReadmeTest.php`
- [ ] T4 NACHWEIS (Moderator): GATE, Paket-Tests, Lighthouse `ard-schema`/`llms-txt` gegen den Host-Server
- [ ] T5 REVIEW (Risiko kritisch)

Bestehende Tests: `tests/Unit/CapabilityEntryTest.php`, `tests/Unit/MechanismTest.php`

## Stand
Spec, Entscheidung 0001 und Rückbau-Task #1433 geschrieben.
