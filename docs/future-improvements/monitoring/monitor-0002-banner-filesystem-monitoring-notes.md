# Banner Filesystem Monitoring Notes

Banner upload and publish code now includes `TO-MONITOR-0002` comments for future monitoring work.
These markers indicate where structured logs/metrics should be introduced for banner filesystem and publish-flow reliability.

## Follow-ups
- Add monitoring for banner staging, publish, and finalization failures (including commit directory collisions).
- Add monitoring for DB write failures after filesystem publish to track tolerated mismatch cases.
- Add monitoring for validation failures specific to banner upload payloads.
- Add latency and success/failure metrics for end-to-end banner upload requests.
