# 0001 — ARD statt eigenem Format, llms.txt im Paket

Status: gilt (Joerg 2026-11-17, Dashboard #1430) · Ersetzt: das Format `/.well-known/ai-manifest.json` (Paket 1.x)

## Entscheidung
- Das Paket wird der Sammelpunkt der Auffindbarkeit durch Agenten: ARD-Manifest (`/.well-known/ard.json`, Spec ARD v0.91) und `llms.txt`.
- Das eigene Format `ai-manifest.json` entfällt. `->aiManifest()` an Routen erzeugt ARD-Einträge. Das ändert die Schnittstelle: Major-Version 2.0.0 (Tag nur auf Ansage von Joerg).
- WebMCP (Formular-Anmerkungen) gehört nicht ins Paket, sondern zu den Formularen (kontaktformular, #1425).

## Begründung und Wahl im Einzelnen
- **Eintragsart der Routen:** ARD-Medientypen für Agenten-Artefakte (MCP-Karte, A2A-Karte …) passen nicht auf eine einzelne Route. Gewählt: ein Erweiterungstyp `application/vnd.ithilbert.ai-manifest-capability+json` mit der Capability inline (`data`). Der Konformitätstest meldet für `application/*`-Erweiterungen nur einen Hinweis (info), keine Warnung.
- **Firmen-Website ohne API:** `{"entries":[]}` ist gültig und ohne Warnung (Quelle: ARD-Konformitätstest in Lighthouse 13.6.0, `third-party/ard/ard.js`; per Lauf bestätigt, siehe Spec AK5). Ein erfundener Eintrag wäre schlechter: `text/markdown` für llms.txt ist kein Standardtyp und würde warnen. Ohne Routen und Einträge liefert das Paket deshalb eine leere Liste.
- **Content-Type:** Die Spec schreibt keinen vor. Gewählt `application/ai-catalog+json; charset=utf-8` — der Typ steht in der Liste der Standard-Medientypen des Konformitätstests, Hugging Face liefert ihn ebenso.
- **llms.txt als `text/plain; charset=utf-8`:** Browser laden `text/markdown` herunter, statt es anzuzeigen; llmstxt.org verlangt keinen Typ.
- **Übergangspfad `/.well-known/ai-catalog.json`:** gleicher Inhalt, per Config abschaltbar. Lighthouse 13.6.0 prüft `ard.json` zuerst und meldet den alten Pfad nur als Warnung; der Pfad dient nur dem Fall, dass PageSpeed Insights noch den alten Namen prüft. Rückbau: Dashboard #1433.
- **Gekapselt:** Alle ARD-Teile (Eintrag, Manifest-Aufbau, Medientypen, Pfade) liegen in `src/Ard/`; ändert sich der Entwurf, bleibt die Änderung dort.
