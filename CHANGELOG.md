# Changelog

## 0.1.0-alpha — 2026-10-05

- Initial alpha. Covers all 20+ TranslatorSage API routes.
- Namespaces exposed via `TsageClient` methods: `consumers`, `tts`, `stt`, `translate`, `dubbing`, `voices`, `sfx`, `voiceDesign`, `voiceChanger`, `dialogue`, `audioIsolation`, `align`, `diarize`, `agents`.
- `TranslatorSage\Sdk\Compat\ElevenLabsCompat` provides a two-line migration from the ElevenLabs PHP SDK.
- Zero external runtime dependencies (ext-curl + ext-json only).
