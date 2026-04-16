---
status: investigating
trigger: "Investigate 14 test failures in MonitorImportTest and DatabaseBackupControllerTest. All returning 500 errors. I have already changed LOG_CHANNEL to single in phpunit.xml but the logs don't seem to capture the test errors."
created: 2025-05-15T10:00:00Z
updated: 2025-05-15T10:00:00Z
---

## Current Focus

hypothesis: Initial evidence gathering - Running the failing tests to see the actual error output.
test: php artisan test --filter=MonitorImportTest,DatabaseBackupControllerTest
expecting: Detailed error output or 500 response details.
next_action: Run the failing tests.

## Symptoms

expected: Tests should pass with 200/302 responses.
actual: 14 tests failing with 500 errors.
errors: 500 Internal Server Error (no details in logs yet).
reproduction: Run `MonitorImportTest` and `DatabaseBackupControllerTest`.
started: Recently.

## Eliminated

## Evidence

## Resolution

root_cause:
fix:
verification:
files_changed: []
