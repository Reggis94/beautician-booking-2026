# Centralized JSON Request Handling

Source markers use `TO-IMPROVE-0002`.

Several HTTP controllers manually validate JSON request content and parse request payloads before building request DTOs.

## Follow-ups
- Centralize this logic so controllers do not duplicate JSON request content validation and payload parsing.
