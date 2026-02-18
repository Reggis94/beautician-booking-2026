# Pro 0001 - Use Public ProNotFound Exception

`TO-PRO-0001`

## Context
`App\ProfilePro\Public\Exception\ProNotFound` now exists as a shared exception for "pro not found" cases.
Some areas still throw framework-specific or generic exceptions (`NotFoundHttpException`, `DomainException`) with equivalent meaning.

## Follow-ups
- Replace generic "pro not found" throws with `App\ProfilePro\Public\Exception\ProNotFound` where applicable.
- Align exception-to-HTTP mapping so controllers can convert `ProNotFound` consistently to the expected response.
- Remove `TO-PRO-0001` comments after migration is complete.
