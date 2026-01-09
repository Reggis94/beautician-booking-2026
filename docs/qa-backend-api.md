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

#### Failure cases

- Missing required fields should fail.

  ```bash
  curl -X POST "http://localhost:8000/api/pro/service/new" \
    -H "Content-Type: application/json" \
    -d '{
      "pro_id": 1,
      "name": ""
    }'
  ```

  Expect a `400` with validation errors.

- Invalid numeric values should fail.

  ```bash
  curl -X POST "http://localhost:8000/api/pro/service/new" \
    -H "Content-Type: application/json" \
    -d '{
      "pro_id": 0,
      "name": "Invalid Pro",
      "duration_min": -5,
      "price_cents": -1
    }'
  ```

  Expect a `400` with validation errors.

- Category does not exist should fail.

  ```bash
  curl -X POST "http://localhost:8000/api/pro/service/new" \
    -H "Content-Type: application/json" \
    -d '{
      "pro_id": 1,
      "name": "Missing Category",
      "category_id": 999999
    }'
  ```

  Expect a `400` with a category ownership/validation error.

- Category belongs to another pro should fail.

  ```bash
  curl -X POST "http://localhost:8000/api/pro/service/new" \
    -H "Content-Type: application/json" \
    -d '{
      "pro_id": 1,
      "name": "Other Pro Category",
      "category_id": 2
    }'
  ```

  Expect a `400` with a category ownership error.

### Service creation with category

1. Create a category for the pro.

   ```bash
   curl -X POST "http://localhost:8000/api/pro/service/category/new" \
     -H "Content-Type: application/json" \
     -d '{
       "pro_id": 1,
       "name": "Hair Care"
     }'
   ```

2. Use the returned `service_category_id` to create a service in that category.

   ```bash
   curl -X POST "http://localhost:8000/api/pro/service/new" \
     -H "Content-Type: application/json" \
     -d '{
       "pro_id": 1,
       "name": "Hair Wash",
       "category_id": 1
     }'
   ```

3. Verify the response is `201` and the service row stores the category ID.

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

#### Failure cases

- Service not owned by the pro should fail.

  ```bash
  curl -X DELETE "http://localhost:8000/api/pro/service/delete" \
    -H "Content-Type: application/json" \
    -d '{
      "service_id": 1,
      "pro_id": 999
    }'
  ```

  Expect a `403` with an ownership error.

- Missing identifiers should fail.

  ```bash
  curl -X DELETE "http://localhost:8000/api/pro/service/delete" \
    -H "Content-Type: application/json" \
    -d '{}'
  ```

  Expect a `403` with an error message.
