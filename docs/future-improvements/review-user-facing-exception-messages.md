# Review user-facing exception messages

Some controllers still return messages from broad exception catches, such as `\Throwable`, `\Exception`, or generic SPL exception types.

Review these responses and replace them with explicit, user-facing custom exceptions where the message is part of the HTTP contract. Internal, framework, infrastructure, and generic PHP exception messages should not be exposed to users.
