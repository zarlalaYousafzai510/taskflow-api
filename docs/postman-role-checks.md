# Postman role and ownership checks

Start the API with `php artisan serve`, then use `http://127.0.0.1:8000/api` as the base URL. Run `php artisan migrate` first if this is a fresh database.

For registration requests, choose **POST** `{{baseUrl}}/register`, select **Body → raw → JSON**, and send the matching body below. Save the returned `token` as `customerToken`, `secondCustomerToken`, or `agentToken` in your Postman environment.

Customer body:

```json
{"name":"Customer One","email":"customer1@example.com","password":"password123","role":"customer"}
```

Register a second customer with a different email and name. Agent body:

```json
{"name":"Support Agent","email":"agent@example.com","password":"password123","role":"agent"}
```

For each protected request, set **Authorization → Bearer Token** to the indicated token. Postman adds the `Authorization: Bearer ...` header.

| Check | Method and URL | Token | Expected result |
| --- | --- | --- | --- |
| Create a ticket as customer one | `POST {{baseUrl}}/tickets` | `customerToken` | `201`; save the returned `id` as `customerTicketId` |
| Create a ticket as customer two | `POST {{baseUrl}}/tickets` | `secondCustomerToken` | `201`; save the returned `id` as `otherTicketId` |
| List tickets as customer one | `GET {{baseUrl}}/tickets` | `customerToken` | `200`; only customer one's tickets appear |
| Read own ticket | `GET {{baseUrl}}/tickets/{{customerTicketId}}` | `customerToken` | `200` |
| Try to read customer two's ticket | `GET {{baseUrl}}/tickets/{{otherTicketId}}` | `customerToken` | `404` |
| Try to update own ticket as customer | `PUT {{baseUrl}}/tickets/{{customerTicketId}}` | `customerToken` | `403` |
| List tickets as agent | `GET {{baseUrl}}/tickets` | `agentToken` | `200`; tickets from both customers appear |
| Read a customer's ticket as agent | `GET {{baseUrl}}/tickets/{{otherTicketId}}` | `agentToken` | `200` |
| Update a ticket as agent | `PUT {{baseUrl}}/tickets/{{otherTicketId}}` | `agentToken` | `200`; the ticket reflects the update |
| Request tickets without a token | `GET {{baseUrl}}/tickets` | none | `401` |

Use this body for ticket creation:

```json
{"title":"Login issue","description":"Cannot sign in"}
```

Use this body for the agent update:

```json
{"status":"closed"}
```

When creating the PUT request, choose **Body → raw → JSON** as well. The `role` field accepts only `customer` or `agent`; other values should fail validation.
