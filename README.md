Written for "Schwab Trader API - Individual" https://developer.schwab.com/products
<<<<<<< HEAD
Files get generated in your project folder: API responses "transactions.html" and Access Tokens "accessTokens.json"

For support: traderapi@schwab.com

*** open your web browser and trigger functions like this **
* https://domain.ext/schwab/?auth // allows you to grant authorization (on schwab.com) and run the functions below
* https://domain.ext/schwab/?quote&symbol=AAPL // gets the price of AAPL and various details
* https://domain.ext/schwab/?accountNumbers // gives your 64-character encoded account number
* https://domain.ext/schwab/?trade // edit JSON to buy or sell, etc.
* https://domain.ext/schwab/?getAccountOrders // there is a slight delay to this, check schwab.com to verify

*** Timings ***
* 30 seconds after auth to use the URL variable 'code' to create an access_token
* 30 minutes to use an access_token before refresh_token needed to new access_token
* 7 days to use the refresh_token before you need to re-authorize

Change c:/wamp64/priv/schwab/ in functions.php to your own private data folder (not in htdocs or www)
=======

Files get generated in your project folder: API responses "responses.html" and Access Tokens "accessTokens.json"

For support: traderapi@schwab.com
>>>>>>> a49b44263d771ea579fe235c26e5328faa1fc6cd
