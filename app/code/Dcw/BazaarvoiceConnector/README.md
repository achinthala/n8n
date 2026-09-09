# Dcw_BazaarvoiceConnector

This module customize the purchase feed from bazaarvoice connector, doing the 
following:

It excludes sample items from orders, and a complete order if everything is a sample.

### Settings

It adds a new setting under:

Stores -> Configuration -> General -> Bazaarvoice Connector -> Feeds -> Remove Samples from Purchase Feed

which allow the user to turn on/off this feature.

### Task Reference:

* [INS-5131](https://dotcomweavers.atlassian.net/browse/INS-5131)
