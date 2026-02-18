# Banner Upload API (Frontend)

## Endpoint
- Method: `POST`
- URL: `/api/pro/banner/create/upload-image`
- Content-Type: `multipart/form-data`

## Purpose
Creates banner images for one professional (`pro`) in a single request.

Important behavior:
- The pro must exist.
- Upload is allowed only when the pro has no active banners yet.
- You can upload 1 to 20 images in one call.
- On success, response has no body.

## Request Fields (Canonical)
Use these canonical field names from frontend:
- `pro_id`: integer, required, must be `> 0`
- `images[]`: file array, required, 1..20 files, each image max 5 MB
- `order_numbers[]`: integer array, required, 1..20 values

Order number rules:
- Must have the same number of items as `images[]`
- Each value must be between `1` and `20`
- Values must be unique

## Backward-Compatible Aliases (Accepted by API)
The endpoint currently also accepts these aliases:
- `proId` instead of `pro_id`
- `files[]` or flattened uploaded files instead of `images[]`
- `orderNumbers[]` instead of `order_numbers[]`

Frontend recommendation: always send canonical names (`pro_id`, `images[]`, `order_numbers[]`).

## Success Response
- Status: `201 Created`
- Body: no response body

## Error Response
- Status: `400 Bad Request`
- Body format:

```json
{
  "errors": [
    "message 1",
    "message 2"
  ]
}
```

Common error messages:
- `pro_id must be a positive integer.`
- `images must contain at least 1 file.`
- `images may contain at most 20 files.`
- `each file must be a valid image.`
- `each file must be at most 5 MB.`
- `order_numbers must contain at least 1 value.`
- `order_numbers may contain at most 20 values.`
- `order_numbers must have the same number of items as images.`
- `order_numbers must be unique.`
- `each order number must be between 1 and 20.`
- `banners already exist for this pro.`
- `pro not found.`

## Postman Example
- Method: `POST`
- URL: `{{baseUrl}}/api/pro/banner/create/upload-image`
- Headers:
  - `Accept: application/json`
- Body type: `form-data`
  - `pro_id` (Text): `123`
  - `images[]` (File): `<select file 1>`
  - `images[]` (File): `<select file 2>`
  - `order_numbers[]` (Text): `1`
  - `order_numbers[]` (Text): `2`

## Frontend Fetch Example
```javascript
const formData = new FormData();
formData.append('pro_id', String(proId));

files.forEach((file, index) => {
  formData.append('images[]', file);
  formData.append('order_numbers[]', String(index + 1));
});

const response = await fetch('/api/pro/banner/create/upload-image', {
  method: 'POST',
  body: formData,
  headers: {
    Accept: 'application/json',
  },
});

if (!response.ok) {
  const data = await response.json();
  throw new Error((data?.errors || ['Banner upload failed.']).join(' '));
}
```

## Integration Notes
- Do not manually set `Content-Type` for `FormData`; the browser sets multipart boundaries.
- If your UI supports drag-and-drop with multiple files, keep `order_numbers[]` aligned with the file order you submit.
- Since success has no response body, use HTTP status (`201`) as the success signal.
