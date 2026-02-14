# Timezone Monitoring Notes

Timezone-related code now includes `TO-MONITOR-*` comments stating that future monitoring code will need to be implemented.
These markers indicate where structured logs/metrics should be introduced once monitoring infrastructure is selected.

## Follow-ups
- Add monitoring around geocoding and timezone resolution failures and latency.
- Add monitoring for timezone update writes (success/failure, affected rows).
- Add monitoring for validation errors that block timezone updates or geocoding requests.
