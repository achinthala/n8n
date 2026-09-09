1. SimpleRequestTransformer - transforms simple form data to
   API format
2. AllRequestTransformer - transforms product/quote form data
   to API format
3. SimpleResponseTransformer - processes simple API responses
4. AllResponseTransformer - processes all API responses
5. SimpleShippingRatesApi - calls /simple endpoint
6. MockShippingRatesApi - provides mock data for /all endpoint

JavaScript Files Summary

allRequester.js - Handles complex product/quote
shipping rate requests
- Manages form validation for product and quote
  request types
- Implements product search and SKU selection
  functionality
- Validates quantities, weights, and quote numbers
- Submits requests to /all endpoint with
  comprehensive data preparation

allResultRenderer.js - Renders results from the /all
endpoint
- Displays line item shipments with fulfillment
  locations and carrier options
- Provides sorting (by cost, carrier, location,
  charge) and grouping capabilities
- Shows detailed tables with cost comparisons,
  savings calculations, and best rate highlighting
- Includes summary statistics and filtering options
  for invalid rates

shipping-rates.js - Legacy main controller (appears
to be replaced)
- Contains mock data toggle functionality for testing
- Basic form handling and product search
  implementation
- Simple results display - mainly used for initial
  development/testing

shippingRatesCoordinator.js - Main coordinator
orchestrating the system
- Routes between simple/product/quote request types
  to appropriate handlers
- Manages form state transitions and module
  initialization
- Coordinates between requesters (simpleRequester,
  allRequester) and renderers
- Handles common functionality like tooltips and form
  submission

simpleRequester.js - Handles simple weight-based
shipping requests
- Validates weight classes, total weight, and ZIP
  codes
- Formats ZIP codes and provides weight class context
- Submits to /simple endpoint with freight
  classification data
- Focuses on LTL/freight shipping scenarios

simpleResultRenderer.js - Renders results from the
/simple endpoint
- Optimized for displaying simple freight shipping
  rates
- Shows package information, carrier details, and
  cost comparisons
- Includes sorting and filtering capabilities similar
  to allResultRenderer
- Provides summary statistics for simple shipping
  scenarios

The architecture uses a coordinator pattern where
shippingRatesCoordinator.js acts as the main
controller, delegating to specialized
requester/renderer pairs based on the request type
selected by the user.
