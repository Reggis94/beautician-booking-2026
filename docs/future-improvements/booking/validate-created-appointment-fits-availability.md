# Validate Created Appointment Fits Availability

`CreateAppointmentByClientCommandHandler` currently checks that the requested start time is inside the professional's business time before creating an appointment.

The appointment repository then derives `end_dt` from the requested service duration. This can allow a booking to start inside availability while ending after the availability window.

Future improvement:
- Validate the full requested appointment range against pro availability before insertion.
- Reuse the requested service duration when computing the candidate appointment end.
- Reject bookings whose full range does not fit inside one availability window.

This should be implemented in the booking flow separately from the public free-ranges read endpoint.
