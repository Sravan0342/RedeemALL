# Stage 2: Database and storage for RedeemALL

> Part of the **RedeemALL** idea: one place to track every credit card offer so none expire unused. See the [main page](../README.md).
> Stage 1 put a demo page on a secured VM, with offers hard-coded in PHP. Stage 2 gives those offers a real home.

## Goal
- Offers and cards are saved in a **managed Azure MySQL database**, not typed into the code.
- Offer images (and later, uploaded statements or screenshots) live in an **Azure Storage account**.
- Both are reachable **only from my VNet**, not from the open internet.

## Plan
```
Internet ── HTTP :80 ──► NSG ──► Ubuntu VM (Apache + PHP)          VNet 10.0.0.0/16
                                   │ subnet 10.0.1.0/24
                                   │
                ┌──────────────────┴───────────────────┐
                ▼ private (port 3306)                   ▼ private endpoint (HTTPS)
   Azure Database for MySQL                  Storage account
   Flexible Server                           blob container: offer-images
   subnet 10.0.2.0/24 (delegated)            subnet 10.0.3.0/24
```

| Resource | Choice | Why |
|----------|--------|-----|
| Database | Azure Database for MySQL Flexible Server, Burstable **B1ms**, private access in the VNet | MySQL works natively with PHP; B1ms is the cheapest tier; no public IP |
| DB subnet | `10.0.2.0/24`, delegated to MySQL | Flexible Server needs its own subnet |
| Storage | Storage account (Standard LRS) with a blob container `offer-images` | Cheap, durable place for images and uploads |
| Storage access | Private endpoint in `10.0.3.0/24`, public network access off | Only the VM can reach it |
| Secrets | DB password in a `config.php` **outside** the web folder and **not** in Git | Keeps credentials out of the public repo |

## Steps
1. **Subnets:** add `db-subnet` (`10.0.2.0/24`, delegated to `Microsoft.DBforMySQL/flexibleServers`) and `storage-subnet` (`10.0.3.0/24`) to the existing VNet.
2. **Database:** create the MySQL Flexible Server in `RedeemALL_RG`, same region as the VM, private access on `db-subnet`. Create database `redeemall`.
3. **Connect from the VM:** `sudo apt install mysql-client php-mysql`, then test with `mysql -h <server>.mysql.database.azure.com -u <admin> -p`.
4. **Tables:** create `cards` and `offers` tables and load the demo offers.
5. **PHP:** change `index.php` to read and update offers from MySQL (PDO) instead of the hard-coded list.
6. **Storage:** create the storage account and `offer-images` container, add a private endpoint on `storage-subnet`, turn public access off.
7. **Images:** upload the images to the container; PHP builds image links from the container (short-lived SAS links).
8. **Prove it's private:** show the DB and storage can't be reached from my laptop, only from the VM.
9. **Document:** screenshots, problems hit, what I learned.

## Cost (rough)
MySQL B1ms with 20 GB storage costs roughly **$15 to $20 a month** if left running. It can be **stopped** when not in use to save money. Storage for a few images costs pennies.

## Status
Planned.
