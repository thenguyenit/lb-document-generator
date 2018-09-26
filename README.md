#Magento2 LB Document Generator module 
It's using to generate the Shipment/Invoice and return document,
Then It will be used to create the Shipment/Invoice and Return document on Logic Broker

Installation Instructions
--------------------------
Add these lines to the composer.json of your project

```
"require":{
    ...
    "fossil-ecommerce/lb-document-generator": "*"
 }
 ```
 
 ```
 "repositories":[
      ...
     {
        "type": "vcs",
        "url":"https://bitbucket.org/fossil-ecommerce/lb-document-generator.git"
     }
 ]
```

Changelog
----------

* 1.0.0 - First release