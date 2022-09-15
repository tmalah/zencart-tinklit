Install instruction:

1. Unzip archive to any folder on your PC.

2. Copy files from this folder to zencart root folder.

3. See instructions to make changes in 2 files:
- admin\orders.txt to change file admin\orders.php
- includes\modules\pages\account\header_php.txt to change file includes\modules\pages\account\header_php.php

4. Run install.sql file in Admin -> Tools -> Install SQL Patches

-------------------------------------------------------------------

When customer confirm order on Checkout Confirmation page order created and set to status you set on Tinklit admin page (usually "Pending").
After finish (or not) payment customer will be redirected to My Account page. Script will check payment status and change order status to needed value ("Processing" or "Pending").
Also admin can update order status from Edit Order page.
For Tinkl.it orders admin can see additional info with payment info on Edit Order page.