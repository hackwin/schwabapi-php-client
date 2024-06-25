<?php
/*

// BUY
{
  "orderType": "MARKET",
  "session": "NORMAL",
  "duration": "DAY",
  "orderStrategyType": "SINGLE",
  "orderLegCollection": [
   {
    "instruction": "SELL",
    "quantity": 1,
    "instrument": {
     "symbol": "INTC",
     "assetType": "EQUITY"
    }
   }
  ]
}

//  SELL
{
  "orderType": "MARKET",
  "session": "NORMAL",
  "duration": "DAY",
  "orderStrategyType": "SINGLE",
  "orderLegCollection": [
   {
    "instruction": "SELL",
    "quantity": 1,
    "instrument": {
     "symbol": "INTC",
     "assetType": "EQUITY"
    }
   }
  ]
}
 // LIMIT SELL
{
  "orderType": "LIMIT",
  "session": "NORMAL",
  "price": "41",
  "duration": "DAY",
  "orderStrategyType": "SINGLE",
  "orderLegCollection": [
   {
    "instruction": "SELL",
    "quantity": 1,
    "instrument": {
     "symbol": "VZ",
     "assetType": "EQUITY"
    }
   }
  ]
}

// BUY AT LIMIT THEN SELL AT LIMIT, "1st trigger sequence"
{
  "orderType": "LIMIT",
  "session": "NORMAL",
  "price": "39",
  "duration": "DAY",
  "orderStrategyType": "TRIGGER",
  "orderLegCollection": [
   {
    "instruction": "BUY",
    "quantity": 1,
    "instrument": {
     "symbol": "VZ",
     "assetType": "EQUITY"
    }
   }
  ],
  "childOrderStrategies": [
    {
    "orderType": "LIMIT",
    "session": "NORMAL",
    "price": "41",
    "duration": "DAY",
    "orderStrategyType": "SINGLE",
    "orderLegCollection": [
      {
        "instruction" : "SELL",
        "quantity": 1,
        "instrument": {
          "symbol": "VZ",
          "assetType": "EQUITY"
        }
      }
    ]
    }
  ]
}

// buy at market price and sell using a trailing stop $5 from the highest price

{
  "orderType": "MARKET",
  "session": "NORMAL",
  "duration": "DAY",
  "orderStrategyType": "TRIGGER",
  "orderLegCollection": [
   {
    "instruction": "BUY",
    "quantity": 1,
    "instrument": {
     "symbol": "NVDA",
     "assetType": "EQUITY"
    }
   }
  ],
  "childOrderStrategies": [
    {
    "orderType": "TRAILING_STOP",
    "session": "NORMAL",
    "stopPriceLinkBasis": "BID",
    "stopPriceLinkType": "VALUE",
    "stopPriceOffset": "5",
    "duration": "END_OF_WEEK",
    "orderStrategyType": "SINGLE",
    "orderLegCollection": [
      {
        "instruction" : "SELL",
        "quantity": 1,
        "instrument": {
          "symbol": "NVDA",
          "assetType": "EQUITY"
        }
      }
    ]
    }
  ]
}

 */

