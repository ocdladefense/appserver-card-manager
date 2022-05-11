# Payment Profile Manager
Manage customers' saved payment profiles(cards) on file.

## Description
The payment profile manager exposes CRUD operations and related UX to manage customers' saved payment methods on file.


## Routes
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
- Editing a field on salesforce - 
1. Make sure you are logged in to the app.  (username = "jbernal@ocdla.com.ocdpartial")
2. Set the values you want to change on the contact object on the block of code between lines 114 and 124 in module.php, and run the cards/show route.
3. In Salesforce Developer Console run this query:
- SELECT Id, Firstname, LastName, Ocdla_Member_Status__c, AuthorizeDotNetCustomerProfileId__c FROM Contact where Id = '003j000000rU9NvAAK'
