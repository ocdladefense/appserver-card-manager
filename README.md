# Card Manager
Manage customers' cards on file.

## Description
The card manager exposes CRUD operations and related UX to manage customers' cards on file.

## Existing functionality
See the `src/` directory for existing functionality.

## Routes
These routes need to be created together with their callback functions.
- `customer/%profileId` - `GET` - retrieve the customer's Authorize.net Profile, including a list of their cards on file.
- `customer/%profileId/card/%paymentProfileId` - `DELETE`
- `customer/%profileId/card/%paymentProfileId` - `POST`
- `customer/%profileId/card/new` - `POST`
