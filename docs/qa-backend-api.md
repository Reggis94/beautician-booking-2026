## Backend API QA testing

### Service creation (inactive by default)

1. Send a request to create a service.

   ```bash
   curl -X POST "http://localhost:8000/api/pro/service/new" \
     -H "Content-Type: application/json" \
     -d '{
       "pro_id": 1,
       "name": "Deluxe Cut",
       "category_id": null,
       "description": "Optional description",
       "duration_min": 45,
       "price_cents": 5500
     }'
   ```

2. Verify the response is `201`.
3. Confirm the row is inserted with `is_active = false` and `deleted_at` as `NULL`.

### Service deletion (soft delete)

1. Send a request to delete a service.

   ```bash
   curl -X DELETE "http://localhost:8000/api/pro/service/delete" \
     -H "Content-Type: application/json" \
     -d '{
       "service_id": 1,
       "pro_id": 1
     }'
   ```

2. Verify the response is `204`.
3. Confirm the row remains, but `deleted_at` is set.
