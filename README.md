## PHP Client for "Schwab Trader API - Individual" 
* https://developer.schwab.com/products/trader-api--individual

Files you must create (from your Schwab App settings page):
* Private folder
* "appParameters.json"
```
{
	"client_id":"32 char", 
	"client_secret":"16 char",
	"redirect_uri":"https://domain.ext/folder/"
}
```

Files that get generated you can check for debugging: 
* API requests and responses: "transactions-log.html"
* Access Tokens: "accessTokens.json"
* Account Number: "accountNumbers.json"
  
For support: traderapi@schwab.com

### Trigger functions like this from your web browser
* https://domain.ext/schwab/?auth // grant authorization (on schwab.com) and run the functions below
* https://domain.ext/schwab/?quote&symbol=AAPL // gets the price of AAPL and various details
* https://domain.ext/schwab/?accountNumbers // gives your 64-character encoded account number (needed below)
* https://domain.ext/schwab/?trade // edit JSON to buy or sell, etc.
* https://domain.ext/schwab/?getAccountOrders // there is a slight delay to this, check schwab.com to verify

### Timings
* 30 seconds after finishing "auth" you must use the URL variable 'code' to create an access_token
* 30 minutes until access_token expires before refresh_token needed to get new access_token
* 7 days to use the refresh_token before you need to re-auth

### Notes
* Change "c:/wamp64/priv/schwab/" in functions.php to your own private data folder (not inside htdocs or www)
