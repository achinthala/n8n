
# Description
This module will allow admin users to run a shipping quote against all the currently supported LTL carriers. 
There are 3 different types of rate requests possible:
- Simple: Used to get a quote for sending X amount of weight from point A to point B
- Product: This is a shipping rule based quote.  The quote is for sending X number of product from point A to point B
- Quote: This is a complete request for all line items on a specific quote.

The UI for this module will be found in the admin portal.  It will be under Sales -> Operations -> Shipping Rates.  
Clicking on that link, the admin user will be taken to the Shipping Rates screen.  This is the screen where the user
can receive the different kind of shipping rates.

# UI Elements
The Shipping Rates screens will consist of 2 sections.  The first section will contain the UI elements required to
capture the user inputs and the second section will contain the results.

There are 3 different types of rate requests possible.  The user will select the type of rate request from a dropdown 
list box with the following options:  simple,product, quote.  The fields required will vary based on the selected type
of rate request.  Even thought the inputs will vary, the results table will always be the same format.

## Simple Request
A Simple Rate request allows the user to enter in basic information such as ship from, ship to, total weight and select
shipping options.  This will return the results from each carrier.

### Input Fields

| Property              | UI Element | Required |
|-----------------------|------------|----------|
| Ship To Zipcode       | TextBox    | Yes      |
| Ship To Street        | TextBox    | No       |
| Ship to City          | TextBox    | No       |
| Ship To State         | TextBox    | No       |
|                       |            |          |
| Ship From Zipcode     | TextBox    | Yes      |
| Ship From Street      | TextBox    | No       |
| Ship From City        | TextBox    | No       |
| Ship From State       | TextBox    | No       |
|                       |            |          |
| Residential           | Checkbox   | Yes      |
| Lift Gate Required    | Checkbox   | Yes      |
| Delivery Notification | Checkbox   | Yes      |
| Transaction Id        | Textbox    | No       |
|                       |            |          |
| Weight Class          | Dropdown   | Yes      |
| Total Weight          | TextBox    | Yes      |


## Product Rate Request
This request lets the user select the specific product(s) that they would like to include in the rate request.  For this
type of request, the user would enter the ship from, ship to, shipping options, and the product and quantity to be
shipped.  (Note: please see the business rules section for more details on how the Weight property will work)

### Input Fields

| Property              | UI Element | Required |
|-----------------------|------------|----------|
| Ship To Zipcode       | TextBox    | Yes      |
| Ship To Street        | TextBox    | No       |
| Ship to City          | TextBox    | No       |
| Ship To State         | TextBox    | No       |
|                       |            |          |
| Residential           | Checkbox   | Yes      |
| Lift Gate Required    | Checkbox   | Yes      |
| Delivery Notification | Checkbox   | Yes      |
| Transaction Id        | TextBox    | No       |
|                       |            |          |
| Product Name          | Dropdown   | Yes      |
| Sku                   | Dropdown   | Yes      |
| Unit Weight           | TextBox    | Yes      |
| Quantity              | TextBox    | Yes      |

## Quote Rate Request

### Input Fields
| Property              | UI Element | Required |
|-----------------------|------------|----------|
| Quote Number          | TextBox    | Yes      |

### Results
The results section will display the API response status code, the result IsSuccessful flag and the result message.  The
result data will be shown in a table that looks like this:

| Ship From Zip | Carrier | Cost | Charge | Message | Line Item | Vendor |
|---------------|---------|------|--------|---------|-----------|--------|


# Business Rules

- Supported weight classes are: [CLASS_50,CLASS_55,CLASS_60,CLASS_65,CLASS_70,CLASS_77_5,CLASS_85,CLASS_92_5,CLASS_100,
  CLASS_110,CLASS_125,CLASS_150,CLASS_175,CLASS_200,CLASS_250,CLASS_300,CLASS_400,CLASS_500,] 
- The following checkboxes will be checked  by default: Residential, Lift Gate Required, Delivery Notification
- The following Weight related TextBoxes will only accept integers: Total Weight, Unit Weight
- The Product Name dropdown
  - Contain all the configurable products
  - Will be in ascending order
  - Allow the user to start typing and have the list filter to match what they are typing
- The Sku dropdown
  - Contains all the simples from the configurable selected in the Product Name dropdowm
  - Will be in ascending order
  - Allow the user to start typing and have the list filter to match what they are typing
- The Unit Weight TextBox
  - Will be set to the unit weight of the selected Sku
  - Will be able to be overriden by the user
  - If the selected Sku unit weight or the manually entered weight is a decimal, round up to the nearest integer.

## Mappings
### Input
### Simple Request
- TransactionId: Transaction Id TextBox
- ShipToAddress
  - .Street: Ship To Street TextBox
  - .City: Ship To City TextBox
  - .State: Ship To State TextBox
  - .ZipCode: Ship To Zipcode TextBox
- DeliveryOptions
  - IsResidential: Residential Checkbox
  - IsLiftGateRequired: Lift Gate Required Checkbox
  - DeliveryNotification: Transaction Id Checkbox
- WeightClass: Weight Class Selected Value
- TotalWeight: Total Weight TextBox

### Product Request
- TransactionId: Transaction Id TextBox
- ShipToAddress
    - .Street: Ship To Street TextBox
    - .City: Ship To City TextBox
    - .State: Ship To State TextBox
    - .ZipCode: Ship To Zipcode TextBox
- DeliveryOptions
    - IsResidential: Residential Checkbox
    - IsLiftGateRequired: Lift Gate Required Checkbox
    - DeliveryNotification: Transaction Id Checkbox
- LineItems[0]
  - Id: 1,
  - Sku: Sku Selected Value
  - UnitWeight: Unit Weight TextBox
  - Quantity: Quantity TextBox

### Quote Request
- TransactionId: Either the Transaction Id TextBox or the Quote Number
- ShipToAddress
    - .Street: Ship To Street TextBox
    - .City: Ship To City TextBox
    - .State: Ship To State TextBox
    - .ZipCode: Ship To Zipcode TextBox
- DeliveryOptions
    - IsResidential: Residential Checkbox
    - IsLiftGateRequired: Lift Gate Required Checkbox
    - DeliveryNotification: Transaction Id Checkbox

For each line item in the cart:
- LineItems[n]
    - Id: line item id,
    - Sku: line item sku
    - UnitWeight: line item unit weight
    - Quantity: line item quantity

### Output
- For each LineItemShipments:
  - carrierName: Carrier
  - cost: Cost
  - charge: Charge
  - message: Message
  - lineItem:Id Line Item
  - fulfilmentLocation.name: Vendor
  - fulfilmentLocation.zipCode: Ship From Zip 

# Shipping Rates API Interface
- Shipping Rates API
    - root url: https://api.flooringinc.net/shipping/rate
    - simple request endpoint: /simple
    - product request endpoint: /all
    - quote request endpoint: /all
    - Header:
      - x-api-key 4077a2edec3446ef897ca66e7fcc5881

## /shipping/rate/simple example:
Request Body:
```angular2html
{ 
  "pickupFrom" : {
    "address":
    {
        "street": "",
      "city":"Gilbert",
      "state": "Az",
      "zipCode":"85233"
    }
  },
  "deliverTo": {
      "street": "",
      "city":"Gilbert",
      "state": "Az",
      "zipCode":"85233"
  },
  "DeliveryOptions": {
    "DeliveryNotification": true,
	"IsLiftGateRequired": true,
	"IsResidential": true
  },
  "weightClass": "CLASS_50",
  "packages": [
    {
        "units": 10,
        "unitWeight": 25
    }
  ]

}
```

Response Body:
```angular2html
{
    "data": [
        {
            "isValid": false,
            "message": "the call to the SAIA api timed out after: 10 seconds",
            "pickUpFrom": null,
            "shipToAddress": {
                "street": "",
                "street2": null,
                "city": "Gilbert",
                "state": "Az",
                "zipCode": "85233",
                "country": "US"
            },
            "carrier": "SAIA",
            "actualCarrierName": "SAIA",
            "packageRates": []
        },
        {
            "isValid": true,
            "message": null,
            "pickUpFrom": {
                "name": null,
                "address": {
                    "street": "",
                    "street2": null,
                    "city": "Gilbert",
                    "state": "Az",
                    "zipCode": "85233",
                    "country": "US"
                }
            },
            "shipToAddress": {
                "street": "",
                "street2": null,
                "city": "Gilbert",
                "state": "Az",
                "zipCode": "85233",
                "country": "US"
            },
            "carrier": "R&L Carrier",
            "actualCarrierName": "R&L Carrier",
            "packageRates": [
                {
                    "package": {
                        "lineItemId": null,
                        "units": 10,
                        "productType": "Standard",
                        "unitWeight": 25,
                        "dollarValue": 0,
                        "serviceLevel": "Standard",
                        "lineItemType": null,
                        "applyUpCharge": true,
                        "upCharge": null
                    },
                    "cost": 165.39,
                    "charge": 0
                }
            ]
        },
        {
            "isValid": true,
            "message": null,
            "pickUpFrom": {
                "name": null,
                "address": {
                    "street": "",
                    "street2": null,
                    "city": "Gilbert",
                    "state": "Az",
                    "zipCode": "85233",
                    "country": "US"
                }
            },
            "shipToAddress": {
                "street": "",
                "street2": null,
                "city": "Gilbert",
                "state": "Az",
                "zipCode": "85233",
                "country": "US"
            },
            "carrier": "Global Tranz",
            "actualCarrierName": "Global Tranz",
            "packageRates": [
                {
                    "package": {
                        "lineItemId": null,
                        "units": 10,
                        "productType": "Standard",
                        "unitWeight": 25,
                        "dollarValue": 0,
                        "serviceLevel": "Standard",
                        "lineItemType": null,
                        "applyUpCharge": true,
                        "upCharge": null
                    },
                    "cost": 165.59,
                    "charge": 0
                }
            ]
        },
        {
            "isValid": true,
            "message": null,
            "pickUpFrom": {
                "name": null,
                "address": {
                    "street": "",
                    "street2": null,
                    "city": "Gilbert",
                    "state": "Az",
                    "zipCode": "85233",
                    "country": "US"
                }
            },
            "shipToAddress": {
                "street": "",
                "street2": null,
                "city": "Gilbert",
                "state": "Az",
                "zipCode": "85233",
                "country": "US"
            },
            "carrier": "Fedex Freight Economy",
            "actualCarrierName": "Fedex Freight Economy",
            "packageRates": [
                {
                    "package": {
                        "lineItemId": null,
                        "units": 10,
                        "productType": "Standard",
                        "unitWeight": 25,
                        "dollarValue": 0,
                        "serviceLevel": "Standard",
                        "lineItemType": null,
                        "applyUpCharge": true,
                        "upCharge": null
                    },
                    "cost": 1042.24,
                    "charge": 0
                }
            ]
        }
    ],
    "isSuccessful": true,
    "message": null
}
```

## /shipping/rate/all example:
Request Body:
```angular2html
{ 
   "transactionId":"12345-c",
  "shipToAddress": {
      "street": "",
      "city":"Gilbert",
      "state": "Az",
      "zipCode":"85233"
  },
  "DeliveryOptions": {
    "DeliveryNotification": true,
	"IsLiftGateRequired": true,
	"IsResidential": true
  },
  "lineItems": [
    {
      "Id": "7665210",
      "Sku": "14003_20905_89589",
      "UnitWeight": 60,
      "Quantity": 20
    }
  ]
}
```

Response Body:
```angular2html
{
    "data": {
        "lineItemShipments": [
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 253.64,
                "charge": 390.22,
                "fulfillmentLocation": {
                    "name": "Athletic Rubber Converting LLC",
                    "address": {
                        "street": null,
                        "street2": null,
                        "city": "Surprise",
                        "state": "AZ",
                        "zipCode": "85379",
                        "country": "US"
                    }
                },
                "carrierName": "SAIA"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 165.39,
                "charge": 254.45,
                "fulfillmentLocation": {
                    "name": "Athletic Rubber Converting LLC",
                    "address": {
                        "street": null,
                        "street2": null,
                        "city": "Surprise",
                        "state": "AZ",
                        "zipCode": "85379",
                        "country": "US"
                    }
                },
                "carrierName": "R&L Carrier"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 172.93,
                "charge": 266.05,
                "fulfillmentLocation": {
                    "name": "Athletic Rubber Converting LLC",
                    "address": {
                        "street": null,
                        "street2": null,
                        "city": "Surprise",
                        "state": "AZ",
                        "zipCode": "85379",
                        "country": "US"
                    }
                },
                "carrierName": "Global Tranz"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 1565.56,
                "charge": 2408.55,
                "fulfillmentLocation": {
                    "name": "Athletic Rubber Converting LLC",
                    "address": {
                        "street": null,
                        "street2": null,
                        "city": "Surprise",
                        "state": "AZ",
                        "zipCode": "85379",
                        "country": "US"
                    }
                },
                "carrierName": "Fedex Freight Economy"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 620.64,
                "charge": 954.83,
                "fulfillmentLocation": {
                    "name": "Athletic Rubber Converting LLC",
                    "address": {
                        "street": "2025 E. Norse Ave",
                        "street2": null,
                        "city": "Cudahy",
                        "state": "WI",
                        "zipCode": "53110",
                        "country": "US"
                    }
                },
                "carrierName": "SAIA"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 383.64,
                "charge": 590.22,
                "fulfillmentLocation": {
                    "name": "Athletic Rubber Converting LLC",
                    "address": {
                        "street": "2025 E. Norse Ave",
                        "street2": null,
                        "city": "Cudahy",
                        "state": "WI",
                        "zipCode": "53110",
                        "country": "US"
                    }
                },
                "carrierName": "R&L Carrier"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 1320.84,
                "charge": 2032.06,
                "fulfillmentLocation": {
                    "name": "Athletic Rubber Converting LLC",
                    "address": {
                        "street": "2025 E. Norse Ave",
                        "street2": null,
                        "city": "Cudahy",
                        "state": "WI",
                        "zipCode": "53110",
                        "country": "US"
                    }
                },
                "carrierName": "Global Tranz"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 5117.14,
                "charge": 7872.52,
                "fulfillmentLocation": {
                    "name": "Athletic Rubber Converting LLC",
                    "address": {
                        "street": "2025 E. Norse Ave",
                        "street2": null,
                        "city": "Cudahy",
                        "state": "WI",
                        "zipCode": "53110",
                        "country": "US"
                    }
                },
                "carrierName": "Fedex Freight Economy"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 424.95,
                "charge": 653.77,
                "fulfillmentLocation": {
                    "name": "US Rubber",
                    "address": {
                        "street": "1231 Lincoln St.",
                        "street2": null,
                        "city": "Colton",
                        "state": "CA",
                        "zipCode": "92324",
                        "country": "US"
                    }
                },
                "carrierName": "SAIA"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 201.29,
                "charge": 309.68,
                "fulfillmentLocation": {
                    "name": "US Rubber",
                    "address": {
                        "street": "1231 Lincoln St.",
                        "street2": null,
                        "city": "Colton",
                        "state": "CA",
                        "zipCode": "92324",
                        "country": "US"
                    }
                },
                "carrierName": "R&L Carrier"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 215.42,
                "charge": 331.42,
                "fulfillmentLocation": {
                    "name": "US Rubber",
                    "address": {
                        "street": "1231 Lincoln St.",
                        "street2": null,
                        "city": "Colton",
                        "state": "CA",
                        "zipCode": "92324",
                        "country": "US"
                    }
                },
                "carrierName": "Global Tranz"
            },
            {
                "isValid": true,
                "message": null,
                "lineItemId": "7665210",
                "cost": 2334.23,
                "charge": 3591.12,
                "fulfillmentLocation": {
                    "name": "US Rubber",
                    "address": {
                        "street": "1231 Lincoln St.",
                        "street2": null,
                        "city": "Colton",
                        "state": "CA",
                        "zipCode": "92324",
                        "country": "US"
                    }
                },
                "carrierName": "Fedex Freight Economy"
            }
        ]
    },
    "isSuccessful": true,
    "message": null
}
```



## Swagger
```angular2html
{
    "openapi": "3.0.1",
    "info": {
      "title": "Shipping Rates API",
      "description": "API for calculating shipping rates across multiple carriers",
      "version": "v1"
    },
    "paths": {
        "/shipping/rate/all": {
            "post": {
                "tags": [
                "AllLineItemShipping"
                ],
                "requestBody": {
            "content": {
                "application/json": {
            "schema": {
                "$ref": "#/components/schemas/RulesBasedShippingQuoteRequest"
                }
                },
                "text/json": {
            "schema": {
                "$ref": "#/components/schemas/RulesBasedShippingQuoteRequest"
                }
                },
                "application/*+json": {
            "schema": {
                "$ref": "#/components/schemas/RulesBasedShippingQuoteRequest"
                }
                }
                }
                },
                "responses": {
            "200": {
                "description": "OK"
                }
                }
                }
                },
                "/shipping/rate/cheapest": {
            "post": {
                "tags": [
                "CheapestLineItemShipping"
                ],
                "requestBody": {
            "content": {
                "application/json": {
            "schema": {
                "$ref": "#/components/schemas/RulesBasedShippingQuoteRequest"
                }
                },
                "text/json": {
            "schema": {
                "$ref": "#/components/schemas/RulesBasedShippingQuoteRequest"
                }
                },
                "application/*+json": {
            "schema": {
                "$ref": "#/components/schemas/RulesBasedShippingQuoteRequest"
                }
                }
                }
                },
                "responses": {
            "200": {
                "description": "OK"
                }
                }
                }
                },
                "/shipping/rate/simple": {
            "post": {
                "tags": [
                "SimpleShipping"
                ],
                "requestBody": {
            "content": {
                "application/json": {
            "schema": {
                "$ref": "#/components/schemas/CustomShipmentQuoteRequest"
                }
                },
                "text/json": {
            "schema": {
                "$ref": "#/components/schemas/CustomShipmentQuoteRequest"
                }
                },
                "application/*+json": {
            "schema": {
                "$ref": "#/components/schemas/CustomShipmentQuoteRequest"
                }
                }
                }
                },
                "responses": {
            "200": {
                "description": "OK"
                }
                }
                }
                }
        },
"components": {
"schemas": {
    "Address": {
        "type": "object",
        "properties": {
            "street": {
                "type": "string",
                "nullable": true
            },
    "street2": {
      "type": "string",
      "nullable": true
    },
    "city": {
        "type": "string",
            "nullable": true
        },
"state": {
"type": "string",
"nullable": true
},
"zipCode": {
"type": "string",
"nullable": true
},
"country": {
"$ref": "#/components/schemas/Countries"
}
    },
"additionalProperties": false
},
"Countries": {
"enum": [
"US",
"CA"
],
"type": "string"
},
"CustomShipmentQuoteRequest": {
"type": "object",
"properties": {
    "pickUpFrom": {
        "$ref": "#/components/schemas/FulfillmentLocation"
    },
    "pickUpDate": {
      "type": "string",
      "format": "date-time"
    },
    "deliverTo": {
        "$ref": "#/components/schemas/Address"
        },
"deliveryOptions": {
"$ref": "#/components/schemas/LTLDeliveryOptions"
},
"carrierName": {
"type": "string",
"nullable": true
},
"weightClass": {
"$ref": "#/components/schemas/WeightClasses"
},
"packages": {
"type": "array",
"items": {
    "$ref": "#/components/schemas/ShipmentPackage"
        },
        "nullable": true
        }
        },
        "additionalProperties": false
        },
    "FulfillmentLocation": {
      "type": "object",
      "properties": {
        "name": {
          "type": "string",
          "nullable": true
        },
    "address": {
        "$ref": "#/components/schemas/Address"
        }
    },
"additionalProperties": false
},
"LTLDeliveryOptions": {
"type": "object",
"properties": {
    "isResidential": {
        "type": "boolean"
    },
    "isLiftGateRequired": {
      "type": "boolean"
    },
    "deliveryNotification": {
        "type": "boolean"
        }
    },
"additionalProperties": false
},
"LineItem": {
"type": "object",
"properties": {
    "id": {
        "type": "string",
        "nullable": true
    },
    "sku": {
      "type": "string",
      "nullable": true
    },
    "quantity": {
        "type": "integer",
            "format": "int32"
        },
"unitWeight": {
"type": "number",
"format": "double"
},
"serviceLevel": {
"$ref": "#/components/schemas/ServiceLevels"
},
"type": {
"$ref": "#/components/schemas/LineItemTypes"
},
"productId": {
"type": "string",
"nullable": true,
"readOnly": true
},
"variantId": {
"type": "string",
"nullable": true,
"readOnly": true
},
"colorId": {
"type": "string",
"nullable": true,
"readOnly": true
}
    },
"additionalProperties": false
},
"LineItemTypes": {
"enum": [
"FinishedGood",
"Sample",
"PromotionalGood"
],
"type": "string"
},
"ProductTypes": {
"enum": [
"Standard",
"Roll"
],
"type": "string"
},
"RulesBasedShippingQuoteRequest": {
"type": "object",
"properties": {
    "transactionId": {
        "type": "string",
        "nullable": true
    },
    "shipToAddress": {
      "$ref": "#/components/schemas/Address"
    },
    "lineItems": {
        "type": "array",
            "items": {
            "$ref": "#/components/schemas/LineItem"
                },
                "nullable": true
        },
"deliveryOptions": {
"$ref": "#/components/schemas/LTLDeliveryOptions"
}
    },
"additionalProperties": false
},
"ServiceLevels": {
"enum": [
"Standard",
"Expedited",
"NextDay",
"OutsideContinentalUS"
],
"type": "string"
},
"ShipmentPackage": {
"type": "object",
"properties": {
    "lineItemId": {
        "type": "string",
        "nullable": true
    },
    "units": {
      "type": "integer",
      "format": "int32"
    },
    "productType": {
        "$ref": "#/components/schemas/ProductTypes"
        },
"unitWeight": {
"type": "number",
"format": "double"
},
"dollarValue": {
"type": "number",
"format": "double"
},
"serviceLevel": {
"$ref": "#/components/schemas/ServiceLevels"
},
"lineItemType": {
"$ref": "#/components/schemas/LineItemTypes"
},
"applyUpCharge": {
"type": "boolean"
},
"upCharge": {
"$ref": "#/components/schemas/UpCharge"
}
    },
"additionalProperties": false
},
"UpCharge": {
"type": "object",
"properties": {
    "percentUpCharge": {
        "type": "number",
        "format": "double",
        "nullable": true
    },
    "surchargeDollarAmount": {
      "type": "number",
      "format": "double",
      "nullable": true
    }
  },
  "additionalProperties": false
},
    "WeightClasses": {
        "enum": [
            "CLASS_50",
            "CLASS_55",
            "CLASS_60",
            "CLASS_65",
            "CLASS_70",
            "CLASS_77_5",
            "CLASS_85",
            "CLASS_92_5",
            "CLASS_100",
            "CLASS_110",
            "CLASS_125",
            "CLASS_150",
            "CLASS_175",
            "CLASS_200",
            "CLASS_250",
            "CLASS_300",
            "CLASS_400",
            "CLASS_500",
            "NA"
            ],
            "type": "string"
        }
    }
}
    }
```




