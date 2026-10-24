# Taste

## Workflow
- Runs the backend locally with `php -d variables_order=GCPS artisan serve --host=0.0.0.0 --port=8000` (not the default `artisan serve` on an arbitrary port), and expects the LAN device IP kept in sync in `vite.config` and `constants/api.ts` when the network changes. Confidence: 0.6
- Expects the agent to read and comprehend the entire codebase (structure, docs, models, routes, config) before starting work, so it understands the project's true purpose. Confidence: 0.6

## Product & UI
- Wants a feature's design/UI carried with parity across every client — mobile user pages, mobile + web IT, and admin — rather than implemented on a single platform. Confidence: 0.5
