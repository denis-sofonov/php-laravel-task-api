# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtain a token from <code>POST /api/v1/login</code> or <code>POST /api/v1/register</code>, then send it as <code>Authorization: Bearer {token}</code>.
