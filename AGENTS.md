# Project conventions

- This repository contains only the complete-source WordPress port. Start from
  the original archive, following the Tempest/Laravel full-file patch workflow.
  Use `scripts/prepare-wordpress.py`, `patches/full-source/` and
  `elephc/full-source/fixtures/`. Never extract selected WordPress function ranges.
- Preserve the complete original archive inventory and original, patch and
  resulting complete-file checksums. Never edit dependencies under `upstream/`.
- Keep the required Elephc changes in `patches/compiler/wordpress-main.patch`,
  and the incremental `patches/compiler/wordpress-web.patch`, with their pinned
  base revision and complete SDK instructions. Distinguish
  compiler adaptations from WordPress patches and runtime fixtures.
- Run application code, Elephc and PHP checks directly on the host. Do not use
  DDEV or Docker. The fixture uses isolated in-memory SQLite and must never
  connect to a shared database.
- The verified targets are the native CLI bootstrap and bounded HTTP rendering
  of the homepage, three seeded posts and five runtime documents. Use
  `127.0.0.1:9000` for the local listener. Preserve request isolation and
  distinguish this entry from full theme routing, administration and
  persistent installations.
- Repository scripts use tools on PATH or explicit compiler arguments, without
  personal paths, required version managers, agent helpers or automatic workload
  scheduling. Apply machine-wide build scheduling outside repository scripts.
- Validate against unmodified PHP with `scripts/test-wordpress-bootstrap.py`
  and `scripts/test-wordpress-controls.py`. Verify native HTTP with
  `scripts/test-wordpress-web.py`. Retain all 47 primary control groups.
  Reproduce from source with `scripts/test-wordpress-clean-room.py`; `--native`
  additionally rebuilds and verifies the complete native bootstrap.
- Keep generated sources, SDKs, executables and local reports under
  `elephc/build/`. Preserve useful current output while removing obsolete trials.
- Keep unsupported native APIs and finite fixture collaborators explicit. Compiler
  traversal, native execution and complete HTTP/admin/plugin coverage are distinct.
  Never report copied source counts as native execution percentages.
- Keep the README, current porting log, provenance, execution boundaries and Code
  Journal consistent. Do not restore superseded subsystem or hybrid experiments.
- Write demo copy, repository documentation and maintained examples in English.
  Preserve Unicode coverage in regression fixtures.
- Use concise English lowercase Conventional Commits without Co-Authored-By.
  Use gh for supported GitHub operations. Never use em dashes.
