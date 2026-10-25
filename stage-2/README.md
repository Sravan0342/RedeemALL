# Stage 2: Database and storage for RedeemALL

> Part of the **RedeemALL** idea: one place to track every credit card offer so none expire unused. See the [main page](../README.md).
> Stage 1 put a demo page on a secured VM, with offers hard-coded in PHP. Stage 2 gives those offers a real home.

## Goal
- Offers and cards are saved in **MySQL running on its own private VM**, not typed into the code.
- Offer images (and later, uploaded statements or screenshots) live in an **Azure Storage account**.
- The database VM and the storage account are reachable **only from inside my VNet**, never from the internet.

## Design
```
Internet ── HTTP :80 ──► web VM (Apache + PHP)              VNet 10.0.0.0/16
                         web-subnet 10.0.1.0/24 (Stage 1)
                           │                         │
          MySQL :3306 / SSH :22                  HTTPS (private endpoint)
                           ▼                         ▼
                db VM (MySQL 8)              Storage account
                db-subnet 10.0.2.0/24        container: offer-images
                NO public IP                 storage-subnet 10.0.3.0/24
                                             public access OFF
```

| Resource | Choice | Why |
|----------|--------|-----|
| DB subnet | `db-subnet` `10.0.2.0/24` | Keeps the database tier separate from the web tier |
| DB VM | `redeemall-db`, Ubuntu 24.04, Standard_B1s, **no public IP** | Cheapest size; invisible from the internet |
| DB NSG | Allow 3306 and 22 **only from 10.0.1.0/24**; deny everything else | Only the web VM can talk to the database |
| Database | MySQL 8 server, database `redeemall`, app user limited to `10.0.1.%` | Even with the password, nobody outside the web subnet can sign in |
| Admin access | SSH to the DB VM **through the web VM** (jump host) | No extra public IP needed |
| Storage | Storage account (Standard LRS), blob container `offer-images` | Cheap, durable place for images and uploads |
| Storage access | Private endpoint in `storage-subnet` `10.0.3.0/24`, public access off | Only the VNet can reach it |
| Secrets | DB password in `/var/www/config.php`, **outside** the web folder and **not** in Git | Keeps credentials out of the public repo |

## Steps
1. Add `db-subnet` (`10.0.2.0/24`) to the existing VNet.
2. Create the `redeemall-db` VM in `db-subnet` with no public IP, and its NSG rules.
3. From the web VM, SSH to the DB VM's private IP and install MySQL server.
4. Let MySQL listen on the private network, create the `redeemall` database, the app user and the tables, and load the demo offers.
5. On the web VM, install `php-mysql`, add `config.php`, and switch `index.php` to read and update offers from MySQL.
6. Create the storage account and `offer-images` container, add a private endpoint on `storage-subnet`, turn public access off.
7. Upload the images; PHP builds image links from the container.
8. Prove it's private: the DB and storage can't be reached from my laptop, only from the web VM.
9. Document: screenshots, problems hit, what I learned.

## Cost (rough)
A Standard_B1s VM costs roughly **$8 to $10 a month** if left running, and can be stopped when not in use. Storage for a few images costs pennies.

## Status
In progress.
