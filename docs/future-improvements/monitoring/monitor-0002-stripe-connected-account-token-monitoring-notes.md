# Stripe Connected Account Token Monitoring Notes

Stripe connected account token creation code now includes `TO-MONITOR-0002` comments stating that future monitoring code will need to be implemented.
These markers indicate where structured logs/metrics should be introduced once monitoring infrastructure is selected.

## Follow-ups
- Add monitoring around Stripe account token creation failures and latency.
- Replace raw upstream/internal error details returned to the frontend with monitored server-side handling.
