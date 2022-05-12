# Payment Profile Manager
Manage customers' saved payment profiles(cards) on file.

## Description
The payment profile manager exposes CRUD operations and related UX to manage customers' saved payment methods on file.


## Routes
- `customer/%contactId/save` - `GET` - Save the Contact's customer Profile ID to Salesforce.
- `cards/show` - `GET` - Show all of the saved payment profiles for the current user.
- `card/create` - `GET` - Display a form so that the current user can create a new payment profile.
- `card/edit/%id` - `GET` - Display a form so that the current user can edit an existing payment profile.
- `card/save` - `POST` - Save a new payment profile, or changes made to an existing payment profile.
- `card/delete/%id` - `DELETE` - Delete a payment profile.


## Required Constants/Settings
- AUTHORIZE_DOT_NET_MERCHANT_ID (string)
- AUTHORIZE_DOT_NET_TRANSACTION_KEY (string)
- AUTHORIZE_DOT_NET_USE_PRODUCTION_ENDPOINT (boolean)

## Authorize.net endpoints
- SANDBOX - https://apitest.authorize.net
- PRODUCTION - https://api2.authorize.net


## Testing Scripts
### Updating a customer's Profile ID.
- Editing a field on Salesforce - 
1. Don't login to the app.
2. Run the <code>customer/003j000000rU9NvAAK/save</code> route.
3. Validate that the route executes without any errors.
4. Validate that the UPDATE was successful:
    - In the Salesforce Developer Console run this query:
        
            SELECT Id, Firstname, LastName, Ocdla_Member_Status__c, AuthorizeDotNetCustomerProfileId__c FROM Contact WHERE Id = '003j000000rU9NvAAK'
