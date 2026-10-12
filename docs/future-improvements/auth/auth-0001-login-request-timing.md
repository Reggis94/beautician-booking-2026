# Login Request Timing Reveals Account Existence

## Context
`POST /api/auth/login-link` returns the same status and body whether the email belongs to a beautician or not.
For an existing beautician, the login email is sent synchronously through Mailchimp before the response, so the
request takes noticeably longer than for an unknown email.

## Improvement
Make both paths take a similar time, for example by sending the email after the response is flushed
(`kernel.terminate`) or through an asynchronous Messenger transport.

## Reason
Response time can be used to learn whether an email address belongs to a beautician.
